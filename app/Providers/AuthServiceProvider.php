<?php

namespace App\Providers;

use App\Resources\Oauth\TokenGuard;
use App\Resources\OauthResources;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        $this->registerGuard();
    }

    protected function registerGuard()
    {
        Auth::extend('oauth', function ($app, $name, array $config) {

            $guard = new TokenGuard(
                $this->app['request'],
                $this->app->make(OauthResources::class)
            );
            $this->app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }
}
