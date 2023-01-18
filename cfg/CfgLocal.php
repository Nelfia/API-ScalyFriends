<?php

declare(strict_types=1);

namespace cfg;

use peps\core\ExecutionMode;

/**
 * Classe 100% statique de configuration de l'application pour le serveur local.
 * FINAL parce que dernier niveau d'héritage.
 * 
 * @see CfgApp
 */
final class CfgLocal extends CfgApp {

    /**
     * Constructeur privé
     */
    private function __construct() {}

    /**
     * Initialise la configuration de l'application.
     * PUBLIC parce que c'est le dernier niveau d'héritage.
     *
     * @return void
     */
    public static function init() : void {
        // Initialiser la configuration de la classe parente.
        parent::init();

        // Mode de fonctionnement.
        self::register('EXECUTION_MODE', ExecutionMode::DEV, true);

        // Driver PDO de la DB.
        self::register('dbDriver', "mysql");

        // Hôte de la DB.
        self::register('dbHost', 'localhost');

        // Port de l'hôte de la DB.
        self::register('dbPort', 3306);

        // Nom de la DB.
        self::register('dbName', 'api-scalyFriends');

        // Identifiant de l'utilisateur de la DB.
        self::register('dbLog', 'root');

        // Mot de passe de la DB.
        self::register('dbPwd', '');

        // Jeu de caractère de la DB.
        self::register('dbCharset', 'utf8mb4');
    }
}