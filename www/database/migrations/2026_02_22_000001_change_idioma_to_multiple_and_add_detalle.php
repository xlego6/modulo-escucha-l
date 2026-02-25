<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Crear tabla junction para idiomas múltiples
        Schema::create('esclarecimiento.entrevista_idioma', function (Blueprint $table) {
            $table->integer('id_e_ind_fvt');
            $table->integer('id_idioma');
            $table->timestamp('created_at')->nullable();
        });

        // Agregar opción "Otro(s)" al catálogo de idiomas (id_cat=8)
        DB::table('catalogos.cat_item')->insert([
            'id_item' => 325,
            'id_cat' => 8,
            'descripcion' => 'Otro(s)',
            'orden' => 99,
            'habilitado' => 1,
        ]);

        // Agregar columna detalle_idiomas a la tabla de entrevistas
        Schema::table('esclarecimiento.e_ind_fvt', function (Blueprint $table) {
            $table->text('detalle_idiomas')->nullable();
        });

        // Migrar datos existentes: copiar id_idioma actual a la junction table
        DB::statement("
            INSERT INTO esclarecimiento.entrevista_idioma (id_e_ind_fvt, id_idioma, created_at)
            SELECT id_e_ind_fvt, id_idioma, NOW()
            FROM esclarecimiento.e_ind_fvt
            WHERE id_idioma IS NOT NULL
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
