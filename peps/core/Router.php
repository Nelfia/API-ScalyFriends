<?php

declare(strict_types=1);

namespace peps\core;

use Error;

/**
 * Classe 100% statique de routage
 * Offre 5 méthodes de routage:
 *      render(): rendre une vue.
 *      text() :envoyer du text brut (text/plain).
 *      json() : envoyer du JSON (application/json)
 *      download(): envoyer un fichier en flux binaire
 *      redirect(): rediriger côté client
 * Toutes ces méthodes ARRETENT l'éxécution.
 */
final class Router {

    /**
     * Constructeur privé.
     */
    private function __construct() {}

    /**
     * Routage initial.
     * Analyse de la requête du client, détermine la route et invoque la méthode appropriée du contrôleur approprié.
     *
     * @throws RouterException Si erreur.
     * @return void
     */
    public static function route() : void {
        // Récupérer le verbe HTTP et l'URI de la requête client.
        $verb = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_SPECIAL_CHARS) ?: filter_var($_SERVER['REQUEST_METHOD'], FILTER_SANITIZE_SPECIAL_CHARS);
        $uri = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_SPECIAL_CHARS) ?: filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_SPECIAL_CHARS);
        // Si pas de verbe ou pas d'URI, erreur 404.
        if(!$verb || !$uri) self::render('error404.php');
        // Charger la table de routage JSON.
        $routesJSON = @file_get_contents(Cfg::get('ROUTES_FILE'));
        // Si fichier introuvable, exception.
        if(!$routesJSON) PEPS::e(new RouterException(RouterException::JSON_ROUTES_FILE_UNAVAILABLE));
        // Décoder le JSON.
        $routes = json_decode($routesJSON);
        if(!$routes) 
            PEPS::e(new RouterException(RouterException::JSON_ROUTES_FILE_CORRUPTED));
        // Parcourir la table de routage.
        foreach($routes as $route) {
            // Utiliser l'expression régulière de l'URI avec un slash final optionnel.
            // Délimiteur @ au lieu de / (pour les routes).
            $regexp = "@^{$route->uri}/?$@";
            // Préparer un tableau pour stcker les éventuels paramètres capturés
            $matches = [];
            // Si route correspondante est trouvée...
            if(!strcasecmp($verb, $route->verb) && preg_match($regexp, $uri, $matches)){
                // Supprimer le premier élément de $matches.
                array_shift($matches);
                // Si clés présentes mais nb de clés différent du nombre de valeurs, exception.
                if(isset($route->params) && count($matches) !== count($route->params))
                    PEPS::e(new RouterException(RouterException::WRONG_PARAMS_ARRAY));
                // Si paramètres, combiner les noms des paramètres avec les valeurs de l'URI pour obtenur un tableau associatif.
                $assocParams = $matches ? array_combine($route->params, $matches) : [];
                // Séparer le nom du contrôleur du nom de la méthode.
                [$controller, $method] = explode('.', $route->method);
                // Préfixer le nom du contrôleur avec son namespace (pas de use)
                $controller = '\\' . (Cfg::get('CONTROLLERS_NAMESPACE') . '\\' .$controller);
                // Invoquer la méthode du controlleur en lui passant le tableau des paramètres
                try {
                    $controller::$method($assocParams);
                } catch (Error $e) {
                    PEPS::e($e, RouterException::CONTROLLER_METHOD_FAILED);
                }
                // Retourner pour quitter le routage.
                return;
            }
        }
        // Si aucune route trouvée, erreur 404
        self::render('error404.php');
    }

    /**
     * Rend une vue et arrête l'exécution
     *
     * @throws RouterException Si erreur.
     * @param string $view Nom de la vue (ex: 'test.php')
     * @param array $assocParams Tableau associatif des paramètres à transmettre à la vue.
     * @return never
     */
    public static function render(string $view, array $assocParams = []) : never {
        // Transformer chaque clé du tableau associatif en variable.
        // Si conflit avec variables existantes, exception.
        if(extract($assocParams, EXTR_SKIP) < count($assocParams)){
            PEPS::e(new RouterException(RouterException::PARAMS_ARRAY_CONTAINS_INVALID_KEY));
        }
        // Si mode DEBUG, insérer le tableau des requêtes.
        if(Cfg::get('EXECUTION_MODE') === ExecutionMode::PROD)
            var_dump(PEPS::getQueries());
        // Insérer la vue.
        try {
            @require Cfg::get('VIEWS_DIR') . '/' . $view;
        } catch (Error $e) {
            PEPS::e($e, RouterException::VIEW_NOT_FOUND);
        }
        // Arrêter l'exécution.
        exit;
    }

    /**
     * Envoie au client une chaîne en texte brut et arrête l'exécution.
     *
     * @param string $text Chaîne de texte.
     * @return never
     */
    public static function text(string $text) : never {
        // Définir le type MIME sur "text/plain"
        header('Content-Type: text/plain');
        // Envoyer le texte et arrêter l'exécution
        exit($text);
    }

    /**
     * Envoie au client une chaîne JSON.
     *
     * @param string $json Chaîne JSON.
     * @return never
     */
    public static function json(string $json) : never {
        // Définir le type MIME sur "application/json"
        header('Content-Type: application/json');
        // Envoyer la chaîne json au client et arrêter l'exécution
        exit($json);
    }

    /**
     * Envoie au client un fichier pour download (ou intégration comme par exemple une image) et arrête l'exécution.
     *
     * @throws RouterException Si fichier inaccessible.
     * @param string $file Chemin du fichier.
     * @param string $mimeType Type MIME du fichier.
     * @param string $name Nom du fichier envoyé au client.
     * @return never
     */
    public static function download(string $file, string $mimeType, string $name = "File") : never {
        try {
            // Paramètrer l'entête HTTP.
            header("Content-Type :" . $mimeType);
            header("Content-Transfer-Encoding: binary");
            header("Content-length: " . @filesize($file));
            header("Content-Disposition-Attachment; filename={$name}");
            // Lire le fichier et l'envoyer vers le client
            readfile($file);
        } catch (Error $e) {
            PEPS::e($e, RouterException::UNREACHABLE_FILE);
        }
        // Arrêter l'exécution.
        exit;
    }

    /**
     * Redirige côté client.
     * Envoi la requête vers le client pour demander une redirection vers une URI puis arrête l'exécution.
     *
     * @param string $uri
     * @return never
     */
    public static function redirect(string $uri) : never {
        // Envoyer la demande de redirection.
        header("Location: {$uri}");
        // Arrêter l'exécution.
        exit;
    }
}