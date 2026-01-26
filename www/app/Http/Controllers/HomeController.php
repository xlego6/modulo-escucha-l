<?php

namespace App\Http\Controllers;

use App\Models\Entrevista;
use App\Models\Entrevistador;
use App\Models\Persona;
use App\Models\Adjunto;
use App\Models\TrazaActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $stats = [
            'total_entrevistas' => Entrevista::where('id_activo', 1)->count(),
            'total_personas' => Persona::count(),
            'total_adjuntos' => Adjunto::count(),
            'entrevistas_mes' => Entrevista::where('id_activo', 1)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];

        $ultimas_entrevistas = Entrevista::where('id_activo', 1)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('home.index', compact('stats', 'ultimas_entrevistas'));
    }

    public function perfil()
    {
        $user = Auth::user();
        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();
        return view('home.perfil', compact('user', 'entrevistador'));
    }

    /**
     * Actualizar datos del perfil
     */
    public function actualizarPerfil(Request $request)
    {
        $user = Auth::user();

        // Solo administradores pueden cambiar el correo
        $emailRules = 'required|email|max:255|unique:users,email,' . $user->id;
        if ($user->id_nivel > 2) {
            // Usuario no admin: el email no debe cambiar
            $emailRules = 'required|email|in:' . $user->email;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => $emailRules,
        ], [
            'email.in' => 'No tiene permisos para cambiar el correo electronico.',
        ]);

        $user->name = $request->name;

        // Solo actualizar email si es administrador
        if ($user->id_nivel <= 2) {
            $user->email = $request->email;
        }

        $user->save();

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'editar',
            'objeto' => 'perfil',
            'id_registro' => $user->id,
            'referencia' => 'Actualizacion de datos del perfil',
            'ip' => $request->ip(),
        ]);

        flash('Perfil actualizado correctamente.')->success();
        return redirect()->route('perfil');
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'password_actual' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password_actual.required' => 'Debe ingresar su contraseña actual',
            'password.required' => 'Debe ingresar la nueva contraseña',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        // Verificar contraseña actual
        if (!Hash::check($request->password_actual, $user->password)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual es incorrecta']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'cambiar_password',
            'objeto' => 'usuario',
            'id_registro' => $user->id,
            'referencia' => 'Cambio de contraseña',
            'ip' => $request->ip(),
        ]);

        flash('Contraseña actualizada correctamente.')->success();
        return redirect()->route('perfil');
    }

    /**
     * Aceptar compromiso de reserva
     */
    public function aceptarCompromisoReserva(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'acepto_compromiso' => 'required|accepted',
        ], [
            'acepto_compromiso.accepted' => 'Debe aceptar el compromiso de reserva',
        ]);

        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();

        if ($entrevistador) {
            $entrevistador->compromiso_reserva = now();
            $entrevistador->save();

            TrazaActividad::create([
                'fecha_hora' => now(),
                'id_usuario' => $user->id,
                'accion' => 'aceptar_compromiso',
                'objeto' => 'compromiso_reserva',
                'id_registro' => $entrevistador->id_entrevistador,
                'referencia' => 'Aceptacion del compromiso de reserva',
                'ip' => $request->ip(),
            ]);

            flash('Compromiso de reserva aceptado correctamente.')->success();
        } else {
            flash('No se encontró el perfil de entrevistador.')->error();
        }

        return redirect()->route('perfil');
    }
}
