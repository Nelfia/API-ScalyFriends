<?php

declare(strict_types=1);

namespace peps\core;

use Exception;

/**
 * Exceptions en lien avec Autoload.
 * Classe 100% statique.
 * @see Autoload
 */
final class AutoloadException extends Exception {
    // Messages d'erreurs.
    public const DOCUMENT_ROOT_UNAVAILABLE = "Server variable DOCUMENT_ROOT unavailable";
}