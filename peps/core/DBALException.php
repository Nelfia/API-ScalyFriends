<?php

declare(strict_types=1);

namespace peps\core;

use Exception;

/**
 * Exceptions en lien avec DBAL.
 * Classe 100% statique.
 * 
 * @see DBAL
 */
final class DBALException extends Exception {
    // Messages d'erreurs.
    public const DB_CONNECTION_FAILED = "DB connection failed.";
    public const WRONG_PREPARED_SQL_QUERY = "Wrong prepared SQL query.";
    public const WRONG_SQL_QUERY_PARAMETERS = "Wrong SQL query parameters.";
    public const WRONG_SELECT_SQL_QUERY = "Wrong SELECT SQL query.";
    public const WRONG_NON_SELECT_SQL_QUERY = "Wrong NON SELECT SQL query.";
    public const FETCH_CLASS_UNAVAILABLE = "Fetch class unavailable.";
}