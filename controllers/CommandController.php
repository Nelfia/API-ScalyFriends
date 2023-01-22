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
     * Accès: PUBLIC (hors ADMIN).
     * 
     * @return void
     */
    public static function create() : void {
        // Initialiser le tableau des résultats.
        $results = [];
        // Vérifier si user connecté, récupérer son token et l'insérer dans la réponse.
        $user = User::getLoggedUser();
        $results['jwt_token'] = JWT::isValidJWT();
        // Si user connecté et que ce n'est pas un ADMIN.
        if($user?->isGranted('ROLE_ADMIN')){
            Router::responseJson(false, "Vous n'êtes pas autorisé à accéder à cette page.", $results);
        }
        // Vérifier que le user n'a pas déjà un panier (max. 1 par user)
        if($user?->getCart()) {
            $results['cart'] = $user->getCart();
            Router::responseJson(false, "Création impossible, l'utilisateur a déjà un panier.", $results);
        }
        // Créer et remplir la nouvelle commande.
        $command = new Command();
        $command->status = 'cart';
        $command->idCustomer = $user?->idUser;
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
        // Initialiser le tableau de la réponse.
        $results = [];
        // Récupérer la commande.
        $command = Command::findOneBy(['idCommand' => $idCommand]);
        // Si pas de commande, envoyer réponse au client.
        if(!$command)
            Router::responseJson(false, "Aucune commande trouvée.");
        // Si status commande = 'cart'
        if($command->status === 'cart') {
            CommandController::updateCartCommand($command);
        }
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
    /**
     * Récupère et valide les données de mise à jour d'un user reçues en PUT.
     *
     * @param Command $command
	 * @return array l'instance du User et le tableau des erreurs.
     */
    private static function processingUserOnCommand(Command $command) : array {
        // Récupérer les données reçues en PUT et les mettre dans la "Super Globale" $_PUT.
		parse_str(file_get_contents("php://input"),$_PUT);
        // Initialiser le tableau des erreurs.
        $errors = [];
        // Récupérer le user logué.
        $user = User::getLoggedUser();
        // Si user non logué, le créer (ROLE_PUBLIC).
        if(!$user){
            $user = new User();
            $user->roles = json_encode(["ROLE_PUBLIC"]);
        }
        // Valider les données.
        $user->email = filter_var($_PUT['email'], FILTER_SANITIZE_EMAIL) ?: null;
        if(!$user->email || !$user->isValidEmail())
            $errors[] = 'Email invalide';
        $user->lastName = filter_var($_PUT['lastName'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$user->lastName || !$user->isValidLastName())
            $errors[] = "Nom de famille invalide.";
        $user->firstName = filter_var($_PUT['firstName'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$user->firstName || !$user->isValidFirstName())
            $errors[] = "Prénom trop long.";    
        $user->mobile = filter_var($_PUT['mobile'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$user->mobile || !$user->isValidMobile())
            $errors[] = "Numéro de téléphone erroné.";    
        $user->postMail = filter_var($_PUT['postMail'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$user->postMail || !$user->isValidPostMail())
            $errors[] = "Adresse incorrecte.";
        $user->postMailComplement = filter_var($_PUT['postMailComplement'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($user->postMailComplement && !$user->isValidPostMailComplement())
            $errors[] = "Complément d'adresse invalide.";
        $user->zipCode = filter_var($_PUT['zipCode'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$user->zipCode || !$user->isValidZipCode())
            $errors[] = "Code postal invalide.";
        $user->city = filter_var($_PUT['city'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$user->city || !$user->isValidCity())
            $errors[] = "Commune ou ville incorrecte.";
        // Remplir la réponse.
		$results['user'] = $user;
		$results['errors'] = $errors;
		return $results;
    }
    /**
     * Met à jour une commande 'cart' validée.
     *
     * @param Command $command
     * @return void
     */
    private static function updateCartCommand(Command $command) : void {
        // Récupérer et traiter les information du user reçues en PUT.
        $processing = CommandController::processingUserOnCommand($command);
        $user = $processing['user'];
        $errors = $processing['errors'];
        // Si aucune erreur, tenter de persister le user en tenant compte de l'email (unique)
        if(!$errors){
            try {
                $user->persist();
            } catch (Exception) {
                $errors[] = "Un utilisateur existe déjà avec cet email, veuillez vous connecter";
            }
        }
        // Récupérer le token si existant et l'inclure dans la réponse.
        $results['jwt_token'] = JWT::isValidJWT();
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
            } catch (Error) {
                $errors[] = "Doublon de référence.";
            } 
        }
        $command->getLines();
        $success = !$errors;
        if($success)
            $message = "Merci de votre confiance. Votre commande a bien été validée.";
        else
            $message = "La commande n'a pas pu être validée.";
        $results['errors'] = $errors;
        $results['customer'] = $user;
        $results['command'] = $command;
        Router::responseJson($success, $message, $results);
    }
}