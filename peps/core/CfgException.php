<?php

declare(strict_types=1);

namespace peps\core;

use Exception;

/**
 * Exceptions en lien avec Cfg.
 * Classe 100% statique.
 * @see Cfg
 */
final class CfgException extends Exception {
    // Messages d'erreurs.
    public const UNAVAILABLE_KEY = "Unavalaible key.";
}