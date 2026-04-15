<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE esclarecimiento.permiso
            ADD COLUMN IF NOT EXISTS motivo_rechazo TEXT
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE esclarecimiento.permiso
            DROP COLUMN IF EXISTS motivo_rechazo
        ");
    }
};
