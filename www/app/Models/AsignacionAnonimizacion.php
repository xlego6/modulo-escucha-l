<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class AsignacionAnonimizacion extends Model
{
    protected $table = 'esclarecimiento.asignacion_anonimizacion';
    protected $primaryKey = 'id_asignacion';

    protected $fillable = [
        'id_e_ind_fvt',
        'id_anonimizador',
        'id_asignado_por',
        'estado',
        'fecha_asignacion',
        'fecha_inicio_edicion',
        'fecha_envio_revision',
        'fecha_revision',
        'id_revisor',
        'comentario_revision',
        'tipos_anonimizar',
        'formato_reemplazo',
        'texto_anonimizado',
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'fecha_inicio_edicion' => 'datetime',
        'fecha_envio_revision' => 'datetime',
        'fecha_revision' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Estados posibles
    const ESTADO_ASIGNADA = 'asignada';
    const ESTADO_EN_EDICION = 'en_edicion';
    const ESTADO_ENVIADA_REVISION = 'enviada_revision';
    const ESTADO_APROBADA = 'aprobada';
    const ESTADO_RECHAZADA = 'rechazada';

    public static function estados()
    {
        return [
            self::ESTADO_ASIGNADA => 'Asignada',
            self::ESTADO_EN_EDICION => 'En Edicion',
            self::ESTADO_ENVIADA_REVISION => 'Enviada a Revision',
            self::ESTADO_APROBADA => 'Aprobada',
            self::ESTADO_RECHAZADA => 'Rechazada',
        ];
    }

    // Relaciones
    public function rel_entrevista()
    {
        return $this->belongsTo(Entrevista::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    public function rel_anonimizador()
    {
        return $this->belongsTo(Entrevistador::class, 'id_anonimizador', 'id_entrevistador');
    }

    public function rel_asignado_por()
    {
        return $this->belongsTo(User::class, 'id_asignado_por', 'id');
    }

    public function rel_revisor()
    {
        return $this->belongsTo(User::class, 'id_revisor', 'id');
    }

    // Accessors
    public function getFmtEstadoAttribute()
    {
        $estados = self::estados();
        return $estados[$this->estado] ?? $this->estado;
    }

    public function getEstadoBadgeClassAttribute()
    {
        $clases = [
            self::ESTADO_ASIGNADA => 'badge-secondary',
            self::ESTADO_EN_EDICION => 'badge-primary',
            self::ESTADO_ENVIADA_REVISION => 'badge-warning',
            self::ESTADO_APROBADA => 'badge-success',
            self::ESTADO_RECHAZADA => 'badge-danger',
        ];
        return $clases[$this->estado] ?? 'badge-secondary';
    }

    public function getTiposArrayAttribute()
    {
        return explode(',', $this->tipos_anonimizar ?? 'PER,LOC');
    }

    // Scopes
    public function scopePendientesRevision($query)
    {
        return $query->where('estado', self::ESTADO_ENVIADA_REVISION);
    }

    public function scopeDeAnonimizador($query, $idAnonimizador)
    {
        return $query->where('id_anonimizador', $idAnonimizador);
    }

    public function scopeActivas($query)
    {
        return $query->whereIn('estado', [
            self::ESTADO_ASIGNADA,
            self::ESTADO_EN_EDICION,
            self::ESTADO_ENVIADA_REVISION,
            self::ESTADO_RECHAZADA,
        ]);
    }
}
