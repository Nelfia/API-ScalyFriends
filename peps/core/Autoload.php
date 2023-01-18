<?php

declare(strict_types=1);

namespace peps\core;

/**
 * Classe 100% statique d'autoload des classes
 */
final class Autoload {
    /**
     * Constructeur privé
     */
    private function __construct(){}

    /**
     * Initialise l'autoload.
     * DOIT être appelée depuis le contrôleur frontal en TOUT PREMIER.
     * Utilise le chemin ABSOLU des classes depuis la racine du serveur pour un fonctionnement correct sur tous les serveurs y compris en cas de gestion des sessions en DB.
     *
     * @throws AutoloadException
     * @return void
     */
    public static function init(): void {
        // Récupérer le chemin absolu du répertoire racine de l'application (index.php). 
        // Double tentative nécessaire pour fonctionnement sur tous serveurs.
        $documentRoot = filter_input(INPUT_SERVER , 'DOCUMENT_ROOT', FILTER_SANITIZE_SPECIAL_CHARS) ?: filter_var($_SERVER['DOCUMENT_ROOT'], FILTER_SANITIZE_SPECIAL_CHARS);
        // var_dump($documentRoot);
        // Si introuvable, exception.
        if(!$documentRoot) 
            PEPS::e(new AutoloadException(AutoloadException::DOCUMENT_ROOT_UNAVAILABLE));
        // Inscrire la fonction d'autoload dans la pile d'autoload.
        spl_autoload_register(
            fn(string $className) : int => @require strtr(("{$documentRoot}/{$className}.php"),'\\', '/' ));
    }
}