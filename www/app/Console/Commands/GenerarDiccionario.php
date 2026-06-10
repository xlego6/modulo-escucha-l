<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Genera el diccionario de datos en Markdown a partir de la introspección
 * de la base de datos (information_schema + pg_catalog).
 *
 * La ESTRUCTURA (tablas, columnas, tipos, nulabilidad, PK, FK reales) se lee
 * directamente del catálogo, por lo que siempre refleja la realidad.
 * Las DESCRIPCIONES provienen de los COMMENT ON de la BD (ver comando dic:comentar).
 *
 * Es determinístico: misma estructura + mismos comentarios => salida idéntica.
 * Útil además para detectar diferencias (drift) entre pruebas y producción.
 */
class GenerarDiccionario extends Command
{
    protected $signature = 'dic:generar
                            {--salida=diccionario_datos_generado.md : Ruta del archivo Markdown de salida (relativa a base_path)}
                            {--esquemas=public,esclarecimiento,fichas,catalogos : Esquemas a documentar (separados por coma)}';

    protected $description = 'Genera el diccionario de datos en Markdown introspeccionando la base de datos PostgreSQL';

    public function handle()
    {
        $esquemas = array_map('trim', explode(',', $this->option('esquemas')));
        $placeholders = implode(',', array_fill(0, count($esquemas), '?'));

        // Tablas base, en orden de esquema y nombre
        $tablas = DB::select("
            SELECT n.nspname AS esquema, c.relname AS tabla,
                   obj_description(c.oid) AS comentario
            FROM pg_class c
            JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE c.relkind = 'r' AND n.nspname IN ($placeholders)
            ORDER BY n.nspname, c.relname
        ", $esquemas);

        if (empty($tablas)) {
            $this->error('No se encontraron tablas en los esquemas indicados.');
            return 1;
        }

        $md  = "# Diccionario de Datos — Módulo Escucha Lite\n\n";
        $md .= "> Generado automáticamente con `php artisan dic:generar` el " . date('Y-m-d H:i') . ".\n";
        $md .= "> No editar a mano: las descripciones se mantienen como `COMMENT ON` en la BD.\n\n";
        $md .= "**Motor:** " . DB::selectOne('SELECT version() AS v')->v . "  \n";
        $md .= "**Base de datos:** `" . DB::selectOne('SELECT current_database() AS d')->d . "`  \n";
        $md .= "**Esquemas:** " . implode(', ', array_map(fn($e) => "`$e`", $esquemas)) . "\n\n---\n\n";

        // Índice
        $md .= "## Índice de tablas\n\n| # | Esquema | Tabla | Descripción |\n|---|---------|-------|-------------|\n";
        foreach ($tablas as $i => $t) {
            $md .= '| ' . ($i + 1) . " | {$t->esquema} | {$t->tabla} | " . $this->limpiar($t->comentario) . " |\n";
        }
        $md .= "\n---\n\n";

        // Detalle por tabla
        foreach ($tablas as $i => $t) {
            $md .= $this->seccionTabla($i + 1, $t);
        }

        $md .= $this->seccionCatalogos($esquemas);

        $ruta = base_path($this->option('salida'));
        file_put_contents($ruta, $md);

        $this->info('Diccionario generado: ' . $ruta);
        $this->info('Tablas documentadas: ' . count($tablas));

        $sinComentario = collect($tablas)->filter(fn($t) => empty($t->comentario))->count();
        if ($sinComentario > 0) {
            $this->warn("$sinComentario tablas sin comentario (descripción vacía). Use dic:comentar para sembrarlas.");
        }

        return 0;
    }

    private function seccionTabla(int $num, object $t): string
    {
        $cols = DB::select("
            SELECT c.column_name AS columna,
                   c.data_type,
                   c.character_maximum_length AS longitud,
                   c.numeric_precision AS precision,
                   c.numeric_scale AS escala,
                   c.is_nullable AS nulo,
                   c.column_default AS def,
                   pgd.description AS comentario
            FROM information_schema.columns c
            LEFT JOIN pg_catalog.pg_statio_all_tables st
                   ON st.schemaname = c.table_schema AND st.relname = c.table_name
            LEFT JOIN pg_catalog.pg_description pgd
                   ON pgd.objoid = st.relid AND pgd.objsubid = c.ordinal_position
            WHERE c.table_schema = ? AND c.table_name = ?
            ORDER BY c.ordinal_position
        ", [$t->esquema, $t->tabla]);

        // Claves primarias
        $pks = collect(DB::select("
            SELECT a.attname AS col
            FROM pg_index i
            JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
            WHERE i.indrelid = ?::regclass AND i.indisprimary
        ", ["{$t->esquema}.{$t->tabla}"]))->pluck('col')->all();

        // Claves foráneas reales (columna => esquema.tabla destino)
        $fks = [];
        foreach (DB::select("
            SELECT kcu.column_name AS col,
                   ccu.table_schema || '.' || ccu.table_name AS destino
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY'
              AND tc.table_schema = ? AND tc.table_name = ?
        ", [$t->esquema, $t->tabla]) as $fk) {
            $fks[$fk->col] = $fk->destino;
        }

        $titulo = $t->comentario ? ' — ' . $this->limpiar($t->comentario) : '';
        $s  = "## {$num}. `{$t->esquema}.{$t->tabla}`{$titulo}\n\n";
        $s .= "| Columna | Tipo | Nulo | PK/FK | Descripción |\n|---------|------|------|-------|-------------|\n";

        foreach ($cols as $c) {
            $pkfk = [];
            if (in_array($c->columna, $pks)) $pkfk[] = 'PK';
            if (isset($fks[$c->columna]))    $pkfk[] = 'FK → ' . $fks[$c->columna];

            $s .= '| ' . $c->columna
                . ' | ' . $this->tipo($c)
                . ' | ' . ($c->nulo === 'YES' ? 'Sí' : 'No')
                . ' | ' . implode(', ', $pkfk)
                . ' | ' . $this->limpiar($c->comentario)
                . " |\n";
        }

        $s .= $this->restricciones($t);

        return $s . "\n---\n\n";
    }

    /**
     * Restricciones CHECK y UNIQUE de la tabla (las PK y FK ya van columna a columna).
     * Los UNIQUE permiten verificar cardinalidades 1:1 sin consultar la BD.
     */
    private function restricciones(object $t): string
    {
        $regclass = "{$t->esquema}.{$t->tabla}";

        $checks = DB::select("
            SELECT conname, pg_get_constraintdef(oid) AS def
            FROM pg_constraint
            WHERE conrelid = ?::regclass AND contype = 'c'
            ORDER BY conname
        ", [$regclass]);

        $unicos = DB::select("
            SELECT ic.relname AS nombre, pg_get_indexdef(i.indexrelid) AS def
            FROM pg_index i
            JOIN pg_class ic ON ic.oid = i.indexrelid
            WHERE i.indrelid = ?::regclass AND i.indisunique AND NOT i.indisprimary
            ORDER BY ic.relname
        ", [$regclass]);

        if (empty($checks) && empty($unicos)) {
            return '';
        }

        $s = "\n**Restricciones:**\n\n";
        foreach ($checks as $c) {
            $s .= "- CHECK `{$c->conname}`: `" . $this->limpiar($c->def) . "`\n";
        }
        foreach ($unicos as $u) {
            $cols = preg_match('/\((.+)\)$/', $u->def, $m) ? $m[1] : $u->def;
            $s .= "- UNIQUE `{$u->nombre}`: (`" . $this->limpiar($cols) . "`)\n";
        }
        return $s;
    }

    /**
     * Contenido vivo de los catálogos (cat_cat → cat_item habilitados y criterio_fijo).
     * Son datos, no estructura: pueden variar entre servidores si un administrador
     * edita los catálogos desde la aplicación.
     */
    private function seccionCatalogos(array $esquemas): string
    {
        if (!in_array('catalogos', $esquemas)) {
            return '';
        }

        $s  = "## Valores de catálogo\n\n";
        $s .= "> Solo ítems con `habilitado = 1`. Los catálogos marcados como editables se\n";
        $s .= "> administran desde la aplicación, por lo que su contenido puede diferir entre servidores.\n\n";

        $cats = DB::select('SELECT id_cat, nombre, descripcion, editable FROM catalogos.cat_cat ORDER BY id_cat');
        foreach ($cats as $cat) {
            $items = DB::select('
                SELECT id_item, descripcion, abreviado
                FROM catalogos.cat_item
                WHERE id_cat = ? AND habilitado = 1
                ORDER BY orden NULLS LAST, id_item
            ', [$cat->id_cat]);

            $edit = $cat->editable ? 'editable' : 'no editable';
            $s .= "### `cat_cat` {$cat->id_cat}: {$cat->nombre} ({$edit}, " . count($items) . " ítems)\n\n";
            if ($cat->descripcion) {
                $s .= $this->limpiar($cat->descripcion) . "\n\n";
            }
            if (empty($items)) {
                $s .= "_Sin ítems habilitados._\n\n";
                continue;
            }
            $s .= "| id_item | Descripción | Abreviado |\n|---------|-------------|-----------|\n";
            foreach ($items as $it) {
                $s .= "| {$it->id_item} | " . $this->limpiar($it->descripcion) . ' | ' . $this->limpiar($it->abreviado) . " |\n";
            }
            $s .= "\n";
        }

        $s .= "### `criterio_fijo` — opciones fijas por grupo\n\n";
        $s .= "| id_grupo | id_opcion | Descripción | Abreviado |\n|----------|-----------|-------------|-----------|\n";
        $fijos = DB::select('
            SELECT id_grupo, id_opcion, descripcion, abreviado
            FROM catalogos.criterio_fijo
            WHERE habilitado = 1
            ORDER BY id_grupo, orden NULLS LAST, id_opcion
        ');
        foreach ($fijos as $cf) {
            $s .= "| {$cf->id_grupo} | {$cf->id_opcion} | " . $this->limpiar($cf->descripcion) . ' | ' . $this->limpiar($cf->abreviado) . " |\n";
        }

        return $s . "\n---\n\n";
    }

    private function tipo(object $c): string
    {
        $t = strtoupper($c->data_type);
        if ($c->longitud)  return "{$t}({$c->longitud})";
        if (in_array($c->data_type, ['numeric', 'decimal']) && $c->precision)
            return "{$t}({$c->precision},{$c->escala})";
        return $t;
    }

    private function limpiar(?string $s): string
    {
        return str_replace(['|', "\n", "\r"], [' ', ' ', ' '], trim((string) $s));
    }
}
