<?php

declare(strict_types=1);

namespace peps\core;

use Error;
use Exception;

/**
 * Classe 100% statique utilitaire.
 */
final class PEPS {
    /**
     * Tableau des requêtes SQL exécutées pour répondre à une requête client.
     * Si mode d'exécution approprié, affiché automatiquement en haut de chaque vue.
     *
     * @var array Tableau.
     */
    private static array $queries = [];

    /**
     * Constructeur privé.
     */
    private function __construct() {}

    /**
     * Ajoute une requête SQL au tableau des requêtes.
     *
     * @param string $q Requête SQL.
     * @return void
     */
    public static function addQuery(string $q, array $params = []) : void {
        self::$queries[] = $q . ($params ? "\n" . print_r($params, true) : '');
    }

    /**
     * Retourne le tableau des requêtes.
     *
     * @return array Tableau des requêtes.
     */
    public static function getQueries() : array {
        return self::$queries;
    }

    public static function e(Error | Exception $e, string $message = null) : void {
        if(Cfg::get('EXECUTION_MODE') !== ExecutionMode::PROD)
            throw $e;
        elseif ($message)
            exit($message);
        else
            exit($e->getMessage());
    }
}