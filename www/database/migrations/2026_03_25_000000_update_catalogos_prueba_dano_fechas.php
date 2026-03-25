<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateCatalogosPruebaDanoFechas extends Migration
{
    public function up()
    {
        // =============================================
        // 1. CATÁLOGOS - Población (id_cat = 9)
        // Completar listado si no existen
        // =============================================
        $poblaciones = [
            'Líderes y/o lideresas',
            'Personas refugiadas',
            'Personas inmigrantes',
            'Personas exiliadas',
            'Habitantes de calle',
            'Personas desmovilizadas',
            'Menores desvinculados',
            'Personas privadas de la libertad',
            'Sindicalistas',
            'Víctimas del conflicto armado',
            'Ex miembro de Fuerza Pública',
            'Experto(a)',
        ];

        foreach ($poblaciones as $orden => $descripcion) {
            DB::statement("
                INSERT INTO catalogos.cat_item (id_cat, descripcion, habilitado, orden)
                SELECT 9, ?, 1, ?
                WHERE NOT EXISTS (
                    SELECT 1 FROM catalogos.cat_item WHERE id_cat = 9 AND descripcion = ?
                )
            ", [$descripcion, $orden + 1, $descripcion]);
        }

        // =============================================
        // 2. CATÁLOGOS - Rangos de edad (id_cat = 14)
        // Reemplazar: deshabilitar existentes e insertar nuevos
        // =============================================
        DB::statement("UPDATE catalogos.cat_item SET habilitado = 0 WHERE id_cat = 14");

        $rangos = [
            ['Niñas y Niños (0 a 13 años)', 1],
            ['Adolescentes (14 a 17 años)', 2],
            ['Jóvenes (18 a 27 años)', 3],
            ['Personas adultas (27- 59 años)', 4],
            ['Personas mayores (60 años o mas)', 5],
        ];

        foreach ($rangos as [$descripcion, $orden]) {
            DB::statement("
                INSERT INTO catalogos.cat_item (id_cat, descripcion, habilitado, orden)
                SELECT 14, ?, 1, ?
                WHERE NOT EXISTS (
                    SELECT 1 FROM catalogos.cat_item WHERE id_cat = 14 AND descripcion = ?
                )
            ", [$descripcion, $orden, $descripcion]);

            // Si ya existía pero deshabilitado, rehabilitar
            DB::statement("
                UPDATE catalogos.cat_item SET habilitado = 1, orden = ?
                WHERE id_cat = 14 AND descripcion = ?
            ", [$orden, $descripcion]);
        }

        // =============================================
        // 3. CATÁLOGOS - Discapacidad (id_cat = 15)
        // Completar listado
        // =============================================
        $discapacidades = [
            'Discapacidad física',
            'Discapacidad funcional',
            'Discapacidad intelectual',
            'Discapacidad múltiple',
            'Discapacidad psicosocial',
            'Discapacidad sensorial',
            'Ninguna',
            'No especificada',
        ];

        foreach ($discapacidades as $orden => $descripcion) {
            DB::statement("
                INSERT INTO catalogos.cat_item (id_cat, descripcion, habilitado, orden)
                SELECT 15, ?, 1, ?
                WHERE NOT EXISTS (
                    SELECT 1 FROM catalogos.cat_item WHERE id_cat = 15 AND descripcion = ?
                )
            ", [$descripcion, $orden + 1, $descripcion]);
        }

        // =============================================
        // 4. CATÁLOGOS - Responsables colectivos (id_cat = 17)
        // Completar listado
        // =============================================
        $responsables = [
            'Agente del Estado',
            'Organismos de inteligencia',
            'Armada Nacional de Colombia',
            'Fuerza Aérea Nacional de Colombia',
            'Ejército Nacional de Colombia',
            'Policía Nacional de Colombia',
            'Autodefensas',
            'Grupos armados disidentes',
            'Grupos armados posdesmovilización',
            'Grupos guerrilleros',
            'Grupos paramilitares',
            'Convivir',
            'Juntas de autodefensa',
            'Intervención militar extranjera',
            'Bandolerismo',
            'Bacrim',
            'Grupo Armado No Identificado',
            'Desconocido',
        ];

        foreach ($responsables as $orden => $descripcion) {
            DB::statement("
                INSERT INTO catalogos.cat_item (id_cat, descripcion, habilitado, orden)
                SELECT 17, ?, 1, ?
                WHERE NOT EXISTS (
                    SELECT 1 FROM catalogos.cat_item WHERE id_cat = 17 AND descripcion = ?
                )
            ", [$descripcion, $orden + 1, $descripcion]);
        }

        // =============================================
        // 5. GEO - "Sin Información" para departamento y municipio
        // =============================================
        DB::statement("
            INSERT INTO catalogos.geo (id_padre, nivel, descripcion, codigo)
            SELECT 0, 2, 'Sin Información', 'SI'
            WHERE NOT EXISTS (
                SELECT 1 FROM catalogos.geo WHERE nivel = 2 AND descripcion = 'Sin Información'
            )
        ");

        // Obtener el id_geo del departamento "Sin Información"
        DB::statement("
            INSERT INTO catalogos.geo (id_padre, nivel, descripcion, codigo)
            SELECT g.id_geo, 3, 'Sin Información', 'SI'
            FROM catalogos.geo g
            WHERE g.nivel = 2 AND g.descripcion = 'Sin Información'
            AND NOT EXISTS (
                SELECT 1 FROM catalogos.geo WHERE nivel = 3 AND descripcion = 'Sin Información' AND id_padre = g.id_geo
            )
        ");

        // =============================================
        // 6. PRUEBA DE DAÑO - Nuevas columnas en consentimiento_informado
        // Valores: 1=Sí, 0=No, 2=No sabe, NULL=sin respuesta
        // =============================================
        DB::statement("
            ALTER TABLE fichas.consentimiento_informado
            ADD COLUMN IF NOT EXISTS prueba_dano_derechos_privados SMALLINT,
            ADD COLUMN IF NOT EXISTS prueba_dano_intereses_publicos SMALLINT,
            ADD COLUMN IF NOT EXISTS prueba_dano_inteligencia SMALLINT,
            ADD COLUMN IF NOT EXISTS prueba_dano_nna SMALLINT
        ");

        // =============================================
        // 7. FLAGS DE FECHA - Opción B: booleanos por componente
        // TRUE = el componente es conocido, FALSE = sin información
        // NULL = no se ha especificado (default, se trata como TRUE)
        // =============================================

        // En la tabla de entrevista (fechas de toma)
        DB::statement("
            ALTER TABLE esclarecimiento.e_ind_fvt
            ADD COLUMN IF NOT EXISTS fecha_toma_inicial_dia_conocido BOOLEAN DEFAULT TRUE,
            ADD COLUMN IF NOT EXISTS fecha_toma_inicial_mes_conocido BOOLEAN DEFAULT TRUE,
            ADD COLUMN IF NOT EXISTS fecha_toma_final_dia_conocido BOOLEAN DEFAULT TRUE,
            ADD COLUMN IF NOT EXISTS fecha_toma_final_mes_conocido BOOLEAN DEFAULT TRUE
        ");

        // En la tabla de contenido (fechas de hechos)
        DB::statement("
            ALTER TABLE esclarecimiento.contenido_testimonio
            ADD COLUMN IF NOT EXISTS fecha_hechos_inicial_dia_conocido BOOLEAN DEFAULT TRUE,
            ADD COLUMN IF NOT EXISTS fecha_hechos_inicial_mes_conocido BOOLEAN DEFAULT TRUE,
            ADD COLUMN IF NOT EXISTS fecha_hechos_final_dia_conocido BOOLEAN DEFAULT TRUE,
            ADD COLUMN IF NOT EXISTS fecha_hechos_final_mes_conocido BOOLEAN DEFAULT TRUE
        ");
    }

    public function down()
    {
        // Prueba de daño
        DB::statement("
            ALTER TABLE fichas.consentimiento_informado
            DROP COLUMN IF EXISTS prueba_dano_derechos_privados,
            DROP COLUMN IF EXISTS prueba_dano_intereses_publicos,
            DROP COLUMN IF EXISTS prueba_dano_inteligencia,
            DROP COLUMN IF EXISTS prueba_dano_nna
        ");

        // Flags de fecha - entrevista
        DB::statement("
            ALTER TABLE esclarecimiento.e_ind_fvt
            DROP COLUMN IF EXISTS fecha_toma_inicial_dia_conocido,
            DROP COLUMN IF EXISTS fecha_toma_inicial_mes_conocido,
            DROP COLUMN IF EXISTS fecha_toma_final_dia_conocido,
            DROP COLUMN IF EXISTS fecha_toma_final_mes_conocido
        ");

        // Flags de fecha - contenido
        DB::statement("
            ALTER TABLE esclarecimiento.contenido_testimonio
            DROP COLUMN IF EXISTS fecha_hechos_inicial_dia_conocido,
            DROP COLUMN IF EXISTS fecha_hechos_inicial_mes_conocido,
            DROP COLUMN IF EXISTS fecha_hechos_final_dia_conocido,
            DROP COLUMN IF EXISTS fecha_hechos_final_mes_conocido
        ");

        // Re-habilitar rangos etarios antiguos
        DB::statement("UPDATE catalogos.cat_item SET habilitado = 1 WHERE id_cat = 14");

        // No eliminamos los items de catálogo insertados para no romper FKs
    }
}
