<?php

declare(strict_types=1);


namespace controllers;

use cfg\CfgApp;
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
        $success = $user?->isGranted("ROLE_ADMIN") ?: false;
        // Si pas de user logué ou role non autorisé
        if(!$user || !$success){
            $message = UserControllerException::ACCESS_DENIED;
            $results['jwt_token'] = JWT::isValidJWT();
        } else {
            $message = "Voici la liste des users.";
            $users = User::findAllBy([],['lastName'=>'ASC', 'firstName'=> 'ASC']);
            $results['nb'] = count($users);
            $results['users'] = $users;
        }
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
		// Si user non logué ou token invalide.
		if(!$loggedUser){
			Router::json(json_encode(UserControllerException::NO_LOGGED_USER));
		}
		// Vérifier les droits d'accès du user.
		if ((!$loggedUser?->isGranted("ROLE_USER") || ($loggedUser?->idUser !== $idUser))|| !$loggedUser?->isGranted("ROLE_ADMIN"))
			Router::json(json_encode(UserControllerException::ACCESS_DENIED));
		else {
            $user = User::findOneBy(['idUser' => $idUser]);
            // Par mesure de sécurité, retirer le role et le mdp du user dans la réponse.
            $user = $user->onlyUser();
        }
		// Envoyer la réponse au client.
		Router::json(json_encode($user));
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
		// Si user logué, le déloguer.
		if(User::getLoggedUser()) self::logout();
        // Récupérer les données envoyées par le client.
        $data = CfgApp::getInputData();
		// Récupérer et valider les données du user.
		$processing = self::processingUserCreation($data);
		$errors = $processing['errors'];
		$user = $processing['user'];
        // Si aucune erreur, persister le user en tenant compte des éventuels doublons d'username ou email
		if(!$errors){
			try {
				$user->persist();
			} catch (Exception $e) {
				$errors[] = $e->getMessage();
			}
		}
		// Remplir la réponse.
		if($errors)
			Router::json(json_encode($errors));
		// Si toujours aucune erreur.
		$payload['user_id'] = $user->idUser;
		$token = JWT::generate([], $payload, 3600);
        $results['idToken'] = json_encode($token);
        $results['expires'] = json_encode(JWT::getPayload($token)['exp']);
        // Créer un nouveau panier au user nouvellement créé.
        CommandController::createCart(["idUser" => $user->idUser]);
        $results['cart'] = $user->getCart();
        $results['idCart'] = $user->getIdCart();
        $user->onlyUser();
        $results['user'] = $user;
		Router::json(json_encode($results));
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
		if(!$loggedUser)
			Router::responseJson(false, "Vous devez être connecté pour accéder à cette page.", $results);
		// Vérifier les droits d'accès du user.
		if(!(($loggedUser?->isGranted("ROLE_USER") && ($loggedUser?->idUser === $idUser))||$loggedUser?->isGranted("ROLE_ADMIN")))
			Router::responseJson(false, "Vous n'êtes pas autorisé à accéder à cette page.", $results);
		// Récupérer le User à mettre à jour.
		$user = User::findOneBy(['idUser' => $idUser]);
		// Récupérer et valider les données.
		$processing = UserController::processingUserUpdate($user);
		$user = $processing['user'];
		$errors = $processing['errors'];
		// Si aucune erreur, persister le user en tenant compte des éventuels doublons d'username ou email
		if(!$errors) {
			try {
				$user->persist();
			} catch (Exception $e) {
				$errors[] = $e->getMessage();
			}		
		}
		// Remplir la réponse au client.
		$success = !$errors;
		if($errors){
			$message = "L'utilisateur n'a pas pu être mis à jour.";
			$results['errors'] = $errors;
		} else
			$message = "L'utilisateur a bien été mis à jour.";
		// Retourner le user "sécurisé".
		$results['user'] = $user->secureReturnedUser();
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
        // Récupérer les données envoyées par le client.
        $data = CfgApp::getInputData();
        // Récupérer les données et tenter le login.
        $user->username = filter_var($_POST['username'] ??$data['username'], FILTER_SANITIZE_SPECIAL_CHARS)?: null;
        $pwd = filter_var($_POST['pwd'] ??$data['pwd'], FILTER_SANITIZE_SPECIAL_CHARS)?: null;
        // Si login OK, générer le JWT et renvoyer réponse en Json.
        if ($user->login($pwd)) {
            // Créer le contenu du payload.
            $payload['user_id'] = $user->idUser ;
            $token = JWT::generate([], $payload, 3600);
            // Construire la réponse à envoyer au client.
            $results['idToken'] = json_encode($token);
            $results['expires'] = json_encode(JWT::getPayload($token)['exp']);
            $results['cart'] = $user->getCart();
            $results['idCart'] = $user->getIdCart();
            $user->onlyUser();
            $results['user'] = $user;
        } else
            $results['errors'] = $errors;
		// Par mesure de sécurité, retirer le role et le mdp du user dans la réponse.

        Router::json(json_encode($results));
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
	
	/**
	 * Récupère et valide les données de création d'un user reçues en POST.
	 *
	 * @return array l'instance du User et le tableau des erreurs.
	 */
	private static function processingUserCreation(array $data): array {
		// Initialiser les tableaux d'erreurs et de résultats.
		$errors = [];
		$results = [];
		// Créer un nouveau user.
		$user = new User();
		$user->roles = json_encode(["ROLE_USER"]);
        // Récupérer les données et tenter le login.
        $user->username = filter_var($_POST['username'] ??$data['username'], FILTER_SANITIZE_SPECIAL_CHARS)?: null;
        if(!$user->username || !$user->isValidUsername())
            $errors[] = UserControllerException::USERNAME_INVALID;
        $user->pwd = filter_var($_POST['pwd'] ?? $data['pwd'], FILTER_SANITIZE_SPECIAL_CHARS)?: null;
		if(!$user->pwd || mb_strlen($user->pwd) < 4 || mb_strlen($user->pwd) > 255)
			$errors[] = UserControllerException::PASSWORD_INVALID;
		else $user->pwd = password_hash($user->pwd, PASSWORD_DEFAULT);
		// Remplir la réponse.
		$results['user'] = $user;
		$results['errors'] = $errors;
		return $results;
	}
	/**
	 * Récupère et valide les données de mise à jour d'un user reçues en PUT.
	 *
	 * @return array l'instance du User et le tableau des erreurs.
	 */
	private static function processingUserUpdate(User $user): array {
		// Récupérer le tableau des données reçues en PUT et les mettre dans la "Super Globale" $_PUT.
		parse_str(file_get_contents("php://input"),$_PUT);
		// Initialiser les tableaux d'erreurs et de résultats.
		$errors = [];
		$results = [];
		// Vérifier les données.
		$user->email = filter_var($_PUT['email'], FILTER_SANITIZE_EMAIL) ?: null;
        if(!$user->email || !$user->isValidEmail())
			$errors[] = 'Email invalide';
		$user->lastName = filter_var($_PUT['lastName'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($user->lastName && !$user->isValidLastName())
			$errors[] = "Nom de famille invalide.";
		$user->firstName = filter_var($_PUT['firstName'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		if($user->firstName && !$user->isValidFirstName())
			$errors[] = "Prénom invalide.";
		$user->mobile = filter_var($_PUT['mobile'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		if($user->mobile && !$user->isValidMobile())
			$errors[] = "Numéro de téléphone erroné.";
		$user->postMail = filter_var($_PUT['postMail'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		if($user->postMail && !$user->isValidPostMail())
			$errors[] = "Adresse invalide.";
		$user->postMailComplement = filter_var($_PUT['postMailComplement'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		if($user->postMailComplement && !$user->isValidPostMailComplement())
			$errors[] = "Complément d'adresse invalide.";
		$user->zipCode = filter_var($_PUT['zipCode'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		if($user->zipCode && !$user->isValidZipCode())
			$errors[] = "Code postal invalide.";
		$user->city = filter_var($_PUT['city'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
		if($user->city && !$user->isValidCity())
			$errors[] = "Commune ou ville trop longue.";
		// Remplir la réponse.
		$results['user'] = $user;
		$results['errors'] = $errors;
		return $results;
	}
}
