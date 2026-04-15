<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompromisoAccesoToEntrevistador extends Migration
{
    public function up()
    {
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE esclarecimiento.entrevistador
            ADD COLUMN IF NOT EXISTS compromiso_acceso timestamp NULL
        ");
    }

    public function down()
    {
        Schema::connection('pgsql')->table('esclarecimiento.entrevistador', function (Blueprint $table) {
            $table->dropColumn('compromiso_acceso');
        });
    }
}
