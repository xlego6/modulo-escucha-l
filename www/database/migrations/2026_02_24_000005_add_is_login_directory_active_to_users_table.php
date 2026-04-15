<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Crear tabla users si no existe (puede haber sido eliminada por migrate:fresh)
        DB::statement("
            CREATE TABLE IF NOT EXISTS public.users (
                id               SERIAL PRIMARY KEY,
                name             VARCHAR(255) NOT NULL,
                email            VARCHAR(255) NOT NULL,
                email_verified_at TIMESTAMP NULL,
                password         VARCHAR(255) NOT NULL,
                remember_token   VARCHAR(100) NULL,
                created_at       TIMESTAMP DEFAULT NOW(),
                updated_at       TIMESTAMP DEFAULT NOW(),
                CONSTRAINT users_email_unique UNIQUE (email)
            )
        ");

        DB::statement("
            ALTER TABLE public.users
            ADD COLUMN IF NOT EXISTS is_login_directory_active boolean NOT NULL DEFAULT false
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE public.users
            DROP COLUMN IF EXISTS is_login_directory_active
        ");
    }
};
