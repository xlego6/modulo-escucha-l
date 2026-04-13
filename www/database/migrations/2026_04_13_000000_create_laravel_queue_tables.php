<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de jobs para la queue de Laravel (driver database)
        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.jobs (
                id          BIGSERIAL PRIMARY KEY,
                queue       VARCHAR(255) NOT NULL,
                payload     TEXT NOT NULL,
                attempts    SMALLINT NOT NULL DEFAULT 0,
                reserved_at INTEGER NULL,
                available_at INTEGER NOT NULL,
                created_at  INTEGER NOT NULL
            )
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_jobs_queue_reserved_available
            ON esclarecimiento.jobs (queue, reserved_at, available_at)
        ");

        // Tabla de jobs fallidos
        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.failed_jobs (
                id         BIGSERIAL PRIMARY KEY,
                uuid       VARCHAR(255) NOT NULL UNIQUE,
                connection TEXT NOT NULL,
                queue      TEXT NOT NULL,
                payload    TEXT NOT NULL,
                exception  TEXT NOT NULL,
                failed_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS esclarecimiento.failed_jobs");
        DB::statement("DROP TABLE IF EXISTS esclarecimiento.jobs");
    }
};
