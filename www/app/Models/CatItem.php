<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatItem extends Model
{
    protected $table = 'catalogos.cat_item';
    protected $primaryKey = 'id_item';
    public $timestamps = false;

    protected $fillable = [
        'id_cat',
        'descripcion',
        'abreviado',
        'texto',
        'orden',
        'predeterminado',
        'otro',
        'habilitado',
        'pendiente_revisar',
        'id_entrevistador',
        'id_reclasificado',
    ];

    public function rel_catalogo() {
        return $this->belongsTo(CatCat::class, 'id_cat', 'id_cat');
    }

    public static function listado_items($id_cat, $vacio = "", $solo_habilitados = true) {
        $query = self::where('id_cat', $id_cat);

        if ($solo_habilitados) {
            $query->where('habilitado', 1);
        }

        $listado = $query->orderby('orden')->pluck('descripcion', 'id_item');

        if (strlen($vacio) > 0) {
            $listado->prepend($vacio, -1);
        }
        return $listado->toArray();
    }

    public static function listado_por_catalogo($nombre_catalogo, $vacio = "") {
        $catalogo = CatCat::where('nombre', $nombre_catalogo)->first();
        if (!$catalogo) {
            return [];
        }
        return self::listado_items($catalogo->id_cat, $vacio);
    }
}
