<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se intentó modificar o borrar una entrada del libro de movimientos. No es
 * un error recuperable: si esto ocurre, hay código nuevo que está violando
 * la garantía de inmutabilidad (RF-50) y hay que corregirlo, no capturarlo.
 */
class BitacoraInmutableException extends RuntimeException {}
