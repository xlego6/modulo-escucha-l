<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirmaCompromiso extends Model
{
    protected $table = 'esclarecimiento.compromiso_firma';
    protected $primaryKey = 'id';

    protected $casts = [
        'fecha_firma' => 'datetime',
    ];

    protected $fillable = [
        'id_entrevistador',
        'tipo',
        'version_texto',
        'fecha_firma',
        'texto_firmado',
    ];

    public function rel_entrevistador()
    {
        return $this->belongsTo(Entrevistador::class, 'id_entrevistador', 'id_entrevistador');
    }

    /**
     * Obtener la firma más reciente de un entrevistador para un tipo de compromiso
     */
    public static function ultimaFirma(int $idEntrevistador, string $tipo): ?self
    {
        return self::where('id_entrevistador', $idEntrevistador)
            ->where('tipo', $tipo)
            ->orderBy('fecha_firma', 'desc')
            ->first();
    }
}
