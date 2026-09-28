<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrupa entidades que el anonimizador unio a mano como "la misma" (ej.
 * "Alejandra Hernandez" y "Aleja" -> misma PERSONA). Guarda la clave
 * normalizada de la etiqueta destino; NULL = grupo por su propio texto
 * normalizado (sin acentos, minusculas, sin puntuacion).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE esclarecimiento.entidad_detectada ADD COLUMN IF NOT EXISTS grupo VARCHAR(255) NULL');
        DB::statement("COMMENT ON COLUMN esclarecimiento.entidad_detectada.grupo IS 'Clave de agrupación (etiquetas unidas como la misma entidad); NULL = por su propio texto'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE esclarecimiento.entidad_detectada DROP COLUMN IF EXISTS grupo');
    }
};
