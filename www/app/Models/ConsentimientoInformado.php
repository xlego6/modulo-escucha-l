<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentimientoInformado extends Model
{
    protected $table = 'fichas.consentimiento_informado';
    protected $primaryKey = 'id_consentimiento';

    protected $fillable = [
        'id_persona_entrevistada',
        'tiene_documento_autorizacion',
        'es_menor_edad',
        'autoriza_ser_entrevistado',
        'permite_grabacion',
        'permite_procesamiento_misional',
        'permite_uso_conservacion_consulta',
        'considera_riesgo_seguridad',
        'autoriza_datos_personales_sin_anonimizar',
        'autoriza_datos_sensibles_sin_anonimizar',
        'observaciones',
    ];

    protected $casts = [
        'tiene_documento_autorizacion' => 'boolean',
        'es_menor_edad' => 'boolean',
        'autoriza_ser_entrevistado' => 'boolean',
        'permite_grabacion' => 'boolean',
        'permite_procesamiento_misional' => 'boolean',
        'permite_uso_conservacion_consulta' => 'boolean',
        'considera_riesgo_seguridad' => 'boolean',
        'autoriza_datos_personales_sin_anonimizar' => 'boolean',
        'autoriza_datos_sensibles_sin_anonimizar' => 'boolean',
    ];

    public function rel_persona_entrevistada()
    {
        return $this->belongsTo(PersonaEntrevistada::class, 'id_persona_entrevistada', 'id_persona_entrevistada');
    }
}
