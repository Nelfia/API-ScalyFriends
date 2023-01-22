<?php

declare(strict_types=1);

namespace controllers;

use DateTime;
use entities\Command;
use peps\core\Router;
use peps\jwt\JWT;
use entities\Product;
use entities\User;
use Error;
use Exception;

/**
 * Classe 100% statique de gestion des commandes.
 */
final class CommandController {
    /**
     * Constructeur privé
     */
    private function __construct() {}

    /**
     * Envoie la liste de toutes les commandes (ou filtrées par status).
     * 
     * GET /api/orders
     * GET /api/orders/(cart|open|pending|closed|cancelled)
     * Accès: ROLE_USER => Reçoit la liste de ses commandes uniquement.
     * Accès: ROLE_ADMIN => Reçoit la liste de toutes les commandes.
     *
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function list(array $assocParams = null) : void {
        // Vérifier si User logué.
        $user = User::getLoggedUser();
        if(!$user) 
        Router::responseJson(false, "Vous devez être connecté pour accéder à cette page.");
        $status = $assocParams['status']?? null;
        // exit(json_encode($status));
        // Récupérer toutes les commandes en fonction du user logué et du status demandé.
        $orders = $user->getCommands($status)?:null;
        // Initialiser le tableau des résultats.
        $results = [];
        if($orders){
            $success = true;
            $message = "Voici la liste de toutes les commandes";
            $results['nb'] = count($orders);
            $results['status'] = $status?: "all";
            $results['orders'] = $orders;
        } else {
            $success = false;
            $message = "Aucune commande trouvée .";
        }
        // Renvoyer la réponse au client.
        Router::responseJson($success, $message, $results);
    }
    /**
     * Affiche le détail d'une commande.
     * 
     * GET /api/orders/{id}
     * Accès: ROLE_USER | ROLE_ADMIN.
     *
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function show(array $assocParams) : void {
        // Vérifier si user logué.
        $user = User::getLoggedUser();
        if(!$user)
            Router::responseJson(false, "Vous devez être connecté pour accéder à cette page.");
        // Récupérer l'id de la commande passé en paramètre.
        $idCommand = (int)$assocParams['id'];
        // Récupérer la commande.
        $command = Command::findOneBy(['idCommand' => $idCommand]);
        $command?->getLines();
        $results = [];
        $results['jwt_token'] = JWT::isValidJWT();
        // Si l'utilisateur est admin.
        if(($user->isGranted('ROLE_USER') && $command?->idCustomer === $user->idUser) || $user->isGranted('ROLE_ADMIN')) {
            $results['command'] = $command;
            Router::responseJson(true, "Voici la commande.", $results);
        }
        // Envoyer la réponse en json.
        Router::responseJson(false, "Vous n'êtes pas autorisé à accéder à cette page.", $results);
    }
    /**
     * Contrôle les données reçues en POST & créé une nouvelle commande en DB.
     * Toute nouvelle commande commence au status de panier.
     * Un même user ne DEVRAIT avoir qu'un seul panier.
     *
     * POST /api/orders
     * Accès: PUBLIC.
     * 
     * @return void
     */
    public static function create() : void {
        // Initialiser le tableau des résultats.
        $results = [];
        // Créer une nouvelle commande.
        $command = new Command();
        // Créer une nouvelle commande.
        $command->status = 'cart';
        // Vérifier si user connecté.
        $user = User::getLoggedUser();
        // Si user connecté.
        if($user){
            // Récupérer son token et l'insérer dans la réponse.
            $token = JWT::isValidJWT();
            $results['jwt_token'] = $token;
            // Vérifier que le user n'a pas déjà un panier (max. 1 par user)
            if($user->getCart()) {
                $results['cart'] = $user->getCart();
                Router::responseJson(false, "Création impossible, l'utilisateur a déjà un panier.", $results);
            }
            // Récupérer son id et l'insérer dans la commande.
            $command->idCustomer = $user->idUser;
        }
        // Ajouter la date de dernier changement.
        $command->lastChange = date('Y-m-d H:i:s');
        // Persister la commande en BD.
        $command->persist();
        $results['cart'] = $command;
        // Envoyer la réponse au client.
        Router::responseJson(true, "Le panier a bien été créé.", $results);
    }
    /**
     * Modifie le status d'une commande existante.
     *
     * PUT /api/orders/{id}
     * Accès: PUBLIC => Passage de panier à commande à traiter
     *  & création du user en DB (ROLE_PUBLIC).
     * Accès: ADMIN => Changements de status de la commande.
     * 
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function update(array $assocParams) : void {
        // Récupérer l'id de la commande.
        $idCommand = (int)$assocParams['id'] ?? null;
        // Récupérer la commande.
        $command = Command::findOneBy(['idCommand' => $idCommand]);
        // Récupérer les données reçues en PUT et les mettre dans la "Super Globale" $_PUT.
		parse_str(file_get_contents("php://input"),$_PUT);
        // Initialiser le tableau des erreurs.
        $errors = [];
        // Initialiser le tableau de la réponse.
        $results = [];
        // Si pas de commande, envoyer réponse au client.
        if(!$command)
            Router::responseJson(false, "Aucune commande trouvée.");
        // Récupérer 
        $user = User::getLoggedUser();
        // Si status commande = 'cart'
        if($command->status === 'cart') {
            // Si user non inscrit (ROLE_PUBLIC), le créer.
            if(!$user){
                $user = new User();
                $user->roles = json_encode(["ROLE_PUBLIC"]);
            }
            // Récupérer et valider les données.
            $user->email = filter_var($_PUT['email'], FILTER_SANITIZE_EMAIL) ?: null;
            $user->email = filter_var($user->email, FILTER_VALIDATE_EMAIL) ?: null;
            if(!$user->email || mb_strlen($user->email) > 50)
                $errors[] = 'Email invalide';

            $user->lastName = filter_var($_PUT['lastName'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
            if(!$user->lastName || mb_strlen($user->lastName) > 255 || mb_strlen($user->lastName) < 2)
                $errors[] = "Nom de famille invalide.";
    
            $user->firstName = filter_var($_PUT['firstName'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
            if(!$user->firstName || mb_strlen($user->firstName) > 200 || mb_strlen($user->firstName) < 2)
                $errors[] = "Prénom trop long.";
            
            $user->mobile = filter_var($_PUT['mobile'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
            if($user->mobile) {
                // Enlever tous les caractères non numériques.
                $user->mobile = preg_replace('`[^0-9]`', '', $user->mobile);
                $isValidMobile = (bool)preg_match('`^0[1-9]([0-9]{2}){4}$`', $user->mobile);
            }
            if(!$user->mobile || !$isValidMobile) {
                $errors[] = "Numéro de téléphone erroné.";
            }
    
            $user->postMail = filter_var($_PUT['postMail'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
            if(!$user->postMail || mb_strlen($user->postMail) > 255 || mb_strlen($user->postMail) < 6 ){
                $errors[] = "Adresse incorrecte.";
            }
    
            $user->postMailComplement = filter_var($_PUT['postMailComplement'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
            if($user->postMailComplement) {
                if(mb_strlen($user->postMailComplement) > 255)
                    $errors[] = "Complément d'adresse trop long.";
            }
            
            $user->zipCode = filter_var($_PUT['zipCode'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
            if($user->zipCode)
                $isValidZipCode = preg_match('`^[0-9]{4}0$`', $user->zipCode);
            if(!$user->zipCode || !$isValidZipCode) 
                $errors[] = "Code postal invalide.";
    
            $user->city = filter_var($_PUT['city'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
            if(!$user->city || mb_strlen($user->city) > 255)
                $errors[] = "Commune ou ville incorrecte.";
        }
        // Si aucune erreur, tenter de persister le user en tenant compte de l'email (unique)
        if(!$errors){
            try {
                $user->persist();
            } catch (Error) {
                $errors[] = "Un utilisateur existe déjà avec cet email, veuillez vous connecter";
            }
        }
        // Récupérer le token si existant et l'inclure dans la réponse avec le user.
        $token = JWT::isValidJWT();
        $results['jwt_token'] = $token;
        $results['customer'] = $user;
        // Si toujours aucune erreur, mettre à jour la commande.
        if(!$errors){
            $command->idCustomer = $user->idUser;
            $command->status = 'open';
            $command->orderDate = date('Y-m-d H:i:s');
            $command->lastChange = $command->orderDate;
            $command->ref = date('YmdHis') . $command->idCustomer;
            // Tenter de persister en tenant compte des éventuels doublon de ref.
            try {
                $command->persist();
            } catch (Exception) {
                $errors[] = "Doublon de référence.";
            } 
        }
        $success = !$errors;
        $command->getLines();
        $results['order'] = $command;
        if($success)
            $message = "Merci de votre confiance. Votre commande a bien été validée.";
        else{
            $message = "La commande n'a pas pu être validée.";
            $results['errors'] = $errors;
        }
        // Envoyer la réponse au client.
        Router::responseJson($success, $message, $results);
    }
    /**
     * Supprime une commande status "cart"
     * n'ayant pas d'idCustomer -> PUBLIC non USER
     * et dont lastChange + $timeout < maintenant.
     *
     * DELETE /orders
     * Accès: ADMIN.
     * 
     * @return void
     */
    public static function delete() : void {
        // Vérifier si User logué.
        $user = User::getLoggedUser();
        if(!$user) 
            Router::responseJson(false, "Vous devez être connecté pour accéder à cette page.");
        // Vérifier si a les droits d'accès.
        if(!$user->isGranted("ROLE_ADMIN"))
            Router::responseJson(false, "Vous n'êtes pas autorisé à accéder à cette page.");
        // Récupérer UNIQUEMENT les commandes dont le status est panier.
        $commands = Command::findAllBy(['status' => 'cart'], []);
        $now = new DateTime();
        $nb = 0;
        foreach($commands as $command) {
            // Si le ^panier n'est pas rattachée à un user.
            if(!$command->idCustomer){
                $lastChange = new DateTime($command->lastChange);
                $interval = date_diff($lastChange, $now);
                $interval = $interval->format('%a'); // exprimée en jour entier (string)
                if((int)$interval > 2 ){
                    $command->remove();
                    $nb++;
                }
            }
        }
        $results = [];
        $results['nb'] = $nb;
        $results['jwt_token'] = JWT::isValidJWT();
        Router::responseJson(true, "Les paniers obsolètes ont bien été supprimés.", $results);
    }
}