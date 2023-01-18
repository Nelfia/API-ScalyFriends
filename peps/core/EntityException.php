<?php

declare(strict_types=1);

namespace peps\core;

use Exception;

/**
 * Exceptions en lien avec Entity.
 * Classe 100% statique.
 * @see Entity
 */
final class EntityException extends Exception {
    // Messages d'erreurs.
    public const WRONG_USAGE_OF_ENTITY_CLASS_ITSELF = "Wrong usage of the Entity Class itself.";
}