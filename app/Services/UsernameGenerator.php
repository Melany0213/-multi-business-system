<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Genera el nombre de usuario estándar: inicial del primer apellido +
 * inicial del segundo apellido + nombre(s) completo(s), todo normalizado.
 * Ante una colisión, se agregan los últimos 2 dígitos del carnet de
 * identidad; si no hay carnet o vuelve a chocar, se agrega un contador.
 */
class UsernameGenerator
{
    public function generate(string $nombre, string $primerApellido, string $segundoApellido, ?string $carnetIdentidad = null): string
    {
        $base = $this->normalize(
            mb_substr($primerApellido, 0, 1).mb_substr($segundoApellido, 0, 1).$nombre
        );

        if (! $this->existe($base)) {
            return $base;
        }

        if ($carnetIdentidad) {
            $sufijo = substr($carnetIdentidad, -2);
            $candidato = $base.$sufijo;

            if (! $this->existe($candidato)) {
                return $candidato;
            }
        }

        $contador = 2;
        while ($this->existe($base.$contador)) {
            $contador++;
        }

        return $base.$contador;
    }

    protected function existe(string $username): bool
    {
        return User::where('username', $username)->exists();
    }

    protected function normalize(string $value): string
    {
        $value = Str::ascii($value);
        $value = preg_replace('/[^A-Za-z0-9]/', '', $value);

        return strtolower($value);
    }
}
