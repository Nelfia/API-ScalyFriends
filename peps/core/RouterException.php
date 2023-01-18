<?php

declare(strict_types=1);

namespace peps\core;

use Exception;

/**
 * Exceptions en lien avec Router.
 * Classe 100% statique.
 * @see Router
 */
final class RouterException extends Exception {
    // Messages d'erreurs.
    public const JSON_ROUTES_FILE_UNAVAILABLE = "JSON routes file unavailable.";
    public const JSON_ROUTES_FILE_CORRUPTED = "JSON routes file corrupted.";
    public const WRONG_PARAMS_ARRAY = "Wrong params array.";
    public const CONTROLLER_METHOD_FAILED = "Controller methode failed.";
    public const PARAMS_ARRAY_CONTAINS_INVALID_KEY = "The params array contains prohibited keys: 'view' or 'assocParams'.";
    public const VIEW_NOT_FOUND = "View not found.";
    public const UNREACHABLE_FILE = "Unreachable file.";
}