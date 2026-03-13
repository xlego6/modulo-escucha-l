<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Entrevistador;
use Illuminate\Support\Facades\Auth;

class CheckCompromisoReserva
{
    /**
     * Verifica que el usuario haya aceptado el compromiso de reserva
     * antes de permitir acceso a las entrevistas
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Solo administradores tienen acceso directo sin compromiso
        if ($user->id_nivel == 1) {
            return $next($request);
        }

        // Verificar si el usuario tiene entrevistador y compromiso aceptado
        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();

        if (!$entrevistador) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'No tiene un perfil asignado. Contacte al administrador.'], 403);
            }
            flash('No tiene un perfil de entrevistador asignado. Contacte al administrador.')->warning();
            return redirect()->route('home');
        }

        // El compromiso de reserva solo aplica para Líder (2) y Transcriptor (4)
        if (in_array($user->id_nivel, [2, 4]) && !$entrevistador->compromiso_reserva) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Debe aceptar el compromiso de reserva antes de continuar.', 'redirect' => route('perfil')], 403);
            }
            flash('Debe aceptar el compromiso de reserva y confidencialidad para acceder a las entrevistas.')->warning();
            return redirect()->route('perfil');
        }

        if (!$entrevistador->compromiso_acceso) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Debe aceptar el compromiso de acceso interno antes de continuar.', 'redirect' => route('perfil')], 403);
            }
            flash('Debe aceptar el compromiso de acceso interno para continuar.')->warning();
            return redirect()->route('perfil');
        }

        return $next($request);
    }
}
