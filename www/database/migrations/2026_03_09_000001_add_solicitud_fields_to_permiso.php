<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSolicitudFieldsToPermiso extends Migration
{
    public function up()
    {
        Schema::connection('pgsql')->table('esclarecimiento.permiso', function (Blueprint $table) {
            $table->boolean('es_solicitud')->default(false)->after('id_estado');
            $table->string('tipo_solicitud', 20)->nullable()->after('es_solicitud');
            $table->string('estado_solicitud', 20)->nullable()->after('tipo_solicitud');
            $table->timestamp('fecha_solicitud')->nullable()->after('estado_solicitud');
            $table->timestamp('fecha_respuesta')->nullable()->after('fecha_solicitud');
            $table->unsignedInteger('id_respondido_por')->nullable()->after('fecha_respuesta');
        });
    }

    public function down()
    {
        Schema::connection('pgsql')->table('esclarecimiento.permiso', function (Blueprint $table) {
            $table->dropColumn(['es_solicitud', 'tipo_solicitud', 'estado_solicitud', 'fecha_solicitud', 'fecha_respuesta', 'id_respondido_por']);
        });
    }
}
