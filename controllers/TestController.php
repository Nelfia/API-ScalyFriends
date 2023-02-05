<?php

declare(strict_types=1);

namespace controllers;

use DateTime;
use entities\Command;
use peps\core\Router;
use peps\jwt\JWT;
use entities\User;
use Error;
use Exception;

/**
 * Classe 100% statique de gestion des commandes.
 */
final class TestController {
    /**
     * Constructeur privé
     */
    private function __construct() {}

    /**
     * Fonction de tests.
     * @return void
     */
    public static function test() : void {
        // Vérifier si User logué.
        Router::json(json_encode("Hello Test!"));
    }

}