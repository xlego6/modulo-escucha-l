<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntidadDetectada extends Model
{
    protected $table = 'esclarecimiento.entidad_detectada';
    protected $primaryKey = 'id_entidad';

    protected $fillable = [
        'id_e_ind_fvt',
        'tipo',
        'texto',
        'texto_anonimizado',
        'posicion_inicio',
        'posicion_fin',
        'confianza',
        'verificado',
        'excluir_anonimizacion',
        'manual',
    ];

    protected $casts = [
        'verificado' => 'boolean',
        'excluir_anonimizacion' => 'boolean',
        'manual' => 'boolean',
        'confianza' => 'float',
    ];

    /**
     * Relación con la entrevista
     */
    public function entrevista()
    {
        return $this->belongsTo(Entrevista::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    /**
     * Scope para filtrar por tipo de entidad
     */
    public function scopeOfType($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Taxonomía de etiquetas de anonimización (basada en la guía de anonimización
     * del expediente testimonial: insumoanon.xlsx / insumoarbol.txt).
     */
    public static function tipos()
    {
        return [
            'NUMERO' => 'Número/identificador',
            'PERSONA' => 'Persona',
            'ORGANIZACION' => 'Organización',
            'LUGAR' => 'Lugar',
            'GENTILICIO' => 'Gentilicio',
            'FECHA' => 'Fecha',
            'EDAD' => 'Edad',
            'OCUPACION' => 'Ocupación',
            'GRUPO_ARMADO' => 'Grupo armado',
            'ROL_ARMADO' => 'Rol en grupo armado',
            'ETNICO' => 'Étnico',
        ];
    }

    /**
     * Mapeo de las etiquetas que produce el modelo spaCy estándar en español
     * (es_core_news_lg/sm: PER, LOC, ORG, MISC) a la taxonomía propia. MISC no
     * tiene equivalente claro y se descarta deliberadamente: solo PERSONA, LUGAR
     * y ORGANIZACION se detectan automáticamente; el resto de la taxonomía
     * (NUMERO, GENTILICIO, FECHA, EDAD, OCUPACION, GRUPO_ARMADO, ROL_ARMADO,
     * ETNICO) solo se puede etiquetar manualmente en el editor.
     */
    public static function mapeoDeteccionAutomatica()
    {
        return [
            'PER' => 'PERSONA',
            'LOC' => 'LUGAR',
            'ORG' => 'ORGANIZACION',
        ];
    }

    /**
     * Tipos que por defecto vienen marcados para cubrir/anonimizar (la guía los
     * trata como "anonimizar siempre"); el resto queda desmarcado por defecto
     * ("en general no se anonimiza, excepto..."). Es solo un valor inicial de
     * checkbox, ajustable por documento.
     */
    public static function tiposPorDefecto()
    {
        return ['PERSONA', 'LUGAR', 'NUMERO', 'ORGANIZACION', 'GRUPO_ARMADO', 'ETNICO'];
    }
}
