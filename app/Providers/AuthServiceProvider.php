<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        $this->registerPolicies();
        Gate::define('isAdmin', fn($user) => $user->role === 'admin');
        Gate::define('isSeller', fn($user) => $user->role === 'seller');
    }
}
