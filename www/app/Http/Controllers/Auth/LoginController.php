<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Entrevistador;
use App\Models\TrazaActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Si no tiene perfil (primer acceso vía directorio activo),
            // se crea automáticamente con nivel Entrevistador
            if (!$user->tiene_perfil()) {
                $numero = (Entrevistador::max('numero_entrevistador') ?? 0) + 1;

                Entrevistador::create([
                    'id_usuario'           => $user->id,
                    'numero_entrevistador' => $numero,
                    'id_nivel'             => 3,
                    'solo_lectura'         => 0,
                ]);

                TrazaActividad::create([
                    'fecha_hora' => now(),
                    'id_usuario' => $user->id,
                    'accion'     => 'crear_perfil_automatico',
                    'objeto'     => 'entrevistador',
                    'id_registro'=> $user->id,
                    'referencia' => 'Perfil creado automáticamente en primer acceso (directorio activo)',
                    'ip'         => $request->ip(),
                ]);
            }

            if ($user->id_nivel == 99) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Usuario deshabilitado.',
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended('/home');
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
