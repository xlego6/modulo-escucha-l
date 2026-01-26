<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Entrevistador extends Model
{
    protected $table = 'esclarecimiento.entrevistador';
    protected $primaryKey = 'id_entrevistador';

    protected $fillable = [
        'id_usuario',
        'id_macroterritorio',
        'id_territorio',
        'id_dependencia_origen',
        'numero_entrevistador',
        'id_ubicacion',
        'id_grupo',
        'id_nivel',
        'solo_lectura',
        'compromiso_reserva',
    ];

    public function rel_usuario() {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function rel_nivel() {
        return $this->belongsTo(CriterioFijo::class, 'id_nivel', 'id_opcion');
    }

    public function rel_dependencia_origen() {
        return $this->belongsTo(CatItem::class, 'id_dependencia_origen', 'id_item');
    }

    public function getFmtDependenciaOrigenAttribute() {
        $dep = $this->rel_dependencia_origen;
        return $dep ? $dep->descripcion : 'Sin asignar';
    }

    public function getFmtIdNivelAttribute() {
        $nivel = $this->rel_nivel;
        return $nivel ? $nivel->descripcion : 'Sin especificar';
    }

    public function getFmtNumeroEntrevistadorAttribute() {
        return str_pad($this->numero_entrevistador, 4, '0', STR_PAD_LEFT);
    }

    public static function listado_items($vacio = "") {
        $query = self::orderby('numero_entrevistador');
        $listado = $query->get()->pluck('fmt_numero_entrevistador', 'id_entrevistador');

        if (strlen($vacio) > 0 && count($listado) > 1) {
            $listado->prepend($vacio, -1);
        }
        return $listado->toArray();
    }
}
