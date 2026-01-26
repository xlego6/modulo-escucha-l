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
     * Tipos de entidades disponibles
     */
    public static function tipos()
    {
        return [
            'PER' => 'Persona',
            'LOC' => 'Lugar',
            'ORG' => 'Organización',
            'DATE' => 'Fecha',
            'EVENT' => 'Evento',
            'GUN' => 'Arma',
            'MISC' => 'Misceláneo',
        ];
    }
}
