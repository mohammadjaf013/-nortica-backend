<?php

namespace App\Providers;

use App\Libs\Socialite\SocialiteDivarProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class DivarProvider extends ServiceProvider
{
    public function boot()
    {
        $socialite = $this->app->make(Factory::class);
        $socialite->extend('divar', function ($app) use ($socialite) {
            $config = $app['config']['services.divar'];
            return $socialite->buildProvider(SocialiteDivarProvider::class, $config);
        });
    }
}
