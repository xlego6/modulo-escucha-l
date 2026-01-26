<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geo extends Model
{
    protected $table = 'catalogos.geo';
    protected $primaryKey = 'id_geo';
    public $timestamps = false;

    protected $fillable = [
        'id_padre',
        'nivel',
        'descripcion',
        'id_tipo',
        'codigo',
        'lat',
        'lon',
        'codigo_2',
    ];

    public function rel_padre() {
        return $this->belongsTo(Geo::class, 'id_padre', 'id_geo');
    }

    public function rel_hijos() {
        return $this->hasMany(Geo::class, 'id_padre', 'id_geo');
    }

    public function getFmtNombreCompletoAttribute() {
        $nombre = $this->descripcion;
        if ($this->nivel == 3 && $this->rel_padre) {
            $nombre .= ', ' . $this->rel_padre->descripcion;
        }
        return $nombre;
    }

    public function getNombreAttribute() {
        return $this->descripcion;
    }

    public static function listado_departamentos($vacio = "") {
        $query = self::where('nivel', 2)->orderby('descripcion');
        $listado = $query->pluck('descripcion', 'id_geo');

        if (strlen($vacio) > 0) {
            $listado->prepend($vacio, -1);
        }
        return $listado->toArray();
    }

    public static function listado_municipios($id_departamento = null, $vacio = "") {
        $query = self::where('nivel', 3)->orderby('descripcion');

        if ($id_departamento) {
            $query->where('id_padre', $id_departamento);
        }

        $listado = $query->pluck('descripcion', 'id_geo');

        if (strlen($vacio) > 0) {
            $listado->prepend($vacio, -1);
        }
        return $listado->toArray();
    }
}
