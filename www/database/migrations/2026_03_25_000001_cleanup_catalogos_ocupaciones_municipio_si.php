<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CleanupCatalogosOcupacionesMunicipioSi extends Migration
{
    public function up()
    {
        // Sincronizar secuencia de cat_item
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('catalogos.cat_item', 'id_item'),
                COALESCE((SELECT MAX(id_item) FROM catalogos.cat_item), 1)
            )
        ");

        // =============================================
        // 1. DISCAPACIDAD (id_cat = 15)
        // Deshabilitar todas y dejar solo las del listado
        // =============================================
        DB::statement("UPDATE catalogos.cat_item SET habilitado = 0 WHERE id_cat = 15");

        $discapacidades = [
            ['Discapacidad física', 1],
            ['Discapacidad funcional', 2],
            ['Discapacidad intelectual', 3],
            ['Discapacidad múltiple', 4],
            ['Discapacidad psicosocial', 5],
            ['Discapacidad sensorial', 6],
            ['Ninguna', 7],
            ['No especificada', 8],
        ];

        foreach ($discapacidades as [$descripcion, $orden]) {
            DB::statement("
                INSERT INTO catalogos.cat_item (id_cat, descripcion, habilitado, orden)
                SELECT 15, ?, 1, ?
                WHERE NOT EXISTS (
                    SELECT 1 FROM catalogos.cat_item WHERE id_cat = 15 AND descripcion = ?
                )
            ", [$descripcion, $orden, $descripcion]);

            DB::statement("
                UPDATE catalogos.cat_item SET habilitado = 1, orden = ?
                WHERE id_cat = 15 AND descripcion = ?
            ", [$orden, $descripcion]);
        }

        // =============================================
        // 2. RANGOS DE EDAD (id_cat = 14)
        // Verificar que solo estén habilitados los correctos
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

            DB::statement("
                UPDATE catalogos.cat_item SET habilitado = 1, orden = ?
                WHERE id_cat = 14 AND descripcion = ?
            ", [$orden, $descripcion]);
        }

        // =============================================
        // 3. RESPONSABLES COLECTIVOS (id_cat = 17)
        // Deshabilitar todas y dejar solo las del listado
        // =============================================
        DB::statement("UPDATE catalogos.cat_item SET habilitado = 0 WHERE id_cat = 17");

        $responsables = [
            ['Agente del Estado', 1],
            ['Organismos de inteligencia', 2],
            ['Armada Nacional de Colombia', 3],
            ['Fuerza Aérea Nacional de Colombia', 4],
            ['Ejército Nacional de Colombia', 5],
            ['Policía Nacional de Colombia', 6],
            ['Autodefensas', 7],
            ['Grupos armados disidentes', 8],
            ['Grupos armados posdesmovilización', 9],
            ['Grupos guerrilleros', 10],
            ['Grupos paramilitares', 11],
            ['Convivir', 12],
            ['Juntas de autodefensa', 13],
            ['Intervención militar extranjera', 14],
            ['Bandolerismo', 15],
            ['Bacrim', 16],
            ['Grupo Armado No Identificado', 17],
            ['Desconocido', 18],
        ];

        foreach ($responsables as [$descripcion, $orden]) {
            DB::statement("
                INSERT INTO catalogos.cat_item (id_cat, descripcion, habilitado, orden)
                SELECT 17, ?, 1, ?
                WHERE NOT EXISTS (
                    SELECT 1 FROM catalogos.cat_item WHERE id_cat = 17 AND descripcion = ?
                )
            ", [$descripcion, $orden, $descripcion]);

            DB::statement("
                UPDATE catalogos.cat_item SET habilitado = 1, orden = ?
                WHERE id_cat = 17 AND descripcion = ?
            ", [$orden, $descripcion]);
        }

        // =============================================
        // 4. OCUPACIONES (id_cat = 11)
        // Deshabilitar todas y dejar solo las del listado
        // =============================================
        DB::statement("UPDATE catalogos.cat_item SET habilitado = 0 WHERE id_cat = 11");

        $ocupaciones = [
            ['Administrador de Finca', 1],
            ['Ama de Casa', 2],
            ['Abogados', 3],
            ['Artesanos', 4],
            ['Campesinos', 5],
            ['Comerciante', 6],
            ['Delincuente', 7],
            ['Docentes', 8],
            ['Economía Informal', 9],
            ['Empleado', 10],
            ['Empresario - Industrial', 11],
            ['Estudiantes', 12],
            ['Fuerza Pública', 13],
            ['Funcionarios', 14],
            ['Gestores de archivos', 15],
            ['Ganadero/Hacendado', 16],
            ['Guerrilleros', 17],
            ['Miembro de Grupo Post-desmovilización', 18],
            ['Mineros', 19],
            ['Músicos', 20],
            ['Obrero', 21],
            ['Paramilitares', 22],
            ['Periodistas', 23],
            ['Pescadores', 24],
            ['Profesional', 25],
            ['Religioso', 26],
            ['Sacerdotes', 27],
            ['Seguridad Privada', 28],
            ['Terrateniente', 29],
            ['Trabajador de Finca', 30],
            ['Trabajadores sexuales', 31],
            ['Trabajadores de la salud', 32],
            ['Transportadores', 33],
            ['Turistas', 34],
            ['Pensionado', 35],
            ['Erradicador', 36],
            ['Raspachines', 37],
            ['Bandolero', 38],
            ['Desempleado', 39],
            ['Trabajo Sin Especificar', 40],
        ];

        foreach ($ocupaciones as [$descripcion, $orden]) {
            DB::statement("
                INSERT INTO catalogos.cat_item (id_cat, descripcion, habilitado, orden)
                SELECT 11, ?, 1, ?
                WHERE NOT EXISTS (
                    SELECT 1 FROM catalogos.cat_item WHERE id_cat = 11 AND descripcion = ?
                )
            ", [$descripcion, $orden, $descripcion]);

            DB::statement("
                UPDATE catalogos.cat_item SET habilitado = 1, orden = ?
                WHERE id_cat = 11 AND descripcion = ?
            ", [$orden, $descripcion]);
        }

        // =============================================
        // 5. MUNICIPIO "Sin Información" genérico
        // Se usa un único registro que el API siempre incluye
        // =============================================
        // Sincronizar secuencia de geo
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('catalogos.geo', 'id_geo'),
                COALESCE((SELECT MAX(id_geo) FROM catalogos.geo), 1)
            )
        ");

        // Asegurar que existe el municipio "Sin Información"
        // (hijo del departamento "Sin Información" ya creado en migración anterior)
        DB::statement("
            INSERT INTO catalogos.geo (id_padre, nivel, descripcion, codigo)
            SELECT g.id_geo, 3, 'Sin Información', 'SI'
            FROM catalogos.geo g
            WHERE g.nivel = 2 AND g.descripcion = 'Sin Información'
            AND NOT EXISTS (
                SELECT 1 FROM catalogos.geo WHERE nivel = 3 AND descripcion = 'Sin Información' AND id_padre = g.id_geo
            )
        ");
    }

    public function down()
    {
        // Re-habilitar items deshabilitados
        DB::statement("UPDATE catalogos.cat_item SET habilitado = 1 WHERE id_cat IN (11, 14, 15, 17)");
    }
}
