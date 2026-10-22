<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Auth;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Con APP_DEBUG=false, solo el administrador (nivel 1) ve el detalle del error;
     * el resto ve la página genérica. El log registra todo igual.
     */
    public function render($request, Throwable $e)
    {
        if (!config('app.debug') && $this->esAdministrador()) {
            config(['app.debug' => true]);
        }

        return parent::render($request, $e);
    }

    private function esAdministrador(): bool
    {
        // Si el error vino de la BD o de la sesión, consultar el usuario puede volver a fallar
        try {
            $user = Auth::user();
            return $user && $user->id_nivel == 1;
        } catch (Throwable $e) {
            return false;
        }
    }
}
