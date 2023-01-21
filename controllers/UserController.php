<?php

declare(strict_types=1);


namespace controllers;

use entities\User;
use Error;
use Exception;
use peps\core\Router;
use peps\jwt\JWT;

/**
 * Classe 100% statique de gestion des utilisateurs.
 */
final class UserController {
    /**
     * Constructeur privé
     */
    private function __construct() {}
    
    /**
     * Réucpère la liste de tous les users.
     * 
     * GET /api/users
     * Accès: ROLE_ADMIN.
     *
     * @return void
     */
    public static function list() : void {
		// Récupérer l'instance du User logué.
		$user = User::getLoggedUser();
        // Initialiser le tableau des résultats.
        $results = array();
        // Vérifier les droits d'accès du user.
        $success = $user?->isGranted("ROLE_ADMIN") !== null ?: false;
        // Si pas de user logué ou role non autorisé
        if(!$user || !$success){
            $message = "Vous n'êtes pas autorisé à accéder à cette page.";
            $results['jwt_token'] = JWT::isValidJWT();
        } else {
            $message = "Voici la liste des users.";
            $users = User::findAllBy([],['lastName'=>'ASC', 'firstName'=> 'ASC']);
            $results['nb'] = count($users);
            $results['users'] = $users;
        };
        // Envoyer la réponse au client.
        Router::responseJson($success, $message, $results);
    }
    /**
     * Récupère les informations d'un User.
     * 
     * GET /api/users/{id}
     * Accès: ROLE_USER || ROLE_ADMIN.
     *
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function show(array $assocParams) : void {
		// Récupérer l'id du User dont on veut récupérer les données.
		$idUser = (int)$assocParams['id'];
		// Récupérer l'instance du User logué.
		$loggedUser = User::getLoggedUser();
		// Initialiser le tableau des résultats de la réponse.
		$results = array();
		$results['jwt_token'] = JWT::isValidJWT();
		// Si user non logué ou token invalide.
		if(!$loggedUser){
			$success = false;
			$message = "Vous devez être connecté pour accéder à cette page.";
			Router::responseJson($success, $message, $results);
		}
		// Vérifier les droits d'accès du user.
		$success = (($loggedUser?->isGranted("ROLE_USER") && ($loggedUser?->idUser === $idUser))||$loggedUser?->isGranted("ROLE_ADMIN"));
		// Si l'accès est refusé.
		if(!$success){
			$message = "Vous n'êtes pas autorisé à accéder à cette page.";
		} else {
			$message = "Voici les information du user.";
			$user = User::findOneBy(['idUser' => $idUser]);
			// Par mesure de sécurité, retirer le role et le mdp du user dans la réponse.
			unset($user->pwd);
			unset($user->roles);
			$results['user'] = $user;
		};
		// Envoyer la réponse au client.
		Router::responseJson($success, $message, $results);
    }
    /**
	 * Création d'un nouveau compte user.
     * Enregistre un nouveau User en BD.
     * 
     * POST /api/users
     * Accès: PUBLIC.
     *
     * @return void
     */
    public static function create() : void {
		// Si user logué, destruction du token.
		if(User::getLoggedUser()) JWT::destroy();
		// Initialiser le tableau de résultats.
		$results = [];
		// Créer un user.
		$user = new User();
		// Initialiser le tableau des erreurs.
		$errors = [];
		// Ajout de l'accès user.
		$user->roles = json_encode(["ROLE_USER"]);
		// Récupérer et valider les données.
		$user->username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$user->username || mb_strlen($user->username) > 50)
			$errors[] = 'Username invalide';

		$user->email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?: null;
		$user->email = filter_var($user->email, FILTER_VALIDATE_EMAIL) ?: null;
        if(!$user->email || mb_strlen($user->email) > 50)
			$errors[] = 'Email invalide';

		$user->pwd = filter_input(INPUT_POST, 'pwd', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		$pwdConfirm = filter_input(INPUT_POST, 'pwdConfirm', FILTER_SANITIZE_SPECIAL_CHARS);
		if(!$user->pwd || mb_strlen($user->pwd) < 4 || ($user->pwd !== $pwdConfirm))
			$errors[] = "Mot de passe invalide.";
		else $user->pwd = password_hash($user->pwd, PASSWORD_DEFAULT);
			
        // Si aucune erreur, persister le user en tenant compte des éventuels doublons d'username ou email
		if(!$errors){
			try {
				$user->persist();
			} catch (Exception $e) {
				$errors[] = $e->getMessage();
			}
		}	
		$success = !$errors;
		// Envoyer la réponse au client.
		if(!$success){
			$message = "L'utilisateur n'a pas pu être crée.";
			$results['errors'] = $errors;
		} else {
			$message = "L'utilisateur a bien été créé et est désormais logué.";
			$payload['user_id'] = $user->idUser;
			$token = JWT::generate([], $payload);
			$results['jwt_token'] = $token;
		}
		// Par sécurité, on retire le mot de passe du user et son role dans la réponse.
		unset($user->pwd);
		unset($user->roles);
		$results['user'] = $user;
		Router::responseJson($success, $message, $results);
    }
    /**
     * Met à jour les données d'un User en BD.
     * 
     * PUT /api/users/{id}
     * Accès: ROLE_USER || ROLE_ADMIN.
     *
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function update(array $assocParams) : void {
		// Récupérer l'id du User dont on veut maj les données.
		$idUser = (int)$assocParams['id'];
		// Récupérer l'instance du User logué.
		$loggedUser = User::getLoggedUser();
		// Initialiser le tableau des résultats de la réponse.
		$results = [];
		$results['jwt_token'] = JWT::isValidJWT();
		// Si user non logué ou token invalide.
		if(!$loggedUser){
			$success = false;
			$message = "Vous devez être connecté pour accéder à cette page.";
			Router::responseJson($success, $message, $results);
		}
		// Vérifier les droits d'accès du user.
		$success = (($loggedUser?->isGranted("ROLE_USER") && ($loggedUser?->idUser === $idUser))||$loggedUser?->isGranted("ROLE_ADMIN"));
		// Si l'accès est refusé.
		if(!$success){
			$message = "Vous n'êtes pas autorisé à accéder à cette page.";
			Router::responseJson($success, $message, $results);
		}	
		// Initialiser le tableau des erreurs.
		$errors = [];
		// Récupérer le User à mettre à jour.
		$user = User::findOneBy(['idUser' => $idUser]);

		// Récupérer le tableau des données reçues en PUT et les mettre dans la Super Globale $_PUT.
		parse_str(file_get_contents("php://input"),$_PUT);

		// Récupérer et valider les données.
		$user->email = filter_var($_PUT['email'], FILTER_SANITIZE_EMAIL) ?: null;
		$user->email = filter_var($user->email, FILTER_VALIDATE_EMAIL) ?: null;
        if(!$user->email || mb_strlen($user->email) > 255)
			$errors[] = 'Email invalide';

		$user->lastName = filter_var($_PUT['lastName'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		if($user->lastName && (mb_strlen($user->lastName) > 255 || mb_strlen($user->lastName) < 2))
			$errors[] = "Nom de famille invalide.";

		$user->firstName = filter_var($_PUT['firstName'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		if($user->firstName && (mb_strlen($user->firstName) > 200 || mb_strlen($user->firstName) < 2))
			$errors[] = "Prénom trop long.";
		
		$user->mobile = filter_var($_PUT['mobile'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		// Enlever tous les caractères non numériques.
		if($user->mobile) {
			$user->mobile = preg_replace('`[^0-9]`', '', $user->mobile);
			$isValidMobile = (bool)preg_match('`^0[1-9]([0-9]{2}){4}$`', $user->mobile);
			if(!$isValidMobile) 
				$errors[] = "Numéro de téléphone erroné.";
		}

		$user->postMail = filter_var($_PUT['postMail'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		if($user->postMail !== null){
			$isValidPostMail = (bool)preg_match('`^[0-9]+ [a-z]\i+ [a-z]\i+`',$user->postMail);
			if(mb_strlen($user->postMail) > 255 || mb_strlen($user->postMail) < 6 || !$isValidPostMail)
				$errors[] = "Adresse incorrecte.";
        }

		$user->postMailComplement = filter_var($_PUT['postMailComplement'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		if($user->postMailComplement) {
			if(mb_strlen($user->postMailComplement) > 255)
				$errors[] = "Complément d'adresse trop long.";
		}
		
		$user->zipCode = filter_var($_PUT['zipCode'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		if($user->zipCode) {
			$isValidZipCode = preg_match('`^[0-9]{4}0$`', $user->zipCode);
			if(!$isValidZipCode) $errors[] = "Code postal invalide.";
		}

		$user->city = filter_var($_PUT['city'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		if($user->city && mb_strlen($user->city) > 255)
			$errors[] = "Commune ou ville trop longue.";

		// Si aucune erreur, persister le user en tenant compte des éventuels doublons d'username ou email
		if(!$errors) {
			try {
				$user->persist();
			} catch (Exception $e) {
				$errors[] = $e->getMessage();
			}		
		}
		$success = !$errors;
		// Envoyer la réponse au client.
		if(!$success){
			$message = "L'utilisateur n'a pas pu être mis à jour.";
			$results['errors'] = $errors;
		} else {
			$message = "L'utilisateur a bien été mis à jour.";
		}
		// Par sécurité, on retire le mot de passe du user et son role dans la réponse.
		unset($user->pwd);
		unset($user->roles);
		$results['user'] = $user;
		Router::responseJson($success, $message, $results);
    }
    /**
     * Supprime un User en BD.
     * 
     * DELETE /api/users/{id}
     * Accès: ROLE_USER || ROLE_ADMIN.
     *
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function delete(array $assocParams) : void {
		// Récupérer l'id du User dont on veut supprimer les données.
		$idUser = (int)$assocParams['id'];
		// Récupérer l'instance du User logué.
		$loggedUser = User::getLoggedUser();
		// Initialiser le tableau des résultats de la réponse.
		$results = [];
		$results['jwt_token'] = JWT::isValidJWT();
		// Si user non logué ou token invalide.
		if(!$loggedUser){
			$success = false;
			$message = "Vous devez être connecté pour accéder à cette page.";
			Router::responseJson($success, $message, $results);
		}
		// Vérifier les droits d'accès du user.
		$success = (($loggedUser?->isGranted("ROLE_USER") && ($loggedUser?->idUser === $idUser))||$loggedUser?->isGranted("ROLE_ADMIN"));
		// Si l'accès est refusé.
		if(!$success){
			$message = "Vous n'êtes pas autorisé à accéder à cette page.";
			Router::responseJson($success, $message, $results);
		}
		// Initialiser le tableau des erreurs.
		$errors = [];
		// Récupérer le User à mettre à jour.
		$user = User::findOneBy(['idUser' => $idUser]);
		if($user) {
			$results['user'] = $user;
			try {
				$success = $user->remove();
				$message = "L'utilisateur a bien été supprimé";
			} catch (Error $e) {
				$errors[] = $e->getMessage();
			}
		} else {
			$message = "Aucun User trouvé.";
		}
		Router::responseJson($success, $message, $results);
    }
    /**
     * Tente de loguer un utilisateur et retourne un jeton JWT.
     * 
     * POST /api/users/login
     * Accès: PUBLIC.
     *
     * @return void
     */
    public static function login() : void {
		// Vérifier si user logué et détruire son token.
		if(User::getLoggedUser()) JWT::destroy();
        // Créer un user
        $user = new User();
        // Initialiser le tableau des erreurs
        $errors = [];
        // Initialiser le tableau de la réponse JSON.
        $results = array();
        // Récupérer les données et tenter le login.
        $user->username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $pwd = filter_input(INPUT_POST, 'pwd', FILTER_SANITIZE_SPECIAL_CHARS);
        // Si login OK, générer le JWT et renvoyer réponse en Json.
		$success = $user->login($pwd);
        if ($success) {
            // On crée le contenu (payload)
            $payload['user_id'] = $user->idUser ;
            $token = JWT::generate([], $payload);
            $message = "Vous êtes désormais connecté !";
            $results['jwt_token'] = $token;
        } else{
			$message = "Login failed !";
            $results['errors'] = $errors;
        }
		// Par mesure de sécurité, retirer le role et le mdp du user dans la réponse.
		unset($user->pwd);
		unset($user->roles);
		$results['user'] = $user;
        Router::responseJson($success, $message, $results);
	}
    /**
     * Délogue l'utilisateur via son JWT.
     * Le JWT DEVRA également être maj côté client (si enregistré dans un strore par exemple).
     * 
     * POST /api/users/logout
     * Accès: ROLE_USER || ROLE_ADMIN.
     *
     * @return void
     */
    public static function logout() : void {
        // Détruire le token.
        $success = JWT::destroy();
        var_dump(JWT::destroy());
        $message = "Token supprimé";
        $results = array();
        $results['jwt_token'] = JWT::isValidJWT()?: '';
        // Rediriger
        Router::responseJson($success, $message, $results);
    }

}
