<?php

declare(strict_types=1);


namespace controllers;

use entities\Favoris;
use entities\User;
use Exception;
use peps\core\Router;
use peps\jwt\JWT;

require_once '.env.local';

/**
 * Classe 100% statique de gestion des utilisateurs.
 */
final class UserController {
    /**
     * Constructeur privé
     */
    private function __construct() {}
    
    /**
     * Affiche le formulaire de création.
     * 
     * POST /api/users
     *
     * @return void
     */
    public static function create(array $assocParams) : void {
        // Rendre la vue.
        Router::render('editUser.php', $assocParams);
    }

    /**
     * Affiche la vue de saisie des identifiants de connexion.
     * 
     * GET/user/signin
     *
     * @return void
     */
    public static function signin() : void {
        // Rendre la vue.
        Router::render('signin.php');
    }

    /**
     * Tente de loguer un utilisateur en session.
     * 
     * POST /users/login
     *
     * @return void
     */
    public static function login() : void {
        // Créer un user
        $user = new User();
        // Initialiser le tableau des erreurs
        $errors = [];
        // Initialiser la réponse JSON.
        $response = array();
        $token='';
        // On vérifie si on reçoit un token
        if(JWT::isValidJWT())
            $token = 
        if(isset($_SERVER['Authorization']))
            $token = trim($_SERVER['Authorization']);
        elseif(isset($_SERVER['HTTP_AUTHORIZATION']))
            $token = trim($_SERVER['HTTP_AUTHORIZATION']);
        elseif(function_exists('apache_request_headers')){
            $requestHeaders = apache_request_headers();
            if(isset($requestHeaders['Authorization'])){
                $token = trim($requestHeaders['Authorization']);
            }
        }
        // On vérifie que le token reçu commence par 'Bearer'
        if($token !== '' && preg_match('/Bearer\s(\S+)/', $token, $matches)){
            // On extrait le token
            $token = str_replace('Bearer ', '', $token);
            if(JWT::isClean($token)) {
                if(JWT::check($token, SECRET)){
                    if(!JWT::isExpired($token)){
                        $payload = JWT::getPayload($token);
                        $user->idUser = $payload['user_id'];
                        $user->hydrate();
                        $user->loggedUser = $user;
                        $response['success'] = true;
                        $response['http_code'] = http_response_code();
                        $response['message'] = "Le user est logué!";
                        $response['results']['user'] = $user;
                        Router::json(json_encode($response));
                        exit;
                    }
                }
            }
        }
        // Récupérer les données et tenter le login
        $user->username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $pwd = filter_input(INPUT_POST, 'pwd', FILTER_SANITIZE_SPECIAL_CHARS);

        // Si login OK, générer le JWT et renvoyer réponse en Json.
        if ($user->login($pwd)) {
            // On crée le contenu (payload)
            $payload = [
                'user_id' => $user->idUser,
                'roles' => $user->role
            ];
            $token = JWT::generate([], $payload, SECRET);
            $response['success'] = true;
            $response['http_code'] = http_response_code();
            $response['message'] = "Le user est logué!";
            $response['results']['user'] = $user;
            $response['results']['jwt_token'] = $token;
        } else{
            $response['success'] = false;
            $response['http_code'] = http_response_code();
            $response['message'] = "Login failed !";
            $response['results']['user'] = $user;
            $response['results']['errors'] = $errors;
        }
        Router::json(json_encode($response));
    }
    
    /**
     * Génère un JWT.
     * 
     * POST /user/authenticate.php
     *
     * @return void
     */
    public static function auth() : void {
            Router::render('authenticate.php', []);
    }

    /**
     * Délogue l'utilisateur en session.
     * 
     * POST /user/logout
     *
     * @return void
     */
    public static function logout() : void {
        // Détruire les variables de session.
        session_destroy();
        // Rediriger
        Router::redirect('/user/signin');
    }

    /**
     * Affiche la page d'édition du user.
     * 
     * GET /edit/{id}
     *
     * @return void
     */
    public static function edit(array $assocParams) : void {
        $user = User::findOneBy(["idUser" => (int)$assocParams['idUser']]);
        var_dump($user);
        // Rendre la vue.
        Router::render('editUser.php', ['user' => $user]);
    }

    // /**
    //  * Retourne le tableau des favoris du user.
    //  * 
    //  * GET /user/favoris/{id}
    //  *
    //  * @return void
    //  */
    // public static function favoris(array $assocParams = []) : void {
    //     $favoris = Favoris::findAllBy(["idUser" => (int)$assocParams['idUser']]);
    //     // var_dump($user);
    //     // Rendre la vue.
    //     Router::json(json_encode($favoris));
    // }

    // /**
    //  * Affiche les commandes passées par le user.
    //  * 
    //  * GET /user/orders/{id}
    //  *
    //  * @return void
    //  */
    // public static function orders(array $assocParams = []) : void {
    //     $user = User::findOneBy(["idUser" => (int)$assocParams['idUser']]);
    //     // Rendre la vue.
    //     Router::json(json_encode($user->getCommandes()));
    // }

    // /**
    //  * Sauvegarde le user en DB.
    //  * 
    //  * POST /user/save
    //  *
    //  * @return void
    //  */
    // public static function save() : void {
    //     // Créer un user.
    //     $user = new User();
    //     // Initialiser le tableau des erreurs
    //     $errors = [];
    //     // Récupérer et valider les données
    //     $user->idUser = filter_input(INPUT_POST, 'idUser', FILTER_VALIDATE_INT) ?: null;
    //     $user->username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
    //     if(!$user->username || mb_strlen($user->username) > 50)
    //     $errors[] = UserControllerException::INVALID_USERNAME;
    //     $user->pwd = password_hash(filter_input(INPUT_POST, 'pwd', FILTER_SANITIZE_SPECIAL_CHARS), PASSWORD_DEFAULT) ?: null;
    //     if(!$user->pwd || mb_strlen($user->pwd) > 255)
    //     $errors[] = UserControllerException::INVALID_PWD;
    //     $user->email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?: null;
    //     if(!$user->email || mb_strlen($user->email) > 50)
    //     $errors[] = UserControllerException::INVALID_EMAIL;
    //     $user->nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
    //     if(!$user->nom || mb_strlen($user->nom) > 50)
    //     $errors[] = UserControllerException::INVALID_NAME;
    //     $user->prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
    //     if(!$user->prenom || mb_strlen($user->prenom) > 50)
    //     $errors[] = UserControllerException::INVALID_FIRSTNAME;
    //     $user->telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
    //     if(!$user->telephone || mb_strlen($user->telephone) > 10)
    //     $errors[] = UserControllerException::INVALID_MOBILE;
    //     $user->adressePostale = filter_input(INPUT_POST, 'adressePostale', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
    //     if(!$user->adressePostale || mb_strlen($user->adressePostale) > 50)
    //     $errors[] = UserControllerException::INVALID_POSTMAIL;
    //     $user->complementAdresse = filter_input(INPUT_POST, 'complementAdresse', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
    //     if($user->complementAdresse && mb_strlen($user->complementAdresse) > 50)
    //     $errors[] = UserControllerException::INVALID_POSTMAIL_COMPLEMENT;
    //     $user->codePostal = filter_input(INPUT_POST, 'codePostal', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
    //     if(!$user->codePostal || mb_strlen($user->codePostal) > 5)
    //     $errors[] = UserControllerException::INVALID_POSTCODE;
    //     $user->commune = filter_input(INPUT_POST, 'commune', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
    //     if(!$user->commune || mb_strlen($user->commune) > 50)
    //     $errors[] = UserControllerException::INVALID_CITY;
    //     $msg = $user->idUser === null ? "Votre compte a bien été créé." : "Votre compte a bien été mis à jour.";
    //     $user->role ?? json_encode("ROLE_USER");
    //     // Si aucune erreur, persister le produit et rediriger
    //     if(!$errors){
    //         // Persister en tenant compte des éventuels doublons de username & email
    //         try {
    //             $user->persist();
    //         } catch (Exception) {
    //             $errors[] = UserControllerException::INVALID_DUPLICATE_USERNAME;
    //         }
    //         // Si toujours aucune erreur, rediriger.
    //         if(!$errors) {
    //             Router::json(json_encode($msg));
    //         }
    //     }
    //     // Rendre la vue.
    //     Router::render('editUser.php', ['user' => $user, 'errors' => $errors]);
    // }

}
