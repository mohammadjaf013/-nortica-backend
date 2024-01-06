<?php

namespace App\Providers;
use App\Libs\Socialite\SocialiteDivarProvider;
use Laravel\Socialite\Contracts\Factory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $socialite = $this->app->make(Factory::class);

        $socialite->extend('divar', function () use ($socialite) {
            $config = config('services.divar');
            return $socialite->buildProvider(SocialiteDivarProvider::class, $config);
        });


//        if ($this->app->environment('local')) {
//            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
//            $this->app->register(TelescopeServiceProvider::class);
//        }
    }
}
