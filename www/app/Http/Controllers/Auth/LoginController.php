<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Entrevistador;
use App\Models\TrazaActividad;
use App\Services\LdapService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    protected LdapService $ldapService;

    public function __construct(LdapService $ldapService)
    {
        $this->ldapService = $ldapService;
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = strtolower(trim($validated['email']));
        $password = $validated['password'];
        $remember = $request->boolean('remember');

        $usuario = User::where('email', $email)->first();
        $esCorreoCorporativo = $this->isCorporateEmail($email);

        /**
         * CASO A:
         * Usuario existe y está marcado para login por Directorio Activo
         */
        if ($usuario && (bool) $usuario->is_login_directory_active) {
            if (!$this->checkLdapAvailability()) {
                return $this->ldapUnavailableResponse('El inicio de sesión con Directorio Activo no está disponible en este momento.');
            }

            if (!$this->ldapService->validateUser($email, $password)) {
                return $this->invalidCredentialsResponse();
            }

            Auth::login($usuario, $remember);
            return $this->finalizeAuthenticatedLogin($request);
        }

        /**
         * CASO B:
         * Usuario NO existe y el correo es corporativo -> intentamos LDAP y creamos usuario local
         */
        if (!$usuario && $esCorreoCorporativo) {
            if (!$this->checkLdapAvailability()) {
                return $this->ldapUnavailableResponse('No se pudo validar el usuario corporativo porque LDAP no está disponible.');
            }

            $ldapUser = $this->ldapService->getUserLdap($email, $password);

            if (!$ldapUser) {
                return $this->invalidCredentialsResponse();
            }

            $nuevoUsuario = User::create([
                'name' => $ldapUser['display_name'] ?? ($ldapUser['name'] ?? $email),
                'email' => $ldapUser['email'] ?? $email,
                'password' => Hash::make(bin2hex(random_bytes(16))), // password local aleatorio
                'is_login_directory_active' => true,
            ]);

            Auth::login($nuevoUsuario, $remember);
            return $this->finalizeAuthenticatedLogin($request);
        }

        /**
         * CASO C:
         * Login normal (base de datos)
         */
        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            return $this->finalizeAuthenticatedLogin($request);
        }

        return $this->invalidCredentialsResponse();
    }

    /**
     * Finaliza el login:
     * - crea perfil automáticamente si no existe
     * - valida deshabilitado
     * - regenera sesión
     */
    protected function finalizeAuthenticatedLogin(Request $request)
    {
        $user = Auth::user();

        // Si usuario deshabilitado
        if ((int) $user->id_nivel === 99) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Usuario deshabilitado.',
            ])->onlyInput('email');
        }

        // Si no tiene perfil, se crea automáticamente con nivel Entrevistador
        if (!$user->tiene_perfil()) {
            $numero = (Entrevistador::max('numero_entrevistador') ?? 0) + 1;

            Entrevistador::create([
                'id_usuario'           => $user->id,
                'numero_entrevistador' => $numero,
                'id_nivel'             => 3,
                'solo_lectura'         => 0,
            ]);

            TrazaActividad::create([
                'fecha_hora'  => now(),
                'id_usuario'  => $user->id,
                'accion'      => 'crear_perfil_automatico',
                'objeto'      => 'entrevistador',
                'id_registro' => $user->id,
                'referencia'  => 'Perfil creado automáticamente en primer acceso (directorio activo)',
                'ip'          => $request->ip(),
            ]);
        }

        $request->session()->regenerate();
        return redirect()->intended('/home');
    }

    /**
     * Disponibilidad LDAP (flag + extensión PHP)
     */
    protected function checkLdapAvailability(): bool
    {
        if (!$this->ldapService->isEnabled()) {
            return false;
        }

        if (!$this->ldapService->isExtensionInstalled()) {
            return false;
        }

        return true;
    }

    protected function isCorporateEmail(string $email): bool
    {
        return str_ends_with(strtolower(trim($email)), '@cnmh.gov.co');
    }

    protected function invalidCredentialsResponse()
    {
        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    protected function ldapUnavailableResponse(string $message)
    {
        return back()->withErrors([
            'email' => $message,
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