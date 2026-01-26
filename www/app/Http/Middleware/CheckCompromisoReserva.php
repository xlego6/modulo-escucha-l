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

        // Administradores y nivel Esclarecimiento tienen acceso directo
        if ($user->id_nivel <= 2) {
            return $next($request);
        }

        // Verificar si el usuario tiene entrevistador y compromiso aceptado
        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();

        if (!$entrevistador) {
            flash('No tiene un perfil de entrevistador asignado. Contacte al administrador.')->warning();
            return redirect()->route('home');
        }

        if (!$entrevistador->compromiso_reserva) {
            flash('Debe aceptar el compromiso de reserva y confidencialidad para acceder a las entrevistas.')->warning();
            return redirect()->route('perfil');
        }

        return $next($request);
    }
}
