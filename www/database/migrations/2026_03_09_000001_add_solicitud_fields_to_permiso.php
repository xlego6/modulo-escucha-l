<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSolicitudFieldsToPermiso extends Migration
{
    public function up()
    {
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE esclarecimiento.permiso
            ADD COLUMN IF NOT EXISTS es_solicitud boolean NOT NULL DEFAULT false,
            ADD COLUMN IF NOT EXISTS tipo_solicitud varchar(20) NULL,
            ADD COLUMN IF NOT EXISTS estado_solicitud varchar(20) NULL,
            ADD COLUMN IF NOT EXISTS fecha_solicitud timestamp NULL,
            ADD COLUMN IF NOT EXISTS fecha_respuesta timestamp NULL,
            ADD COLUMN IF NOT EXISTS id_respondido_por integer NULL
        ");
    }

    public function down()
    {
        Schema::connection('pgsql')->table('esclarecimiento.permiso', function (Blueprint $table) {
            $table->dropColumn(['es_solicitud', 'tipo_solicitud', 'estado_solicitud', 'fecha_solicitud', 'fecha_respuesta', 'id_respondido_por']);
        });
    }
}
