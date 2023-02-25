<?php

declare(strict_types=1);

namespace controllers;

use cfg\CfgApp;
use entities\Command;
use entities\Line;
use entities\User;
use Error;
use Exception;
use peps\core\Router;
use peps\jwt\JWT;

/**
 * Classe 100% statique de gestion des commandes.
 */
final class CommandController {
    /**
     * Constructeur privé
     */
    private function __construct() {}

    /**
     * Envoie la liste de toutes les commandes demandées.
     * Possibilité de filtrer par status.
     *
     * Envoi de la réponse sous forme d'array:
     *      ['nb'] compte le nb de commandes retournées
     *      ['commands'] la liste de commandes
     * Si erreur, retourne:
     *      ['error']
     *
     * GET /api/orders
     * GET /api/orders/(cart|open|pending|closed|cancelled)
     *
     * Accès: ROLE_USER => Reçoit la liste de ses commandes uniquement.
     * Accès: ROLE_ADMIN => Reçoit la liste de toutes les commandes.
     *
     * @param array|null $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function list(array $assocParams = null) : void {
        // Vérifier si User logué.
        $user = User::getLoggedUser();
        if(!$user) 
            Router::json(json_encode(UserControllerException::NO_LOGGED_USER));
        $status = $assocParams['status'] ?? null;
        if($status === "cart")
            Router::json(json_encode($user->getCart()));
        // Récupérer toutes les commandes en fonction du user logué et du status demandé.
        $commands = $user->getCommands($status)?:null;
        // Initialiser le tableau des résultats.
        $results = [];
        if($commands){
            $results['nb'] = count($commands);
            $results['commands'] = $commands;
        } else {
            $results['error'] = CommandControllerException::NO_MATCH_FOUND;
        }
        // Renvoyer la réponse au client.
        Router::json(json_encode($results));
    }
    /**
     * Affiche le détail d'une commande.
     * 
     * GET /api/orders/{idCommand}
     * Accès: ROLE_USER | ROLE_ADMIN.
     *
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function show(array $assocParams) : void {
        // Vérifier si user logué.
        $user = User::getLoggedUser();
        if(!$user)
            Router::json(UserControllerException::NO_LOGGED_USER);
        // Récupérer l'id de la commande passé en paramètre.
        $idCommand = (int)$assocParams['idCommand'];
        // Récupérer la commande.
        $command = Command::findOneBy(['idCommand' => $idCommand]);
        $command?->getLines();
        // Si l'utilisateur est admin.
        if(($user->isGranted('ROLE_USER') && $command?->idCustomer === $user->idUser) || $user->isGranted('ROLE_ADMIN')) {
            Router::json(json_encode($command));
        }
    }
    /**
     * Contrôle les données reçues en POST & créé/maj commande en DB.
     * Toute nouvelle commande a un status de 'cart'.
     * Un même user ne DEVRAIT avoir qu'un seul panier.
     *
     * Renvoie le panier au client.
     *
     * POST /api/orders
     * Accès: PUBLIC (hors ADMIN).
     * 
     * @return void
     */
    public static function update() : void {
        // Vérifier si user connecté.
        $user = User::getLoggedUser();
        // Si user connecté et que ce n'est pas un ADMIN.
        if($user?->isGranted('ROLE_ADMIN'))
            Router::json(json_encode(UserControllerException::ACCESS_DENIED));
        // Si l'utilisateur a déjà un panier, le récupérer.
        $cart = $user->getCart();
        if(!$cart)
            $cart = CommandController::createCart((array)$user);
        //Récupérer et mettre à jour les lignes du panier reçues en POST.
        $_POST = CfgApp::getInputData();
        $cartLS = $_POST['cart'];
        $lines = $cartLS->lines;
        if($lines){
            foreach ($lines as $line) {
                $line->idCommand = $cart->idCommand;
                //Pour chaque ligne, la mettre à jour en DB.
                LineController::processingDataLine($line, true);
            }
        }
        $cart->getLines();
        Router::json(json_encode($cart));
    }

    /**
     * Modifie le status d'une commande existante.
     *
     * PUT /api/orders/{id}
     * Accès: PUBLIC => Passage de panier à commande à traiter
     *  & créer le user en DB (ROLE_PUBLIC).
     * Accès: ADMIN => Changements de status de la commande.
     *
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function changeStatus(array $assocParams) : void {
        // Récupérer l'id de la commande.
        $idCommand = (int)$assocParams['id'] ?? null;
        // Vérifier si user connecté.
        $user = User::getLoggedUser();
        // Récupérer la commande initiale grâce à l'id passé en paramètre.
        $targetCommand = Command::findOneBy(['idCommand' => $idCommand]);
        // Si user connecté, vérifier ses droits d'accès.
        if(!$user?->isGranted('ROLE_ADMIN') || $user->idUser !== $targetCommand->idCustomer)
            Router::json(json_encode(UserControllerException::ACCESS_DENIED));
        // Récupérer les données reçues en PUT.
        $_PUT = CfgApp::getInputData();
        // Vérifier les données reçues.
        $newCart = $_PUT['cart'];
        $lines = $newCart->lines;
        foreach ($lines as $line){
            $newLine = new Line();
            $newLine->idLine = filter_var($line->idLine, FILTER_SANITIZE_NUMBER_INT) ?: null;
            $newLine->idLine = $newLine->idLine === 0 ? null : $newLine->idLine;

        }
        //TODO: Réécrire fonction changeStatus
        // Persister ligne à ligne
        
    }
    private static function processingUserOnCommand() : array {
        // Récupérer les données reçues en PUT et les mettre dans la "Super Globale" $_PUT.
		$_PUT = CfgApp::getInputData();
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
        $user->email = filter_var($_PUT['email'], FILTER_SANITIZE_EMAIL) ?: $user->email;
        if(!$user->email || !$user->isValidEmail() )
            $errors[] = 'Email invalide';
        $user->lastName = filter_var($_PUT['lastName'], FILTER_SANITIZE_SPECIAL_CHARS) ?: $user->lastName;
        if(!$user->lastName || !$user->isValidLastName())
            $errors[] = "Nom de famille invalide.";
        $user->firstName = filter_var($_PUT['firstName'], FILTER_SANITIZE_SPECIAL_CHARS) ?: $user->firstName;
        if(!$user->firstName || !$user->isValidFirstName())
            $errors[] = "Prénom trop long.";    
        $user->mobile = filter_var($_PUT['mobile'], FILTER_SANITIZE_SPECIAL_CHARS) ?: $user->mobile;
        if(!$user->mobile || !$user->isValidMobile())
            $errors[] = "Numéro de téléphone erroné.";    
        $user->postMail = filter_var($_PUT['postMail'], FILTER_SANITIZE_SPECIAL_CHARS) ?: $user->postMail;
        if(!$user->postMail || !$user->isValidPostMail())
            $errors[] = "Adresse incorrecte.";
        $user->postMailComplement = filter_var($_PUT['postMailComplement'], FILTER_SANITIZE_SPECIAL_CHARS) ?: $user->postMailComplement;
        if($user->postMailComplement && !$user->isValidPostMailComplement())
            $errors[] = "Complément d'adresse invalide.";
        $user->zipCode = filter_var($_PUT['zipCode'], FILTER_SANITIZE_SPECIAL_CHARS) ?: $user->zipCode;
        if(!$user->zipCode || !$user->isValidZipCode())
            $errors[] = "Code postal invalide.";
        $user->city = filter_var($_PUT['city'], FILTER_SANITIZE_SPECIAL_CHARS) ?: $user->city;
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
    private static function updateCartToCommand(Command $command) : void {
        // Récupérer et traiter les information du user reçues en PUT.
        $processing = CommandController::processingUserOnCommand();
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

    /**
     * Retourne le panier de l'utilisateur.
     * Si déjà existant, le retourne, sinon le créé.
     *
     * GET /api/createCart
     *
     * @param User $user
     * @return Command
     */
    public static function createCart(array $assocParams) : Command {
        $user = User::findOneBy(['idUser' => (int)$assocParams['idUser']]);
        $cart = $user->getCart() ?? null;
        if($cart !== null)
            return $cart;
        $cart = new Command();
        $cart->idCustomer = $user->idUser;
        $cart->status = 'cart';
        $cart->lastChange = date('Y-m-d H:i:s');
        $cart->persist();
        return $user->getCart();
    }
}