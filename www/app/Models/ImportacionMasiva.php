<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportacionMasiva extends Model
{
    protected $table = 'esclarecimiento.importaciones_masivas';
    protected $primaryKey = 'id_importacion';

    protected $fillable = [
        'id_usuario',
        'nombre_archivo',
        'ruta_csv',
        'estado',
        'total_expedientes',
        'procesados',
        'con_error',
        'configuracion',
    ];

    protected $casts = [
        'configuracion' => 'array',
    ];

    // Estados posibles
    const ESTADO_PENDIENTE   = 'pendiente';
    const ESTADO_MAPEANDO    = 'mapeando';
    const ESTADO_CONFIRMADO  = 'confirmado';
    const ESTADO_PROCESANDO  = 'procesando';
    const ESTADO_COMPLETADO  = 'completado';
    const ESTADO_CON_ERRORES = 'con_errores';

    public function rel_expedientes()
    {
        return $this->hasMany(ImportacionExpediente::class, 'id_importacion', 'id_importacion');
    }

    public function rel_usuario()
    {
        return $this->belongsTo(\App\User::class, 'id_usuario', 'id');
    }

    public function getPorcentajeAttribute(): int
    {
        if ($this->total_expedientes == 0) return 0;
        return (int) round(($this->procesados + $this->con_error) / $this->total_expedientes * 100);
    }

    public function getEtiquetaEstadoAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE   => 'Pendiente',
            self::ESTADO_MAPEANDO    => 'Mapeando catálogos',
            self::ESTADO_CONFIRMADO  => 'Listo para procesar',
            self::ESTADO_PROCESANDO  => 'Procesando',
            self::ESTADO_COMPLETADO  => 'Completado',
            self::ESTADO_CON_ERRORES => 'Completado con errores',
            default                  => ucfirst($this->estado),
        };
    }

    public function getClaseEstadoAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_COMPLETADO  => 'success',
            self::ESTADO_PROCESANDO  => 'primary',
            self::ESTADO_CON_ERRORES => 'warning',
            self::ESTADO_MAPEANDO,
            self::ESTADO_CONFIRMADO  => 'info',
            default                  => 'secondary',
        };
    }
}
