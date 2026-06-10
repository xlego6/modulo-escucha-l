<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nivela la definición de users.is_login_directory_active con producción.
 *
 * La migración 2026_02_24_000005 la crea como NOT NULL DEFAULT false, pero en
 * entornos donde la columna ya existía (nullable) el ADD COLUMN IF NOT EXISTS
 * la saltó. Solo cambia la definición de la columna: no altera valores de la
 * bandera ni la configuración del directorio activo. Idempotente: en entornos
 * ya nivelados (prod/pruebas) es un no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE public.users
            SET is_login_directory_active = false
            WHERE is_login_directory_active IS NULL
        ");
        DB::statement("
            ALTER TABLE public.users
            ALTER COLUMN is_login_directory_active SET DEFAULT false
        ");
        DB::statement("
            ALTER TABLE public.users
            ALTER COLUMN is_login_directory_active SET NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE public.users
            ALTER COLUMN is_login_directory_active DROP NOT NULL
        ");
    }
};
