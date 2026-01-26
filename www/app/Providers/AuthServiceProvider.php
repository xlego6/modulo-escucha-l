<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot()
    {
        $this->registerPolicies();

        Gate::define('admin', function ($user) {
            return $user->id_nivel == 1;
        });

        Gate::define('entrevistador', function ($user) {
            return in_array($user->id_nivel, [1, 2, 3, 4, 5]);
        });
    }
}
