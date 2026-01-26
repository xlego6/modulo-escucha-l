<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'fichas.persona';
    protected $primaryKey = 'id_persona';

    protected $fillable = [
        'nombre',
        'apellido',
        'alias',
        'fec_nac_a',
        'fec_nac_m',
        'fec_nac_d',
        'id_lugar_nacimiento',
        'id_lugar_nacimiento_depto',
        'id_sexo',
        'id_orientacion',
        'id_identidad',
        'id_etnia',
        'id_etnia_indigena',
        'id_tipo_documento',
        'num_documento',
        'id_nacionalidad',
        'id_estado_civil',
        'id_lugar_residencia',
        'id_lugar_residencia_muni',
        'id_lugar_residencia_depto',
        'id_zona',
        'telefono',
        'correo_electronico',
        'id_edu_formal',
        'profesion',
        'ocupacion_actual',
        'id_ocupacion_actual',
        // Nuevos campos
        'nombre_identitario',
        'id_rango_etario',
        'id_discapacidad',
    ];

    public function rel_sexo() {
        return $this->belongsTo(CatItem::class, 'id_sexo', 'id_item');
    }

    public function rel_etnia() {
        return $this->belongsTo(CatItem::class, 'id_etnia', 'id_item');
    }

    public function rel_tipo_documento() {
        return $this->belongsTo(CatItem::class, 'id_tipo_documento', 'id_item');
    }

    public function rel_lugar_nacimiento() {
        return $this->belongsTo(Geo::class, 'id_lugar_nacimiento', 'id_geo');
    }

    public function rel_lugar_residencia() {
        return $this->belongsTo(Geo::class, 'id_lugar_residencia', 'id_geo');
    }

    public function rel_orientacion() {
        return $this->belongsTo(CatItem::class, 'id_orientacion', 'id_item');
    }

    public function rel_identidad() {
        return $this->belongsTo(CatItem::class, 'id_identidad', 'id_item');
    }

    public function rel_rango_etario() {
        return $this->belongsTo(CatItem::class, 'id_rango_etario', 'id_item');
    }

    public function rel_discapacidad() {
        return $this->belongsTo(CatItem::class, 'id_discapacidad', 'id_item');
    }

    // Relaciones múltiples
    public function rel_poblaciones() {
        return $this->belongsToMany(CatItem::class, 'fichas.persona_poblacion', 'id_persona', 'id_poblacion', 'id_persona', 'id_item');
    }

    public function rel_ocupaciones() {
        return $this->belongsToMany(CatItem::class, 'fichas.persona_ocupacion', 'id_persona', 'id_ocupacion', 'id_persona', 'id_item');
    }

    public function getFmtNombreCompletoAttribute() {
        return trim($this->nombre . ' ' . $this->apellido);
    }

    public function getFmtSexoAttribute() {
        $sexo = $this->rel_sexo;
        return $sexo ? $sexo->descripcion : 'Sin especificar';
    }

    public function getFmtFechaNacimientoAttribute() {
        if (empty($this->fec_nac_a)) {
            return 'Sin fecha';
        }
        $d = $this->fec_nac_d ?? '??';
        $m = $this->fec_nac_m ?? '??';
        $a = $this->fec_nac_a;
        return "$d/$m/$a";
    }
}
