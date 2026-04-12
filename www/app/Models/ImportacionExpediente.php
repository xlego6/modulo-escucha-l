<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportacionExpediente extends Model
{
    protected $table = 'esclarecimiento.importacion_expedientes';
    protected $primaryKey = 'id_imp_expediente';

    protected $fillable = [
        'id_importacion',
        'id_csv',
        'id_e_ind_fvt',
        'estado',
        'datos_csv',
        'filas_originales',
        'archivos',
        'advertencias',
        'error_mensaje',
    ];

    protected $casts = [
        'datos_csv'       => 'array',
        'filas_originales' => 'array',
        'archivos'        => 'array',
        'advertencias'    => 'array',
    ];

    const ESTADO_PENDIENTE   = 'pendiente';
    const ESTADO_PROCESANDO  = 'procesando';
    const ESTADO_COMPLETADO  = 'completado';
    const ESTADO_ERROR       = 'error';

    public function rel_importacion()
    {
        return $this->belongsTo(ImportacionMasiva::class, 'id_importacion', 'id_importacion');
    }

    public function rel_entrevista()
    {
        return $this->belongsTo(Entrevista::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }
}
