<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RolModuloPermiso;

class CheckNivelAcceso
{
    /**
     * Niveles de acceso:
     * 1 = Administrador
     * 2 = Líder
     * 3 = Entrevistador
     * 4 = Transcriptor
     * 5 = Gestor de Conocimiento
     * 99 = Deshabilitado
     * >= 10 = Roles personalizados
     */

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
            flash('No tiene permisos para acceder a este modulo.')->error();
            return redirect()->route('home');
        }

        return $next($request);
    }

    /**
     * Verificar si un nivel tiene permiso para un módulo (DB-driven)
     */
    protected function tienePermiso($modulo, $nivel)
    {
        return RolModuloPermiso::puedeVer((int) $nivel, $modulo);
    }

    /**
     * Método estático para usar en vistas y controladores
     */
    public static function puedeAcceder($modulo, $nivel = null)
    {
        $nivel = $nivel ?? (Auth::user()->id_nivel ?? 99);
        return RolModuloPermiso::puedeVer((int) $nivel, $modulo);
    }
}
