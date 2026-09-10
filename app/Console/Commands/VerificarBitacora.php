<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

/**
 * Recorre el libro de movimientos y recalcula la cadena de hash (RF-50).
 *
 * Esto es lo que convierte "inmutable" en una afirmación demostrable en vez
 * de una promesa: la aplicación puede impedir que alguien edite una entrada
 * desde el sistema, pero no que un administrador de base de datos la toque
 * por debajo. Lo que sí puede es dejar en evidencia que ocurrió.
 */
class VerificarBitacora extends Command
{
    protected $signature = 'bitacora:verificar {--desde=1 : Id desde el cual verificar}';

    protected $description = 'Verifica la cadena de integridad del libro de movimientos';

    public function handle(): int
    {
        $desde = (int) $this->option('desde');
        $total = ActivityLog::where('id', '>=', $desde)->count();

        if ($total === 0) {
            $this->info('No hay movimientos que verificar.');

            return self::SUCCESS;
        }

        $this->info("Verificando {$total} movimientos...");

        $barra = $this->output->createProgressBar($total);
        $rotas = [];
        $anterior = ActivityLog::where('id', '<', $desde)->orderByDesc('id')->value('hash');

        ActivityLog::where('id', '>=', $desde)->orderBy('id')->chunk(500, function ($movimientos) use (&$rotas, &$anterior, $barra) {
            foreach ($movimientos as $movimiento) {
                $problema = match (true) {
                    // Entradas anteriores a que existiera la cadena: no se
                    // pueden verificar, pero tampoco son un hallazgo.
                    $movimiento->hash === null => null,
                    $movimiento->hash_previo !== $anterior => 'la cadena no engancha con el movimiento anterior',
                    $movimiento->calcularHash() !== $movimiento->hash => 'el contenido no coincide con su hash',
                    default => null,
                };

                if ($problema) {
                    $rotas[] = ['id' => $movimiento->id, 'fecha' => (string) $movimiento->created_at, 'problema' => $problema];
                }

                $anterior = $movimiento->hash;
                $barra->advance();
            }
        });

        $barra->finish();
        $this->newLine(2);

        if ($rotas === []) {
            $this->info("Cadena íntegra: los {$total} movimientos verificados coinciden con su huella.");

            return self::SUCCESS;
        }

        $this->error(count($rotas).' movimiento(s) con la cadena rota:');
        $this->table(['Id', 'Fecha', 'Problema'], $rotas);
        $this->warn('Una entrada alterada rompe también todas las posteriores: el primer id de la lista es el punto donde empezó.');

        return self::FAILURE;
    }
}
