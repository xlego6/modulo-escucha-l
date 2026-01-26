<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CriterioFijo extends Model
{
    protected $table = 'catalogos.criterio_fijo';
    protected $primaryKey = 'id_opcion';
    public $timestamps = false;

    protected $fillable = [
        'id_grupo',
        'descripcion',
        'abreviado',
        'orden',
        'habilitado',
    ];

    public static function listado_items($id_grupo, $vacio = "") {
        $query = self::where('id_grupo', $id_grupo)
            ->where('habilitado', 1)
            ->orderby('orden');

        $listado = $query->pluck('descripcion', 'id_opcion');

        if (strlen($vacio) > 0) {
            $listado->prepend($vacio, -1);
        }
        return $listado->toArray();
    }
}
