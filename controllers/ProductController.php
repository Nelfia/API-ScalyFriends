<?php

declare(strict_types=1);

namespace controllers;

use peps\core\Router;
use peps\jwt\JWT;
use entities\Product;
use entities\User;
use Exception;

require_once '.env.local';


/**
 * Classe 100% statique de gestion des produits.
 */
final class ProductController {
    /**
     * Constructeur privé
     */
    private function __construct() {}

    /**
     * Envoie liste de tous les produits.
     * 
     * GET /api/products
     * Accès: PUBLIC.
     *
     * @return void
     */
    public static function list() : void {
        // Récupérer tous les produits non archivés dans l'ordre alphabétique.
        $products = Product::findAllBy([ "isVisible" => true ], ['name' => 'ASC']);
        if($products){
            $success = true;
            $message = "Voici la liste de tous les produits";
            $results['nb'] = count($products);
            $results['products'] = $products;
        } else {
            $success = false;
            $message = "Aucun produit trouvé !";
        }
        // Renvoyer la réponse au client.
        Router::responseJson($success, $message, $results);
    }
    /**
     * Affiche le détail d'un produit.
     * 
     * GET /api/products/{id}
     * Accès: PUBLIC.
     *
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function show(array $assocParams) : void {
        // Récupérer l'id du produit passé en paramètre.
        $idProduct = (int)$assocParams['id'];
        // Récupérer le produit s'il est visible.
        $product = Product::findOneBy(['idProduct' => $idProduct, "isVisible" => true ]);
        // Si aucun produit trouvé, retourner l'erreur au client.
        if(!$product)
            Router::responseJson(false, "Aucun produit trouvé.", []);
        $results = [];
        $results['product'] = $product;
        // Envoyer la réponse en json.
        Router::responseJson(true, "Produit récupéré .", $results);
    }
    /**
     * Contrôle les données reçues & insère un nouveau produit en DB.
     *
     * POST /api/products
     * Accès: ADMIN.
     * 
     * @return void
     */
    public static function create() : void {
        // Vérifier si token et si valide.
        $token = JWT::isValidJWT();
        if(!$token) 
            Router::responseJson(false, "Vous devez être connecté pour accéder à cette page.");
        // Vérifier les droits d'accès du user.
        $user = User::getLoggedUser();
        if(!$user->isGranted("ROLE_ADMIN"))
            Router::responseJson(false, "Vous n'êtes pas autorisé à accéder à cette page.");
        // Initialiser le tableau des résultats.
        $results = [];
        // Ajouter le token.
        $results['jwt_token'] = $token;
        // Créer un produit.
        $product = new Product();
        // Initialiser le tableau des erreurs
        $errors = [];
        // Récupérer et valider les données
        $product->category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$product->category || mb_strlen($product->category) > 50)
            $errors[] = ProductControllerException::INVALID_CATEGORY;
        $product->type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$product->type || mb_strlen($product->type) > 100)
            $errors[] = ProductControllerException::INVALID_TYPE;
        $product->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$product->name || mb_strlen($product->name) > 255)
            $errors[] = ProductControllerException::INVALID_NAME;
        $product->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$product->description)
            $errors[] = ProductControllerException::INVALID_DESCRIPTION;
        $product->price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT) ?: null;
        if(!$product->price || $product->price <= 0 || $product->price > 10000)
            $errors[] = ProductControllerException::INVALID_PRICE;
        $product->stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT) ?: null;
        if(!$product->stock || $product->stock < 0)
            $errors[] = ProductControllerException::INVALID_STOCK;
        $product->gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->gender) {
            if($product->gender !== "f" || $product->gender !== "m" || mb_strlen($product->gender) > 1)
                $errors[] = ProductControllerException::INVALID_GENDER;
        }
        $product->species = filter_input(INPUT_POST, 'species', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$product->species || mb_strlen($product->species) > 200)
            $errors[] = ProductControllerException::INVALID_SPECIES; 
        $product->race = filter_input(INPUT_POST, 'race', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->race && mb_strlen($product->race) > 200)
                $errors[] = ProductControllerException::INVALID_RACE;
        $product->birth = filter_input(INPUT_POST, 'birth', FILTER_VALIDATE_INT) ?: null;
        if($product->birth){
            if ($product->birth < 2010|| $product->birth > date('Y'))
                $errors[] = ProductControllerException::INVALID_BIRTH;
        }
        $product->requiresCertification = filter_input(INPUT_POST, 'requiresCertification', FILTER_VALIDATE_BOOL) !== null ?: null;
        if($product->requiresCertification === null)
            $errors[] = ProductControllerException::INVALID_REQUIRES_CERTIFICATION;
        $product->dimensionsMax = filter_input(INPUT_POST, 'dimensionsMax', FILTER_VALIDATE_FLOAT) ?: null;
        if(!$product->dimensionsMax || $product->dimensionsMax <= 0 || $product->dimensionsMax > 10000)
            $errors[] = ProductControllerException::INVALID_DIMENSION;
        $product->dimensionsUnit = filter_input(INPUT_POST, 'dimensionsUnit', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$product->dimensionsUnit || mb_strlen($product->dimensionsUnit) > 10)
            $errors[] = ProductControllerException::INVALID_DIMENSION_UNIT;
        $product->specification = filter_input(INPUT_POST, 'specification', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$product->specification || mb_strlen($product->specification) > 50)
            $errors[] = ProductControllerException::INVALID_SPECIFICATION;
        $product->specificationValue = filter_input(INPUT_POST, 'specificationValue', FILTER_VALIDATE_FLOAT) ?: null;
        if($product->specificationValue && $product->specificationValue <= 0 )
            $errors[] = ProductControllerException::INVALID_SPECIFICATION_VALUE;
        $product->specificationUnit = filter_input(INPUT_POST, 'specificationUnit', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
            if(!$product->specificationUnit || mb_strlen($product->specificationUnit) > 3)
                $errors[] = ProductControllerException::INVALID_SPECIFICATION_UNIT;
        // Générer une référence unique.
        $catRef = strtoupper(substr($product->category, 0, 4));
        $dateRef = date('Ymdhms');
        $nbRef = substr((string)Product::getCount(),-3);
        $product->ref = $catRef . $dateRef . $nbRef;
        // Récupérer l'idUser du créateur du produit.
        $payload = JWT::getPayload($token);
        $product->idAuthor = $payload['user_id'];
        // Faire apparaître le produit.
        $product->isVisible = true;
        // Si aucune erreur, persister le produit.
        if(!$errors) {
            // Tenter de persister en tenant compte du potentiel doublon de référence.
            try {
                $product->persist();
            } catch (Exception) {
                $errors[] = "La référence existe déjà.";
            }
            // Si toujours pas d'erreur.
            if(!$errors) {
            // Remplir la réponse à envoyer au client.
            $success = true;
            $message = "Produit créé avec succès";
            $results['product'] = $product;
            }
        } else {
            // Remplir la réponse à envoyer au client.
            $success = false;
            $message = "Impossible de créer produit !";
            $results['errors'] = $errors;            
            $results['product'] = $product;            
        }

        // Envoyer la réponse au client.
        Router::responseJson($success, $message, $results);
    }
    /**
     * Modifie les données d'un produit existant.
     *
     * PUT /api/users/{id}
     * Accès: ADMIN.
     * 
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function update(array $assocParams) : void {
        // Vérifier si token et si valide.
        $token = JWT::isValidJWT();
        if(!$token) 
            Router::responseJson(false, "Vous devez être connecté pour accéder à cette page.");
        // Vérifier les droits d'accès du user.
        $user = User::getLoggedUser();
        if(!$user->isGranted("ROLE_ADMIN"))
            Router::responseJson(false, "Vous n'êtes pas autorisé à accéder à cette page.");
        // Initialiser le tableau des résultats.
        $results = [];
        // Ajouter le token.
        $results['jwt_token'] = $token;
        // Récupérer l'id du produit passé en paramètre.
        $idProduct = (int)$assocParams['id'];
        // Récupérer le produit.
        $product = Product::findOneBy(['idProduct' => $idProduct ]);
        // Initialiser le tableau des erreurs
        $errors = [];
        // Récupérer le tableau des données reçues en PUT et les mettre dans la Super Globale $_PUT.
		parse_str(file_get_contents("php://input"),$_PUT);
        // Récupérer et valider les données
        $product->category = filter_var($_PUT['category'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->category && (mb_strlen($product->category) > 50 || mb_strlen($product->category) < 6))
            $errors[] = ProductControllerException::INVALID_CATEGORY;
        $product->type = filter_var($_PUT['type'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->type && (mb_strlen($product->type) > 100 || mb_strlen($product->type) < 4))
            $errors[] = ProductControllerException::INVALID_TYPE;
        $product->name = filter_var($_PUT['name'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->name && (mb_strlen($product->name) > 255 || mb_strlen($product->name) < 3))
            $errors[] = ProductControllerException::INVALID_NAME;
        $product->description = filter_var($_PUT['description'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->description && mb_strlen($product->description) < 10)
            $errors[] = ProductControllerException::INVALID_DESCRIPTION;
        $product->price = filter_var($_PUT['price'], FILTER_VALIDATE_FLOAT) ?: null;
        if($product->price && ($product->price <= 0 || $product->price > 10000))
            $errors[] = ProductControllerException::INVALID_PRICE;
        $product->stock = filter_var($_PUT['stock'], FILTER_VALIDATE_INT) ?: null;
        if($product->stock && $product->stock < 0)
            $errors[] = ProductControllerException::INVALID_STOCK;
        $product->gender = filter_var($_PUT['gender'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->gender && ($product->gender !== "f" || $product->gender !== "m" || mb_strlen($product->gender) > 1)) 
                $errors[] = ProductControllerException::INVALID_GENDER;
        $product->species = filter_var($_PUT['species'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->species && (mb_strlen($product->species) > 200 || mb_strlen($product->species) < 3))
            $errors[] = ProductControllerException::INVALID_SPECIES; 
        $product->race = filter_var($_PUT['race'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->race && (mb_strlen($product->race) > 200))
                $errors[] = ProductControllerException::INVALID_RACE;
        $product->birth = filter_var($_PUT['birth'], FILTER_VALIDATE_INT) ?: null;
        if($product->birth && ($product->birth < 2010|| $product->birth > date('Y')))
                $errors[] = ProductControllerException::INVALID_BIRTH;
        $product->requiresCertification = filter_var($_PUT['requiresCertification'], FILTER_VALIDATE_BOOL) !== null ?: null;
        if($product->requiresCertification === null)
            $errors[] = ProductControllerException::INVALID_REQUIRES_CERTIFICATION;
        $product->dimensionsMax = filter_var($_PUT['dimensionsMax'], FILTER_VALIDATE_FLOAT) ?: null;
        if($product->dimensionsMax && ($product->dimensionsMax <= 0 || $product->dimensionsMax > 10000))
            $errors[] = ProductControllerException::INVALID_DIMENSION;
        $product->dimensionsUnit = filter_var($_PUT['dimensionsUnit'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->dimensionsUnit && mb_strlen($product->dimensionsUnit) > 10)
            $errors[] = ProductControllerException::INVALID_DIMENSION_UNIT;
        $product->specification = filter_var($_PUT['specification'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->specification && mb_strlen($product->specification) > 50)
            $errors[] = ProductControllerException::INVALID_SPECIFICATION;
        $product->specificationValue = filter_var($_PUT['specificationValue'], FILTER_VALIDATE_FLOAT) ?: null;
        if($product->specificationValue && $product->specificationValue <= 0 )
            $errors[] = ProductControllerException::INVALID_SPECIFICATION_VALUE;
        $product->specificationUnit = filter_var($_PUT['specificationUnit'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
            if($product->specificationUnit && mb_strlen($product->specificationUnit) > 3)
                $errors[] = ProductControllerException::INVALID_SPECIFICATION_UNIT;
        // Si aucune erreur, persister le produit.
        if(!$errors) {
            $product->persist();
            // Remplir la réponse à envoyer au client.
            $success = true;
            $message = "Produit a bien été mis à jour";
            $results['product'] = $product;
        } else {
            // Remplir la réponse à envoyer au client.
            $success = false;
            $message = "Impossible de modifier le produit !";
            $results['errors'] = $errors;            
            $results['product'] = $product;            
        }
        // Envoyer la réponse au client.
        Router::responseJson($success, $message, $results);
    }
    /**
     * "Supprime" un produit.
     * Passe 'isVisible' à false.
     *
     * DELETE /products/{id}
     * Accès: ADMIN.
     * 
     * @param array $assocParams Tableau des paramètres.
     * @return void
     */
    public static function delete(array $assocParams) : void {
        // Vérifier si User logué.
        $user = User::getLoggedUser();
        if(!$user) 
            Router::responseJson(false, "Vous devez être connecté pour accéder à cette page.");
        // Vérifier si a les droits d'accès.
        if(!$user->isGranted("ROLE_ADMIN"))
            Router::responseJson(false, "Vous n'êtes pas autorisé à accéder à cette page.");
        $product = Product::findOneBy(['idProduct' => (int)$assocParams['id']]);
        $product->isVisible = false;
        $product->persist();
        $results = [];
        $results['jwt_token'] = JWT::isValidJWT();
        Router::responseJson(true, "Le produit a été supprimé.", $results);
    }
}