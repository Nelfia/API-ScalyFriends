<?php

declare(strict_types=1);

use peps\core\Autoload;
use peps\core\Cfg;
use peps\core\DBAL;
use peps\core\Router;

require './peps/core/Autoload.php';

// Initialisation de l'autoload (à faire EN PREMIER)
Autoload::init();

// Initialiser la configuration en fonction de l'IP du serveur(à faire EN DEUXIEME).
$serverIP = filter_input(INPUT_SERVER , 'SERVER_ADDR', FILTER_VALIDATE_IP) ?: filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP);
if(!$serverIP) exit ("Server variable SERVER_ADDR unavailable");

// ICI VOS CLASSES DE CONFIGURATION EN FONCTION DES IP DE VOS SERVEURS.
// Antislash initial obligatoire ici.
match ($serverIP) {
    "127.0.0.1", "::1" => \cfg\CfgLocal::init(),
    default => exit("Cfg class not found for server IP.")
};

// Initialisation de la connexion DB.
DBAL::init(
    Cfg::get('dbDriver'),
    Cfg::get('dbHost'),
    Cfg::get('dbPort'),
    Cfg::get('dbName'),
    Cfg::get('dbLog'),
    Cfg::get('dbPwd'),
    Cfg::get('dbCharset')
);

//Access-Control-Allow-Origin: http://www.monsite.fr
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE");
header("Access-Control-Allow-Headers: access-control-allow-origin, authorization, content-type");

// Routage de la requête du client (à faire EN DERNIER).
Router::route();
