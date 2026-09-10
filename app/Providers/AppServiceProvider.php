<?php

namespace App\Providers;

use App\Listeners\RegistrarEventosDeSesion;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Entradas y salidas del sistema al libro de movimientos (RF-49): sin
        // esto no se puede contestar "¿quién estaba dentro cuando pasó esto?".
        Event::listen(Login::class, [RegistrarEventosDeSesion::class, 'handleLogin']);
        Event::listen(Logout::class, [RegistrarEventosDeSesion::class, 'handleLogout']);
        Event::listen(Failed::class, [RegistrarEventosDeSesion::class, 'handleFailed']);
        Event::listen(Lockout::class, [RegistrarEventosDeSesion::class, 'handleLockout']);
    }
}
