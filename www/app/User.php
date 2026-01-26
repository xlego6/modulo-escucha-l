<?php

namespace App;

use App\Models\Entrevistador;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function rel_entrevistador() {
        return $this->belongsTo(Entrevistador::class, 'id','id_usuario')->orderby('id_nivel');
    }

    public function tiene_perfil() {
        $perfil = Entrevistador::where('id_usuario', $this->id)->orderby('id_nivel')->first();
        return $perfil ? true : false;
    }

    public function getIdNivelAttribute() {
        $cual = Entrevistador::where('id_usuario', $this->id)->orderby('id_nivel')->first();
        return $cual ? $cual->id_nivel : 99;
    }

    public function getNivelAttribute() {
        return $this->id_nivel;
    }

    public function getIdEntrevistadorAttribute() {
        $cual = Entrevistador::where('id_usuario', $this->id)->orderby('id_nivel')->first();
        return $cual ? $cual->id_entrevistador : 0;
    }

    public function getFmtPrivilegiosAttribute() {
        $datos = Entrevistador::where('id_usuario', $this->id)->orderby('id_nivel')->first();
        return $datos ? $datos->fmt_id_nivel : "Sin Especificar";
    }

    public function getSoloLecturaAttribute() {
        $cual = Entrevistador::where('id_usuario', $this->id)->orderby('id_nivel')->first();
        return $cual ? $cual->solo_lectura : 1;
    }

    public function getImagenAttribute() {
        return empty($this->avatar) ? url("logo_vertical.jpg") : $this->avatar;
    }

    public static function listado_items($vacio = "") {
        $query = User::orderby('name');
        $listado = $query->pluck('name', 'id');

        if (strlen($vacio) > 0 && count($listado) > 1) {
            $listado->prepend($vacio, -1);
        }
        return $listado->toArray();
    }

    public function setEmailAttribute($val) {
        $this->attributes['email'] = mb_strtolower($val);
    }
}
