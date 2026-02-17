<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangeCompromisoReservaToTimestamp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Verificar si la columna aun es INTEGER antes de convertir
        $isInteger = DB::selectOne("
            SELECT data_type FROM information_schema.columns
            WHERE table_schema = 'esclarecimiento'
              AND table_name = 'entrevistador'
              AND column_name = 'compromiso_reserva'
        ");

        if ($isInteger && strtolower($isInteger->data_type) === 'integer') {
            DB::statement('ALTER TABLE esclarecimiento.entrevistador ALTER COLUMN compromiso_reserva DROP DEFAULT');
            DB::statement('UPDATE esclarecimiento.entrevistador SET compromiso_reserva = NULL WHERE compromiso_reserva = 0');
            DB::statement('ALTER TABLE esclarecimiento.entrevistador ALTER COLUMN compromiso_reserva TYPE TIMESTAMP USING CASE WHEN compromiso_reserva IS NOT NULL THEN NOW() ELSE NULL END');
            DB::statement('ALTER TABLE esclarecimiento.entrevistador ALTER COLUMN compromiso_reserva SET DEFAULT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE esclarecimiento.entrevistador ALTER COLUMN compromiso_reserva DROP DEFAULT');
        DB::statement('ALTER TABLE esclarecimiento.entrevistador ALTER COLUMN compromiso_reserva TYPE INTEGER USING CASE WHEN compromiso_reserva IS NOT NULL THEN 1 ELSE 0 END');
        DB::statement('ALTER TABLE esclarecimiento.entrevistador ALTER COLUMN compromiso_reserva SET DEFAULT 0');
    }
}
