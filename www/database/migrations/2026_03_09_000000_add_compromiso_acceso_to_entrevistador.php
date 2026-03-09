<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompromisoAccesoToEntrevistador extends Migration
{
    public function up()
    {
        Schema::connection('pgsql')->table('esclarecimiento.entrevistador', function (Blueprint $table) {
            $table->timestamp('compromiso_acceso')->nullable()->after('compromiso_reserva');
        });
    }

    public function down()
    {
        Schema::connection('pgsql')->table('esclarecimiento.entrevistador', function (Blueprint $table) {
            $table->dropColumn('compromiso_acceso');
        });
    }
}
