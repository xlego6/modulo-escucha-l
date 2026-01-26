<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TrazaActividad extends Model
{
    protected $table = 'traza_actividad';
    protected $primaryKey = 'id_traza_actividad';
    public $timestamps = false;

    protected $fillable = [
        'fecha_hora',
        'id_usuario',
        'accion',
        'objeto',
        'id_registro',
        'referencia',
        'codigo',
        'ip',
        'id_personificador',
    ];

    public function rel_usuario() {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function rel_personificador() {
        return $this->belongsTo(User::class, 'id_personificador', 'id');
    }

    /**
     * Registrar una actividad en la traza
     */
    public static function registrar($accion, $objeto = null, $id_registro = null, $codigo = null, $referencia = null) {
        $traza = new self();
        $traza->fecha_hora = now();
        $traza->id_usuario = Auth::id();
        $traza->accion = $accion;
        $traza->objeto = $objeto;
        $traza->id_registro = $id_registro;
        $traza->codigo = $codigo;
        $traza->referencia = $referencia;
        $traza->ip = request()->ip();
        $traza->save();

        return $traza;
    }

    public function getFmtFechaHoraAttribute() {
        return $this->fecha_hora ? date('d/m/Y H:i:s', strtotime($this->fecha_hora)) : '';
    }

    public function getFmtAccionAttribute() {
        $acciones = [
            'crear' => 'Crear',
            'editar' => 'Editar',
            'eliminar' => 'Eliminar',
            'ver' => 'Ver',
            'descargar' => 'Descargar',
            'subir' => 'Subir',
            'login' => 'Iniciar sesión',
            'logout' => 'Cerrar sesión',
            'exportar' => 'Exportar',
            'buscar' => 'Buscar',
            'subir_adjunto' => 'Subir adjunto',
            'descargar_adjunto' => 'Descargar adjunto',
            'eliminar_adjunto' => 'Eliminar adjunto',
            'exportar_entrevistas' => 'Exportar entrevistas',
            'exportar_personas' => 'Exportar personas',
        ];

        return $acciones[$this->accion] ?? ucfirst(str_replace('_', ' ', $this->accion ?? ''));
    }

    public function getFmtObjetoAttribute() {
        $objetos = [
            'entrevista' => 'Entrevista',
            'e_ind_fvt' => 'Entrevista',
            'persona' => 'Persona',
            'adjunto' => 'Adjunto',
            'usuario' => 'Usuario',
            'permiso' => 'Permiso',
            'catalogo' => 'Catálogo',
            'item_catalogo' => 'Item de catálogo',
        ];

        return $objetos[$this->objeto] ?? ucfirst(str_replace('_', ' ', $this->objeto ?? ''));
    }

    /**
     * Obtener badge class según la acción
     */
    public function getBadgeClassAttribute() {
        $clases = [
            'crear' => 'success',
            'editar' => 'warning',
            'eliminar' => 'danger',
            'ver' => 'info',
            'descargar' => 'primary',
            'subir' => 'success',
            'login' => 'info',
            'logout' => 'secondary',
            'exportar' => 'primary',
            'subir_adjunto' => 'success',
            'descargar_adjunto' => 'primary',
            'eliminar_adjunto' => 'danger',
            'exportar_entrevistas' => 'primary',
            'exportar_personas' => 'primary',
        ];

        return $clases[$this->accion] ?? 'secondary';
    }
}
