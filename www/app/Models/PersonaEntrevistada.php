<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonaEntrevistada extends Model
{
    protected $table = 'fichas.persona_entrevistada';
    protected $primaryKey = 'id_persona_entrevistada';

    protected $fillable = [
        'id_persona',
        'id_e_ind_fvt',
        'es_victima',
        'es_testigo',
        'es_familiar',
        'edad',
        'sintesis_relato',
    ];

    public function rel_persona() {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    public function rel_entrevista() {
        return $this->belongsTo(Entrevista::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    public function rel_consentimiento() {
        return $this->hasOne(ConsentimientoInformado::class, 'id_persona_entrevistada', 'id_persona_entrevistada');
    }

    public function getFmtTipoAttribute() {
        $tipos = [];
        if ($this->es_victima == 1) $tipos[] = 'Víctima';
        if ($this->es_testigo == 1) $tipos[] = 'Testigo';
        if ($this->es_familiar == 1) $tipos[] = 'Familiar';
        return count($tipos) > 0 ? implode(', ', $tipos) : 'Sin especificar';
    }
}
