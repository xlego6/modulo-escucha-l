<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Índices trigram para la buscadora: permiten que ILIKE '%término%' sobre el
 * texto extraído y el nombre de los adjuntos use índice en vez de recorrer y
 * descomprimir todas las transcripciones en cada búsqueda.
 *
 * CREATE EXTENSION pg_trgm requiere superusuario en PostgreSQL < 13.
 * Los índices se crean CONCURRENTLY para no bloquear la subida de adjuntos
 * mientras se construyen; por eso la migración no corre dentro de transacción.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_adjunto_texto_extraido_trgm ON esclarecimiento.adjunto USING gin (texto_extraido gin_trgm_ops)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_adjunto_nombre_original_trgm ON esclarecimiento.adjunto USING gin (nombre_original gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS esclarecimiento.idx_adjunto_texto_extraido_trgm');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS esclarecimiento.idx_adjunto_nombre_original_trgm');
    }
};
