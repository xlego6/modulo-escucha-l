<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Detecta el schema donde vive 'users' y agrega la columna si no existe.
        // Necesario porque el search_path varía entre entornos.
        DB::statement("
            DO \$\$
            DECLARE v_schema text;
            BEGIN
                -- Si la columna ya existe, no hacer nada
                SELECT table_schema INTO v_schema
                FROM information_schema.columns
                WHERE table_name = 'users' AND column_name = 'is_login_directory_active'
                LIMIT 1;
                IF v_schema IS NOT NULL THEN RETURN; END IF;

                -- Buscar en qué schema está la tabla users
                SELECT table_schema INTO v_schema
                FROM information_schema.tables
                WHERE table_name = 'users'
                LIMIT 1;

                IF v_schema IS NOT NULL THEN
                    EXECUTE format(
                        'ALTER TABLE %I.users ADD COLUMN is_login_directory_active boolean NOT NULL DEFAULT false',
                        v_schema
                    );
                END IF;
            END \$\$
        ");
    }

    public function down(): void
    {
        DB::statement("
            DO \$\$
            DECLARE v_schema text;
            BEGIN
                SELECT table_schema INTO v_schema
                FROM information_schema.columns
                WHERE table_name = 'users' AND column_name = 'is_login_directory_active'
                LIMIT 1;
                IF v_schema IS NOT NULL THEN
                    EXECUTE format('ALTER TABLE %I.users DROP COLUMN is_login_directory_active', v_schema);
                END IF;
            END \$\$
        ");
    }
};
