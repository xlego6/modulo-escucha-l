<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckNivelAcceso
{
    /**
     * Niveles de acceso:
     * 1 = Administrador
     * 2 = Líder
     * 3 = Entrevistador
     * 4 = Transcriptor
     * 99 = Deshabilitado
     */

    /**
     * Permisos por módulo según nivel
     */
    protected $permisos = [
        'entrevistas' => [1, 2, 3],      // Admin, Líder, Entrevistador
        'personas' => [1, 3],             // Admin, Entrevistador
        'buscador' => [1, 2, 3],          // Admin, Líder, Entrevistador
        'estadisticas' => [1, 2],         // Admin, Líder
        'mapa' => [1, 2],                 // Admin, Líder
        'exportar' => [1, 2],             // Admin, Líder
        'procesamientos' => [1, 2, 4],    // Admin, Líder, Transcriptor
        'procesamientos.edicion' => [1, 2, 4],  // Admin, Líder, Transcriptor
        'procesamientos.transcripcion' => [1, 2],  // Admin, Líder (iniciar transcripción)
        'procesamientos.entidades' => [1, 2],      // Admin, Líder
        'procesamientos.anonimizacion' => [1, 2, 4],  // Admin, Líder, Transcriptor
        'usuarios' => [1],                // Solo Admin
        'permisos' => [1, 2],             // Admin, Líder
        'desclasificacion' => [1],        // Solo Admin
        'catalogos' => [1, 2],            // Admin, Líder
        'traza' => [1, 2],                // Admin, Líder
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $modulo
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $modulo)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $nivelUsuario = $user->id_nivel;

        // Usuario deshabilitado
        if ($nivelUsuario == 99) {
            Auth::logout();
            flash('Su cuenta ha sido deshabilitada. Contacte al administrador.')->error();
            return redirect()->route('login');
        }

        // Verificar si tiene permiso para el módulo
        if (!$this->tienePermiso($modulo, $nivelUsuario)) {
            flash('No tiene permisos para acceder a este módulo.')->error();
            return redirect()->route('home');
        }

        return $next($request);
    }

    /**
     * Verificar si un nivel tiene permiso para un módulo
     */
    protected function tienePermiso($modulo, $nivel)
    {
        if (!isset($this->permisos[$modulo])) {
            // Si no está definido, solo Admin
            return $nivel == 1;
        }

        return in_array($nivel, $this->permisos[$modulo]);
    }

    /**
     * Método estático para usar en vistas
     */
    public static function puedeAcceder($modulo, $nivel = null)
    {
        $nivel = $nivel ?? Auth::user()->id_nivel ?? 99;

        $permisos = [
            'entrevistas' => [1, 2, 3],
            'personas' => [1, 3],
            'buscador' => [1, 2, 3],
            'estadisticas' => [1, 2],
            'mapa' => [1, 2],
            'exportar' => [1, 2],
            'procesamientos' => [1, 2, 4],
            'procesamientos.edicion' => [1, 2, 4],
            'procesamientos.transcripcion' => [1, 2],
            'procesamientos.entidades' => [1, 2],
            'procesamientos.anonimizacion' => [1, 2, 4],
            'usuarios' => [1],
            'permisos' => [1, 2],
            'desclasificacion' => [1],
            'catalogos' => [1, 2],
            'traza' => [1, 2],
        ];

        if (!isset($permisos[$modulo])) {
            return $nivel == 1;
        }

        return in_array($nivel, $permisos[$modulo]);
    }
}
