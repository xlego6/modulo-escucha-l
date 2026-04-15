<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Crear tabla junction para idiomas múltiples (IF NOT EXISTS)
        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.entrevista_idioma (
                id_e_ind_fvt integer NOT NULL,
                id_idioma    integer NOT NULL,
                created_at   timestamp NULL
            )
        ");

        // Agregar opción "Otro(s)" al catálogo de idiomas (ignorar si ya existe)
        DB::table('catalogos.cat_item')->insertOrIgnore([
            'id_item' => 325,
            'id_cat' => 8,
            'descripcion' => 'Otro(s)',
            'orden' => 99,
            'habilitado' => 1,
        ]);

        // Agregar columna detalle_idiomas (IF NOT EXISTS)
        DB::statement("
            ALTER TABLE esclarecimiento.e_ind_fvt
            ADD COLUMN IF NOT EXISTS detalle_idiomas text
        ");

        // Migrar datos existentes solo si la junction table está vacía
        DB::statement("
            INSERT INTO esclarecimiento.entrevista_idioma (id_e_ind_fvt, id_idioma, created_at)
            SELECT id_e_ind_fvt, id_idioma, NOW()
            FROM esclarecimiento.e_ind_fvt
            WHERE id_idioma IS NOT NULL
            AND NOT EXISTS (SELECT 1 FROM esclarecimiento.entrevista_idioma LIMIT 1)
        ");
    }

    public function down()
    {
        Schema::table('esclarecimiento.e_ind_fvt', function (Blueprint $table) {
            $table->dropColumn('detalle_idiomas');
        });

        DB::table('catalogos.cat_item')->where('id_item', 325)->delete();

        Schema::dropIfExists('esclarecimiento.entrevista_idioma');
    }
};
