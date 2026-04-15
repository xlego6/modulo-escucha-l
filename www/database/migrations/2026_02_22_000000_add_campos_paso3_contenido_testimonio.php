<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Agregar columnas a contenido_testimonio (IF NOT EXISTS para idempotencia)
        DB::statement("
            ALTER TABLE esclarecimiento.contenido_testimonio
            ADD COLUMN IF NOT EXISTS otras_poblaciones_mencionadas text,
            ADD COLUMN IF NOT EXISTS otras_ocupaciones_mencionadas text,
            ADD COLUMN IF NOT EXISTS detalle_grupos_etnicos text,
            ADD COLUMN IF NOT EXISTS otros_hechos_victimizantes text,
            ADD COLUMN IF NOT EXISTS detalle_resistencias text
        ");

        // Crear catálogo de prácticas de resistencia (ignorar si ya existe)
        DB::table('catalogos.cat_cat')->insertOrIgnore([
            'id_cat' => 20,
            'nombre' => 'practicas_resistencia',
            'descripcion' => 'Prácticas de resistencia',
        ]);

        // Insertar items del catálogo (ignorar duplicados)
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
            DB::table('catalogos.cat_item')->insertOrIgnore($item);
        }

        // Crear tabla junction para prácticas de resistencia (IF NOT EXISTS)
        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.contenido_practica_resistencia (
                id_e_ind_fvt integer NOT NULL,
                id_practica integer NOT NULL
            )
        ");
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
