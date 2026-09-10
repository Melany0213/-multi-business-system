<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Bitacora;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

/**
 * Entradas y salidas del sistema en el libro de movimientos (RF-49).
 *
 * Sin esto no había forma de contestar la primera pregunta de cualquier
 * revisión: "¿quién estaba dentro cuando pasó esto?". Los intentos fallidos
 * importan tanto como los exitosos — tres seguidos sobre el mismo usuario a
 * las 3 de la mañana son un dato, aunque ninguno haya entrado.
 *
 * Estos movimientos no cuelgan de ningún negocio (business_id nulo): pasan a
 * nivel del sistema, antes de que exista un negocio activo.
 */
class RegistrarEventosDeSesion
{
    public function __construct(
        private Bitacora $bitacora,
        private Request $request,
    ) {}

    public function handleLogin(Login $event): void
    {
        $usuario = $event->user;

        $this->bitacora->registrar(
            'sesion.iniciada',
            "Inicio de sesión de \"{$usuario->username}\" desde {$this->origen()}",
            ['sujeto' => $usuario, 'causer_id' => $usuario->getKey(), 'action' => 'created'],
        );
    }

    public function handleLogout(Logout $event): void
    {
        $usuario = $event->user;

        if (! $usuario) {
            return;
        }

        $this->bitacora->registrar(
            'sesion.cerrada',
            "Cierre de sesión de \"{$usuario->username}\"",
            ['sujeto' => $usuario, 'causer_id' => $usuario->getKey()],
        );
    }

    public function handleFailed(Failed $event): void
    {
        // `credentials` trae la contraseña en claro: acá solo se toma el
        // identificador. Registrar el resto convertiría el libro de auditoría
        // en el peor archivo de contraseñas imaginable.
        $intento = $event->credentials['username'] ?? 'desconocido';

        $this->bitacora->registrar(
            'sesion.fallida',
            "Intento de inicio de sesión FALLIDO para \"{$intento}\" desde {$this->origen()}",
            [
                'sujeto' => $event->user instanceof User ? $event->user : null,
                // Nadie llegó a autenticarse: el movimiento no tiene autor,
                // aunque sí tenga un usuario apuntado.
                'causer_id' => $event->user?->getKey(),
            ],
        );
    }

    public function handleLockout(Lockout $event): void
    {
        $this->bitacora->registrar(
            'sesion.bloqueada',
            "Demasiados intentos fallidos seguidos desde {$this->origen()}: acceso bloqueado temporalmente",
            ['causer_id' => null],
        );
    }

    /**
     * IP del intento. No es identidad, pero es lo único que distingue "entró
     * desde el local" de "entró desde otro lado" cuando hay que reconstruir
     * qué pasó.
     */
    private function origen(): string
    {
        return $this->request->ip() ?? 'origen desconocido';
    }
}
