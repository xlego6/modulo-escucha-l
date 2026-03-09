<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'esclarecimiento.permiso';
    protected $primaryKey = 'id_permiso';

    protected $fillable = [
        'id_entrevistador',
        'id_e_ind_fvt',
        'id_tipo',
        'fecha_otorgado',
        'fecha_vencimiento',
        'justificacion',
        'id_otorgado_por',
        'fecha_desde',
        'fecha_hasta',
        'id_adjunto',
        'id_estado',
        'id_revocado_por',
        'fecha_revocado',
        'codigo_entrevista',
        'es_solicitud',
        'tipo_solicitud',
        'estado_solicitud',
        'fecha_solicitud',
        'fecha_respuesta',
        'id_respondido_por',
    ];

    protected $casts = [
        'fecha_otorgado' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'fecha_revocado' => 'datetime',
        'fecha_solicitud' => 'datetime',
        'fecha_respuesta' => 'datetime',
        'es_solicitud' => 'boolean',
    ];

    // Estados de permiso
    const ESTADO_VIGENTE = 1;
    const ESTADO_REVOCADO = 2;

    // Tipos de permiso
    const TIPO_LECTURA = 1;
    const TIPO_ESCRITURA = 2;
    const TIPO_COMPLETO = 3;

    // Tipos de solicitud
    const SOLICITUD_ACCESO = 'acceso';
    const SOLICITUD_EDICION = 'edicion';
    const SOLICITUD_ELIMINACION = 'eliminacion';

    // Estados de solicitud
    const SOLICITUD_PENDIENTE = 'pendiente';
    const SOLICITUD_APROBADA = 'aprobado';
    const SOLICITUD_RECHAZADA = 'rechazado';

    public function rel_respondido_por()
    {
        return $this->belongsTo(\App\User::class, 'id_respondido_por', 'id');
    }

    public function rel_entrevistador()
    {
        return $this->belongsTo(Entrevistador::class, 'id_entrevistador', 'id_entrevistador');
    }

    public function rel_entrevista()
    {
        return $this->belongsTo(Entrevista::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    public function rel_otorgado_por()
    {
        return $this->belongsTo(Entrevistador::class, 'id_otorgado_por', 'id_entrevistador');
    }

    public function rel_revocado_por()
    {
        return $this->belongsTo(Entrevistador::class, 'id_revocado_por', 'id_entrevistador');
    }

    public function rel_adjunto()
    {
        return $this->belongsTo(Adjunto::class, 'id_adjunto', 'id_adjunto');
    }

    public function rel_tipo()
    {
        return $this->belongsTo(CriterioFijo::class, 'id_tipo', 'id_opcion');
    }

    /**
     * Verifica si el permiso está vigente considerando estado y fechas
     */
    public function getEstaVigenteAttribute()
    {
        // Si está revocado, no está vigente
        if ($this->id_estado == self::ESTADO_REVOCADO) {
            return false;
        }

        $hoy = now()->startOfDay();

        // Verificar fecha_vencimiento (campo original)
        if ($this->fecha_vencimiento && $this->fecha_vencimiento < $hoy) {
            return false;
        }

        // Verificar rango de fechas (campos nuevos para desclasificación)
        if ($this->fecha_desde && $this->fecha_desde > $hoy) {
            return false;
        }

        if ($this->fecha_hasta && $this->fecha_hasta < $hoy) {
            return false;
        }

        return true;
    }

    /**
     * Verifica si el permiso está dentro del rango de fechas de desclasificación
     */
    public function getEnRangoFechasAttribute()
    {
        if (!$this->fecha_desde && !$this->fecha_hasta) {
            return true;
        }

        $hoy = now()->startOfDay();

        if ($this->fecha_desde && $this->fecha_desde > $hoy) {
            return false;
        }

        if ($this->fecha_hasta && $this->fecha_hasta < $hoy) {
            return false;
        }

        return true;
    }

    public function getFmtTipoAttribute()
    {
        $tipos = [
            1 => 'Lectura',
            2 => 'Escritura',
            3 => 'Completo',
        ];
        return $tipos[$this->id_tipo] ?? 'Lectura';
    }

    public function getFmtEstadoAttribute()
    {
        return $this->id_estado == self::ESTADO_REVOCADO ? 'Revocado' : 'Vigente';
    }

    public function getFmtFechaDesdeAttribute()
    {
        return $this->fecha_desde ? $this->fecha_desde->format('d/m/Y') : null;
    }

    public function getFmtFechaHastaAttribute()
    {
        return $this->fecha_hasta ? $this->fecha_hasta->format('d/m/Y') : null;
    }

    public function getFmtRangoFechasAttribute()
    {
        if (!$this->fecha_desde && !$this->fecha_hasta) {
            return 'Sin límite';
        }

        $desde = $this->fecha_desde ? $this->fecha_desde->format('d/m/Y') : 'Inicio';
        $hasta = $this->fecha_hasta ? $this->fecha_hasta->format('d/m/Y') : 'Sin límite';

        return "{$desde} - {$hasta}";
    }

    /**
     * Scope para filtrar permisos vigentes
     */
    public function scopeVigentes($query)
    {
        return $query->where('id_estado', self::ESTADO_VIGENTE)
            ->where(function ($q) {
                // Not a solicitud, or solicitud that's been approved
                $q->where('es_solicitud', false)
                  ->orWhere(function($q2) {
                      $q2->where('es_solicitud', true)
                         ->where('estado_solicitud', self::SOLICITUD_APROBADA);
                  });
            })
            ->where(function ($q) {
                $q->whereNull('fecha_vencimiento')
                  ->orWhere('fecha_vencimiento', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('fecha_desde')
                  ->orWhere('fecha_desde', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('fecha_hasta')
                  ->orWhere('fecha_hasta', '>=', now());
            });
    }

    /**
     * Scope para filtrar permisos revocados
     */
    public function scopeRevocados($query)
    {
        return $query->where('id_estado', self::ESTADO_REVOCADO);
    }

    /**
     * Scope para filtrar por entrevistador
     */
    public function scopePorEntrevistador($query, $idEntrevistador)
    {
        return $query->where('id_entrevistador', $idEntrevistador);
    }

    /**
     * Scope para filtrar por entrevista
     */
    public function scopePorEntrevista($query, $idEntrevista)
    {
        return $query->where('id_e_ind_fvt', $idEntrevista);
    }

    /**
     * Scope para filtrar por código de entrevista
     */
    public function scopePorCodigo($query, $codigo)
    {
        return $query->where('codigo_entrevista', 'ILIKE', "%{$codigo}%");
    }

    /**
     * Buscar entrevista por código
     */
    public static function buscarEntrevistaPorCodigo($codigo)
    {
        return Entrevista::where('entrevista_codigo', $codigo)
            ->where('id_activo', 1)
            ->first();
    }

    /**
     * Revocar el permiso
     */
    public function revocar($idRevocadoPor)
    {
        $this->id_estado = self::ESTADO_REVOCADO;
        $this->id_revocado_por = $idRevocadoPor;
        $this->fecha_revocado = now();
        $this->save();

        return $this;
    }

    /**
     * Scope para solicitudes pendientes de aprobación
     */
    public function scopeSolicitudesPendientes($query)
    {
        return $query->where('es_solicitud', true)->where('estado_solicitud', self::SOLICITUD_PENDIENTE);
    }

    /**
     * Accessor para estado de solicitud legible
     */
    public function getFmtEstadoSolicitudAttribute()
    {
        return match($this->estado_solicitud) {
            self::SOLICITUD_PENDIENTE => 'Pendiente',
            self::SOLICITUD_APROBADA => 'Aprobada',
            self::SOLICITUD_RECHAZADA => 'Rechazada',
            default => '-',
        };
    }

    /**
     * Accessor para tipo de solicitud legible
     */
    public function getFmtTipoSolicitudAttribute()
    {
        return match($this->tipo_solicitud) {
            self::SOLICITUD_ACCESO => 'Acceso',
            self::SOLICITUD_EDICION => 'Edición',
            self::SOLICITUD_ELIMINACION => 'Eliminación',
            default => '-',
        };
    }
}
