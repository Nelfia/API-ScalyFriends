<?php

declare(strict_types=1);

namespace controllers;


use peps\core\Cfg;
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
     *
     * @return void
     */
    public static function list() : void {
        // Vérifier si token et si valide.
        $token = JWT::isValidJWT();
        // Vérifier les droits d'accès du user.
        $user = User::getLoggedUser();
        if(!$user->isGranted("ROLE_USER"))
            exit('User non autorisé !');
        else $user->isGranted("ROLE_USER");
        // Initialiser le tableau des résultats.
        $results = array();
        // Ajouter le token.
        $results['jwt_token'] = $token;
        if($token){
            // Récupérer tous les produits dans l'ordre alphabétique.
            $products = Product::findAllBy([], ['name' => 'ASC']);
            if($products){
                $success = true;
                $message = "Voici la liste de tous les produits";
                $results['nb'] = count($products);
                $results['products'] = $products;
            } else {
                $success = false;
                $message = "Aucun produit trouvé !";
            }
        } else {
            $success = false;
            $message = "Token invalide !";
        }
        // Renvoyer la réponse au client.
        Router::responseJson($success, $message, $results);
    }

    /**
     * Affiche le détail d'un produit.
     * 
     * GET /api/products/([1-9]-[0-9]*)
     *
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function show(array $assocParams) : void {
        // Récupérer l'idProduct.
        $idProduct = (int)$assocParams['id'];
        // Instancier le produit.
        $product = Product::findOneBy(['idProduct' => $idProduct]);
        // Hydrater le produit.
        if(!$product){
            // Si aucun produit trouvé, retourner l'erreur au client.
            $response['success'] = false;
            $response['http_code'] = http_response_code();
            $response['message'] = "Produit non trouvé";
            Router::json(json_encode($response));
        }
        // Définir le prix formatté.
        $formattedPrice = Cfg::get('appLocale2dec')->format($product->price);
        // Envoyer la réponse en json.
        $response['success'] = true;
        $response['http_code'] = http_response_code();
        $response['message'] = "Produit récupéré !";
        $response['results']['product'] = $product;
        Router::json(json_encode($response));
    }

    /**
     * Contrôle les données reçues & insère un nouveau produit en DB.
     *
     * POST /api/products
     * 
     * @return void
     */
    public static function create() : void {
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

        // $product->requiresCertification = filter_input(INPUT_POST, 'requiresCertification', FILTER_VALIDATE_BOOL) ?: null;
        // if($product->requiresCertification === null)
        //     $errors[] = ProductControllerException::INVALID_REQUIRES_CERTIFICATION;

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
                
        // $product->ref = filter_input(INPUT_POST, 'ref', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        // if(!$product->ref || mb_strlen($product->ref) > 10)
        //     $errors[] = ProductControllerException::INVALID_REF;

        // Initialiser le tableau des résultats.
        $results = array();
        // Vérifier si token et si valide.
        $token = JWT::isValidJWT();
        // Insérer le token dans le tableau des résultats.
        $results['jwt_token'] = $token;
        if($token){
            $payload = JWT::getPayload($token);
            $product->idAuthor = $payload['user_id'];
            // Si aucune erreur, persister le produit.
            if(!$errors) {
                $product->persist();
                // Remplir la réponse à envoyer au client.
                $success = true;
                $message = "Produit créé avec succès";
                $results['product'] = $product;
            } else {
                // Remplir la réponse à envoyer au client.
                $success = false;
                $message = "Impossible de créer produit !";
                $results['errors'] = $errors;            
                $results['product'] = $product;            
            }
        } else {
            // Remplir la réponse à envoyer au client.
            $success = false;
            $message = "Token invalide ! Vous devez être connecté pour pouvoir créer un produit!";
        }
        // Envoyer la réponse au client.
        Router::responseJson($success, $message, $results);
    }

    /**
     * Affiche le formulaire de modification d'un produit existant.
     *
     * GET /product/update/{idProduct}
     * 
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    // public static function update(array $assocParams) : void {
    //     // Créer un produit.
    //     $product = new Product((int)$assocParams['idProduct']);
    //     // Si l'hydratratation échoue, erreur 404.
    //     if(!$product->hydrate()) 
    //         Router::render('error404.php');
    //     // Récupérer les catégories pour peupler le menu déroulant.
    //     $categories = Category::findAllBy([], ['name' => 'ASC']);
    //     // Rendre la vue
    //     Router::render('editProduct.php',['product' => $product, 'categories' => $categories]);
    // }

    /**
     * Sauvegarde le produit en DB.
     * 
     * POST /product/save
     *
     * @return void
     */
    // public static function save() : void {
    //     // Créer un produit.
    //     $product = new Product();
    //     // Initialiser le tableau des erreurs
    //     $errors = [];
    //     // Récupérer et valider les données
    //     $product->idProduct = filter_input(INPUT_POST, 'idProduct', FILTER_VALIDATE_INT) ?: null;
    //     $product->idCategory = filter_input(INPUT_POST, 'idCategory', FILTER_VALIDATE_INT) ?: null;
    //     if(!$product->idCategory || $product->idCategory <= 0)
    //         $errors[] = ProductControllerException::INVALID_CATEGORY;
    //     $product->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
    //     if(!$product->name || mb_strlen($product->name) > 50)
    //         $errors[] = ProductControllerException::INVALID_NAME;
    //     $product->ref = filter_input(INPUT_POST, 'ref', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
    //     if(!$product->ref || mb_strlen($product->ref) > 10)
    //         $errors[] = ProductControllerException::INVALID_REF;
    //     $product->price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT) ?: null;
    //     if(!$product->price || $product->price <= 0 || $product->price > 10000)
    //         $errors[] = ProductControllerException::INVALID_PRICE;
    //     // Si aucune erreur, persister le produit et rediriger
    //     if(!$errors){
    //         // Persister en tenant compte des éventuels doublons de référence
    //         try {
    //             $product->persist();
    //         } catch (Exception) {
    //             $errors[] = ProductControllerException::INVALID_DUPLICATE_REF;
    //         }
    //         // Si toujours aucune erreur, rediriger.
    //         if(!$errors)
    //         Router::redirect('/');
    //     }
    //     // Récupérer les catégories pour peupler le menu déroulant.
    //     $categories = Category::findAllBy([], ['name' => 'ASC']);
    //     // Rendre la vue.
    //     Router::render('editProduct.php', ['product' => $product, 'categories' => $categories, 'errors' => $errors]);
    // }

    // /**
    //  * Supprime un produit.
    //  *
    //  * GET /product/remove/{idProduct}
    //  * 
    //  * @param array $assocParams Tableau des paramètres.
    //  * @return void
    //  */
    // public static function remove(array $assocParams) : void {
    //     // Créer le produit tout en récupérant son idProduct puis le supprimer en DB
    //     (new Product((int)$assocParams['idProduct']))->remove();
    //     // Rediriger.
    //     Router::redirect('/');
    // }
}