<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'esclarecimiento.rol';
    protected $primaryKey = 'id_nivel';
    public $timestamps = false;

    protected $fillable = [
        'id_nivel',
        'nombre',
        'descripcion',
        'es_sistema',
        'habilitado',
        'orden',
    ];

    protected $casts = [
        'es_sistema' => 'boolean',
        'habilitado' => 'boolean',
    ];

    public function permisos()
    {
        return $this->hasMany(RolModuloPermiso::class, 'id_nivel', 'id_nivel');
    }

    /**
     * Siguiente id_nivel disponible para roles custom (mínimo 10)
     */
    public static function siguienteNivel(): int
    {
        $max = self::where('es_sistema', false)->max('id_nivel');
        return max($max ? $max + 1 : 10, 10);
    }

    /**
     * Listado para selects: [id_nivel => nombre]
     */
    public static function listado_items(string $vacio = ''): array
    {
        $listado = self::where('habilitado', true)
            ->orderBy('orden')
            ->pluck('nombre', 'id_nivel');

        if (strlen($vacio) > 0) {
            $listado = $listado->prepend($vacio, '');
        }

        return $listado->toArray();
    }
}
