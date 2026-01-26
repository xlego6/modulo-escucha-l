<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatCat extends Model
{
    protected $table = 'catalogos.cat_cat';
    protected $primaryKey = 'id_cat';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'editable',
        'id_reclasificado',
    ];

    public function rel_items() {
        return $this->hasMany(CatItem::class, 'id_cat', 'id_cat');
    }

    public static function listado_catalogos() {
        return self::orderby('nombre')->pluck('nombre', 'id_cat')->toArray();
    }
}
