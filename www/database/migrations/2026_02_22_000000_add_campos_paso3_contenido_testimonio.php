<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Agregar columnas a contenido_testimonio
        Schema::table('esclarecimiento.contenido_testimonio', function (Blueprint $table) {
            $table->text('otras_poblaciones_mencionadas')->nullable();
            $table->text('otras_ocupaciones_mencionadas')->nullable();
            $table->text('detalle_grupos_etnicos')->nullable();
            $table->text('otros_hechos_victimizantes')->nullable();
            $table->text('detalle_resistencias')->nullable();
        });

        // Crear catálogo de prácticas de resistencia
        DB::table('catalogos.cat_cat')->insert([
            'id_cat' => 20,
            'nombre' => 'practicas_resistencia',
            'descripcion' => 'Prácticas de resistencia',
        ]);

        // Insertar items del catálogo
        $items = [
            ['id_item' => 316, 'id_cat' => 20, 'descripcion' => 'Prácticas de resistencia colectivas', 'orden' => 1, 'habilitado' => 1],
            ['id_item' => 317, 'id_cat' => 20, 'descripcion' => 'Prácticas de resistencia cultural', 'orden' => 2, 'habilitado' => 1],
            ['id_item' => 318, 'id_cat' => 20, 'descripcion' => 'Prácticas de resistencia de grupos específicos de personas', 'orden' => 3, 'habilitado' => 1],
            ['id_item' => 319, 'id_cat' => 20, 'descripcion' => 'Prácticas de resistencia económica', 'orden' => 4, 'habilitado' => 1],
            ['id_item' => 320, 'id_cat' => 20, 'descripcion' => 'Prácticas de resistencia espiritual', 'orden' => 5, 'habilitado' => 1],
            ['id_item' => 321, 'id_cat' => 20, 'descripcion' => 'Prácticas de resistencia individuales', 'orden' => 6, 'habilitado' => 1],
            ['id_item' => 322, 'id_cat' => 20, 'descripcion' => 'Prácticas de resistencia jurídica', 'orden' => 7, 'habilitado' => 1],
            ['id_item' => 323, 'id_cat' => 20, 'descripcion' => 'Prácticas de resistencia política', 'orden' => 8, 'habilitado' => 1],
            ['id_item' => 324, 'id_cat' => 20, 'descripcion' => 'Prácticas de resistencia social', 'orden' => 9, 'habilitado' => 1],
        ];

        foreach ($items as $item) {
            DB::table('catalogos.cat_item')->insert($item);
        }

        // Crear tabla junction para prácticas de resistencia
        Schema::create('esclarecimiento.contenido_practica_resistencia', function (Blueprint $table) {
            $table->integer('id_e_ind_fvt');
            $table->integer('id_practica');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esclarecimiento.contenido_practica_resistencia');

        DB::table('catalogos.cat_item')->where('id_cat', 20)->delete();
        DB::table('catalogos.cat_cat')->where('id_cat', 20)->delete();

        Schema::table('esclarecimiento.contenido_testimonio', function (Blueprint $table) {
            $table->dropColumn([
                'otras_poblaciones_mencionadas',
                'otras_ocupaciones_mencionadas',
                'detalle_grupos_etnicos',
                'otros_hechos_victimizantes',
                'detalle_resistencias',
            ]);
        });
    }
};
