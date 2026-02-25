<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\LdapService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    protected $ldapService;

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

        if ($usuario && $usuario->is_login_directory_active) {
            if (!$this->ldapService->isEnabled()) {
                return $this->ldapUnavailableResponse('El inicio de sesión con Directorio Activo está deshabilitado en la configuración.');
            }

            if (!$this->ldapService->isExtensionInstalled()) {
                return $this->ldapUnavailableResponse('La extensión LDAP de PHP no está instalada o habilitada en el servidor.');
            }

            if (!$this->validateUserLogin($email, $password)) {
                return $this->invalidCredentialsResponse();
            }

            Auth::login($usuario, $remember);
            return $this->finalizeAuthenticatedLogin($request);
        }

        if (!$usuario && $esCorreoCorporativo) {
            if (!$this->ldapService->isEnabled()) {
                return $this->ldapUnavailableResponse('No se pudo validar el usuario corporativo porque LDAP está deshabilitado.');
            }

            if (!$this->ldapService->isExtensionInstalled()) {
                return $this->ldapUnavailableResponse('No se pudo validar el usuario corporativo porque la extensión LDAP de PHP no está instalada.');
            }

            $ldapUser = $this->ldapService->getUserLdap($email, $password);

            if (!$ldapUser) {
                return $this->invalidCredentialsResponse();
            }

            $nuevoUsuario = User::create([
                'name' => $ldapUser['display_name'] ?? $email,
                'email' => $ldapUser['email'] ?? $email,
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'is_login_directory_active' => true,
            ]);

            Auth::login($nuevoUsuario, $remember);
            return $this->finalizeAuthenticatedLogin($request);
        }

        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            return $this->finalizeAuthenticatedLogin($request);
        }

        return $this->invalidCredentialsResponse();
    }

    protected function validateUserLogin(string $email, string $password): bool
    {
        return $this->ldapService->validateUser($email, $password);
    }

    protected function isCorporateEmail(string $email): bool
    {
        return str_ends_with(strtolower(trim($email)), '@cnmh.gov.co');
    }

    protected function finalizeAuthenticatedLogin(Request $request)
    {
        $user = Auth::user();

        if (!$user->tiene_perfil()) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Usuario sin perfil de entrevistador asignado.',
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
