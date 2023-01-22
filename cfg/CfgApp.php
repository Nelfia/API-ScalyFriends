<?php

declare(strict_types=1);

namespace cfg;

use peps\core\Cfg;

/**
 * Classe 100% statique de configuration générale de l'application.
 * DEVRAIT être étendue par une sous-classe par serveur.
 * 
 * @see Cfg
 */
class CfgApp extends Cfg {

    /**
     * Constructeur privé
     */
    private function __construct() {}

    /**
     * Initialise la configuration de l'application.
     * PROTECTED parce que sous-classes présentes.
     *
     * @return void
     */
    protected static function init() : void {
        // Initialiser la configuration de la classe parente.
        parent::init();

        // Titre de l'application.
        self::register('appTitle', "API ScalyFriends");

        // Locale.
        self::register('appLocale', 'fr-FR');

        // Devise.
        self::register('appCurrency', 'EUR');
    }
}