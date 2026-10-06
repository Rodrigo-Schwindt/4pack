<?php

namespace App\Providers;

use App\Http\Middleware\TienePermiso;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Las acciones de Livewire tambien pasan por el permiso de la pantalla.
        Livewire::addPersistentMiddleware([TienePermiso::class]);
    }
}