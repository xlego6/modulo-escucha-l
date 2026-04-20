<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Nivel 5 (Gestor de conocimiento): entrevistas de su dependencia
        DB::statement("UPDATE esclarecimiento.rol_modulo_permiso
            SET alcance_dependencia = true
            WHERE id_nivel = 5 AND modulo = 'entrevistas'");

        // Adjuntos: solo visualizar (sin crear/editar/descargar), alcance dependencia
        DB::statement("UPDATE esclarecimiento.rol_modulo_permiso
            SET alcance_dependencia = true, puede_editar = false, puede_crear = false
            WHERE id_nivel = 5 AND modulo = 'adjuntos'");

        // Permisos: gestionar solicitudes de su dependencia
        DB::statement("UPDATE esclarecimiento.rol_modulo_permiso
            SET alcance_dependencia = true
            WHERE id_nivel = 5 AND modulo = 'permisos'");

        // Traza: ver propia + de su dependencia
        DB::statement("UPDATE esclarecimiento.rol_modulo_permiso
            SET puede_ver = true, alcance_propias = true, alcance_dependencia = true
            WHERE id_nivel = 5 AND modulo = 'traza'");
    }

    public function down(): void
    {
        DB::statement("UPDATE esclarecimiento.rol_modulo_permiso
            SET alcance_dependencia = false
            WHERE id_nivel = 5 AND modulo = 'entrevistas'");

        DB::statement("UPDATE esclarecimiento.rol_modulo_permiso
            SET alcance_dependencia = false, puede_editar = true, puede_crear = true
            WHERE id_nivel = 5 AND modulo = 'adjuntos'");

        DB::statement("UPDATE esclarecimiento.rol_modulo_permiso
            SET alcance_dependencia = false
            WHERE id_nivel = 5 AND modulo = 'permisos'");

        DB::statement("UPDATE esclarecimiento.rol_modulo_permiso
            SET puede_ver = false, alcance_propias = false, alcance_dependencia = false
            WHERE id_nivel = 5 AND modulo = 'traza'");
    }
};
