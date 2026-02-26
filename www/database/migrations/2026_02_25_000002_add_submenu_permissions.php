<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSubmenuPermissions extends Migration
{
    /**
     * Agrega filas de permisos para los submódulos de procesamientos
     * a todos los roles existentes.
     */
    public function up()
    {
        // Submódulos y los niveles de sistema que tenían acceso hardcodeado
        $submodulos = [
            'procesamientos.transcripcion' => [1, 2],
            'procesamientos.edicion'       => [1, 2, 4],
            'procesamientos.entidades'     => [1, 2],
            'procesamientos.anonimizacion' => [1, 2, 4],
        ];

        $puedeEliminarNiveles = [1, 2];

        // Obtener todos los roles existentes
        $roles = DB::select("SELECT id_nivel FROM esclarecimiento.rol");

        foreach ($roles as $rol) {
            $nivel = $rol->id_nivel;

            foreach ($submodulos as $modulo => $nivelesAcceso) {
                // Para roles del sistema, usar el mapeo hardcodeado original
                // Para roles custom (>=10), no dar acceso por defecto
                $ver = in_array($nivel, $nivelesAcceso);

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
        $submodulos = [
            'procesamientos.transcripcion',
            'procesamientos.edicion',
            'procesamientos.entidades',
            'procesamientos.anonimizacion',
        ];

        foreach ($submodulos as $modulo) {
            DB::statement("DELETE FROM esclarecimiento.rol_modulo_permiso WHERE modulo = ?", [$modulo]);
        }
    }
}
