<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class RolModuloPermiso extends Model
{
    protected $table = 'esclarecimiento.rol_modulo_permiso';
    protected $primaryKey = 'id_permiso_rol';
    public $timestamps = false;

    protected $fillable = [
        'id_nivel',
        'modulo',
        'puede_ver',
        'puede_crear',
        'puede_editar',
        'puede_eliminar',
        'alcance_propias',
        'alcance_dependencia',
        'alcance_todas',
    ];

    protected $casts = [
        'puede_ver'           => 'boolean',
        'puede_crear'         => 'boolean',
        'puede_editar'        => 'boolean',
        'puede_eliminar'      => 'boolean',
        'alcance_propias'     => 'boolean',
        'alcance_dependencia' => 'boolean',
        'alcance_todas'       => 'boolean',
    ];

    /**
     * Obtiene todos los permisos de un nivel (con caché de 5 min)
     * Retorna: ['modulo' => [...campos...], ...]
     */
    public static function getPermisosPara(int $nivel): array
    {
        return Cache::remember("permisos_rol_{$nivel}", 300, function () use ($nivel) {
            return self::where('id_nivel', $nivel)
                ->get()
                ->keyBy('modulo')
                ->map(fn($p) => $p->toArray())
                ->toArray();
        });
    }

    /**
     * Verifica si un nivel puede ver un módulo
     */
    public static function puedeVer(int $nivel, string $modulo): bool
    {
        $permisos = self::getPermisosPara($nivel);

        if (!isset($permisos[$modulo])) {
            return $nivel === 1; // Si no está definido, solo Admin
        }

        return (bool) $permisos[$modulo]['puede_ver'];
    }

    /**
     * Invalida la caché de permisos de un nivel
     */
    public static function clearCache(int $nivel): void
    {
        Cache::forget("permisos_rol_{$nivel}");
    }

    /**
     * Lista de módulos del sistema (incluye submenús)
     */
    public static function MODULOS(): array
    {
        return [
            'entrevistas'                  => 'Entrevistas',
            'adjuntos'                     => 'Adjuntos',
            'personas'                     => 'Personas',
            'buscador'                     => 'Buscadora',
            'estadisticas'                 => 'Estadisticas',
            'mapa'                         => 'Mapa',
            'exportar'                     => 'Exportar Excel',
            'procesamientos'               => 'Procesamientos',
            'procesamientos.transcripcion' => 'Transcripcion',
            'procesamientos.edicion'       => 'Edicion',
            'procesamientos.entidades'     => 'Entidades',
            'procesamientos.anonimizacion' => 'Anonimizacion',
            'permisos'                     => 'Permisos',
            'usuarios'                     => 'Usuarios',
            'catalogos'                    => 'Catalogos',
            'traza'                        => 'Traza de Actividad',
            'roles'                        => 'Roles',
        ];
    }

    /**
     * Estructura jerárquica: padre => [hijos]
     */
    public static function SUBMODULOS(): array
    {
        return [
            'procesamientos' => [
                'procesamientos.transcripcion',
                'procesamientos.edicion',
                'procesamientos.entidades',
                'procesamientos.anonimizacion',
            ],
        ];
    }

    /**
     * Retorna el padre de un submódulo, o null si es módulo raíz
     */
    public static function getPadre(string $modulo): ?string
    {
        foreach (self::SUBMODULOS() as $padre => $hijos) {
            if (in_array($modulo, $hijos)) {
                return $padre;
            }
        }
        return null;
    }
}
