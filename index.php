<?php

declare(strict_types=1);

use cfg\CfgIonos;
use peps\core\Autoload;
use peps\core\AutoloadException;
use peps\core\Cfg;
use peps\core\CfgException;
use peps\core\DBAL;
use peps\core\DBALException;
use peps\core\Router;
use peps\core\RouterException;

require './peps/core/Autoload.php';

// Initialisation de l'autoload (à faire EN PREMIER)
try {
    Autoload::init();
} catch (AutoloadException $e) {
}

// Initialiser la configuration en fonction de l'IP du serveur(à faire EN DEUXIEME).
$serverIP = filter_input(INPUT_SERVER , 'SERVER_ADDR', FILTER_VALIDATE_IP) ?: filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP);
if(!$serverIP) exit ("Server variable SERVER_ADDR unavailable");

// ICI VOS CLASSES DE CONFIGURATION EN FONCTION DES IP DE VOS SERVEURS.
// Antislash initial obligatoire ici.
match ($serverIP) {
    "127.0.0.1", "::1" => CfgIonos::init(),
    default => exit("Cfg class not found for server IP.")
};

// Initialisation de la connexion DB.
try {
    DBAL::init(
        Cfg::get('dbDriver'),
        Cfg::get('dbHost'),
        Cfg::get('dbPort'),
        Cfg::get('dbName'),
        Cfg::get('dbLog'),
        Cfg::get('dbPwd'),
        Cfg::get('dbCharset')
    );
} catch (CfgException|DBALException $e) {}

//Access-Control-Allow-Origin: http://www.monsite.fr
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE");
header("Access-Control-Allow-Headers: access-control-allow-origin, authorization, content-type, credentials");

// Routage de la requête du client (à faire EN DERNIER).
try {
    Router::route();
} catch (RouterException $e) {
}
