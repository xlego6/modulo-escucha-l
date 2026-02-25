<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateRolTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Tabla rol
        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.rol (
                id_nivel INTEGER PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                descripcion TEXT,
                es_sistema BOOLEAN DEFAULT FALSE,
                habilitado BOOLEAN DEFAULT TRUE,
                orden INTEGER DEFAULT 0
            )
        ");

        // Tabla rol_modulo_permiso
        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.rol_modulo_permiso (
                id_permiso_rol SERIAL PRIMARY KEY,
                id_nivel INTEGER REFERENCES esclarecimiento.rol(id_nivel),
                modulo VARCHAR(50) NOT NULL,
                puede_ver BOOLEAN DEFAULT FALSE,
                puede_crear BOOLEAN DEFAULT FALSE,
                puede_editar BOOLEAN DEFAULT FALSE,
                puede_eliminar BOOLEAN DEFAULT FALSE,
                alcance_propias BOOLEAN DEFAULT FALSE,
                alcance_dependencia BOOLEAN DEFAULT FALSE,
                alcance_todas BOOLEAN DEFAULT FALSE,
                UNIQUE(id_nivel, modulo)
            )
        ");

        DB::statement("CREATE INDEX IF NOT EXISTS idx_rol_modulo_permiso_nivel ON esclarecimiento.rol_modulo_permiso(id_nivel)");

        // =============================================
        // SEED: 5 roles del sistema
        // =============================================
        $roles = [
            [1, 'Administrador',          'Acceso completo al sistema',                        true, true, 1],
            [2, 'Lider',                  'Gestion de entrevistadores y transcripciones',       true, true, 2],
            [3, 'Entrevistador',          'Recoleccion y gestion de entrevistas',               true, true, 3],
            [4, 'Transcriptor',           'Edicion y gestion de transcripciones',               true, true, 4],
            [5, 'Gestor de Conocimiento', 'Consulta y analisis de informacion',                 true, true, 5],
        ];

        foreach ($roles as [$id, $nombre, $desc, $esSistema, $habilitado, $orden]) {
            DB::statement("
                INSERT INTO esclarecimiento.rol (id_nivel, nombre, descripcion, es_sistema, habilitado, orden)
                VALUES (?, ?, ?, ?, ?, ?)
                ON CONFLICT (id_nivel) DO NOTHING
            ", [$id, $nombre, $desc, $esSistema, $habilitado, $orden]);
        }

        // =============================================
        // SEED: Permisos por módulo
        // Traducidos del array hardcodeado en CheckNivelAcceso
        // =============================================

        // Niveles con acceso a cada módulo (puede_ver)
        $modulosAcceso = [
            'entrevistas'    => [1, 2, 3, 5],
            'adjuntos'       => [1, 2, 3, 4, 5],
            'personas'       => [1],
            'buscador'       => [1, 3, 5],
            'estadisticas'   => [1, 2, 3, 4, 5],
            'mapa'           => [1, 3, 5],
            'exportar'       => [1],
            'procesamientos' => [1, 2, 4],
            'permisos'       => [1, 3, 5],
            'usuarios'       => [1],
            'catalogos'      => [1],
            'traza'          => [1],
            'roles'          => [1],
        ];

        // Solo Admin (1) y Líder (2) pueden eliminar
        $puedeEliminarNiveles = [1, 2];

        foreach ($modulosAcceso as $modulo => $nivelAcceso) {
            foreach ([1, 2, 3, 4, 5] as $nivel) {
                $ver        = in_array($nivel, $nivelAcceso);
                $crear      = $ver;
                $editar     = $ver;
                $eliminar   = $ver && in_array($nivel, $puedeEliminarNiveles);
                $propias    = $ver && $nivel !== 1;
                $dependencia = $ver && $nivel === 2;
                $todas      = $ver && $nivel === 1;

                DB::statement("
                    INSERT INTO esclarecimiento.rol_modulo_permiso
                        (id_nivel, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar, alcance_propias, alcance_dependencia, alcance_todas)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON CONFLICT (id_nivel, modulo) DO NOTHING
                ", [
                    $nivel, $modulo,
                    $ver, $crear, $editar, $eliminar,
                    $propias, $dependencia, $todas,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement("DROP TABLE IF EXISTS esclarecimiento.rol_modulo_permiso");
        DB::statement("DROP TABLE IF EXISTS esclarecimiento.rol");
    }
}
