<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LugarMencionado extends Model
{
    protected $table = 'esclarecimiento.contenido_lugar';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_e_ind_fvt',
        'id_departamento',
        'id_municipio',
    ];

    public function rel_entrevista() {
        return $this->belongsTo(Entrevista::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    public function rel_departamento() {
        return $this->belongsTo(Geo::class, 'id_departamento', 'id_geo');
    }

    public function rel_municipio() {
        return $this->belongsTo(Geo::class, 'id_municipio', 'id_geo');
    }
}
