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
            'reordenar' => 'Reordenar',
            'cambiar_password' => 'Cambiar contraseña',
            'aceptar_compromiso' => 'Aceptar compromiso',
            'crear_perfil_automatico' => 'Crear perfil automático',
            'subir_adjunto' => 'Subir adjunto',
            'descargar_adjunto' => 'Descargar adjunto',
            'eliminar_adjunto' => 'Eliminar adjunto',
            'descargar_formtr' => 'Descargar FormTR',
            'exportar_entrevistas' => 'Exportar entrevistas',
            'exportar_personas' => 'Exportar personas',
            'crear_persona' => 'Crear persona',
            'editar_persona' => 'Editar persona',
            'eliminar_persona' => 'Eliminar persona',
            'crear_usuario' => 'Crear usuario',
            'actualizar_usuario' => 'Actualizar usuario',
            'eliminar_usuario' => 'Eliminar usuario',
            'crear_rol' => 'Crear rol',
            'actualizar_permisos_rol' => 'Actualizar permisos de rol',
            'eliminar_rol' => 'Eliminar rol',
            'otorgar_permiso' => 'Otorgar permiso',
            'revocar_permiso' => 'Revocar permiso',
            'desclasificar' => 'Desclasificar',
            'solicitar_permiso' => 'Solicitar permiso',
            'aprobar_solicitud' => 'Aprobar solicitud',
            'rechazar_solicitud' => 'Rechazar solicitud',
            'iniciar_transcripcion' => 'Iniciar transcripción',
            'editar_transcripcion' => 'Editar transcripción',
            'aprobar_transcripcion' => 'Aprobar transcripción',
            'rechazar_transcripcion' => 'Rechazar transcripción',
            'enviar_revision' => 'Enviar a revisión',
            'asignar_transcripcion' => 'Asignar transcripción',
            'editar_anonimizacion' => 'Editar anonimización',
            'aprobar_anonimizacion' => 'Aprobar anonimización',
            'rechazar_anonimizacion' => 'Rechazar anonimización',
            'asignar_anonimizacion' => 'Asignar anonimización',
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
            'perfil' => 'Perfil',
            'entrevistador' => 'Entrevistador',
            'permiso' => 'Permiso',
            'rol' => 'Rol',
            'catalogo' => 'Catálogo',
            'item_catalogo' => 'Item de catálogo',
            'transcripcion' => 'Transcripción',
            'anonimizacion' => 'Anonimización',
            'compromiso_acceso' => 'Compromiso de acceso',
            'compromiso_reserva' => 'Compromiso de reserva',
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
            'buscar' => 'info',
            'reordenar' => 'secondary',
            'cambiar_password' => 'warning',
            'aceptar_compromiso' => 'success',
            'crear_perfil_automatico' => 'success',
            'subir_adjunto' => 'success',
            'descargar_adjunto' => 'primary',
            'eliminar_adjunto' => 'danger',
            'descargar_formtr' => 'primary',
            'exportar_entrevistas' => 'primary',
            'exportar_personas' => 'primary',
            'crear_persona' => 'success',
            'editar_persona' => 'warning',
            'eliminar_persona' => 'danger',
            'crear_usuario' => 'success',
            'actualizar_usuario' => 'warning',
            'eliminar_usuario' => 'danger',
            'crear_rol' => 'success',
            'actualizar_permisos_rol' => 'warning',
            'eliminar_rol' => 'danger',
            'otorgar_permiso' => 'success',
            'revocar_permiso' => 'danger',
            'desclasificar' => 'warning',
            'solicitar_permiso' => 'info',
            'aprobar_solicitud' => 'success',
            'rechazar_solicitud' => 'danger',
            'iniciar_transcripcion' => 'success',
            'editar_transcripcion' => 'warning',
            'aprobar_transcripcion' => 'success',
            'rechazar_transcripcion' => 'danger',
            'enviar_revision' => 'info',
            'asignar_transcripcion' => 'primary',
            'editar_anonimizacion' => 'warning',
            'aprobar_anonimizacion' => 'success',
            'rechazar_anonimizacion' => 'danger',
            'asignar_anonimizacion' => 'primary',
        ];

        return $clases[$this->accion] ?? 'secondary';
    }
}
