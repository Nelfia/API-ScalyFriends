<?php

declare(strict_types=1);

namespace controllers;

use cfg\CfgApp;
use peps\core\Cfg;
use peps\core\Router;
use peps\jwt\JWT;
use entities\Product;
use entities\User;
use Exception;

/**
 * Classe 100% statique de gestion des produits.
 * @throws ProductControllerException
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
     * GET /api/products/(animals|materials|feeding)
     * Accès: PUBLIC.
     *
     * @param array $assocParams
     * @return void
     */
    public static function list(array $assocParams) : void {
        $filters = [];
        if($assocParams && $assocParams['category'] !== null) {
            $filters['category'] = (string)$assocParams['category'];
        }
        $filters['isVisible'] = true;
        // Récupérer tous les produits non archivés dans l'ordre alphabétique.
        $products = Product::findAllBy($filters);
        if(!$products) {
            Router::json(json_encode("erreur: aucun résultat."));
        }
        Router::json((json_encode($products)));
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
            Router::json(json_encode("Aucun produit trouvé."));
        // Envoyer la réponse en json.
        Router::json(json_encode($product));
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
            Router::json(UserControllerException::NO_LOGGED_USER);
        // Vérifier les droits d'accès du user.
        $user = User::getLoggedUser();
        if(!$user->isGranted("ROLE_ADMIN"))
            Router::json(json_encode(UserControllerException::ACCESS_DENIED));
        // Initialiser le tableau des résultats.
        $results = [];
        // Ajouter le token.
        $results['jwt_token'] = $token;
        // Initialiser le tableau des erreurs
        $errors = [];
        // Créer un produit.
        $product = new Product();
        //Récupérer les données reçues du client.
        $_POST = CfgApp::getInputData();
        $imageSrc = $_POST['imageSrc'];
        $fileName = self::createImage($imageSrc);
        $product->img = '/assets/img/' . $fileName;
        if(!$product->img)
            $errors[] = ProductControllerException::INVALID_IMG;
        // TODO: modifier la vérification des variables reçues en POST + refacto?
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
            if($product->gender !== "f" || mb_strlen($product->gender) > 1)
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
        $dateRef = date('Ymdhis');
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
                $errors[] = ProductControllerException::INVALID_DUPLICATE_REF;
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
     * Importe une image.
     * POST /api/image-upload
     * @return void
     */
    public static function imageUpload() : void {
        $fileName = self::createImage(CfgApp::getInputData()['image']);
        Router::json(json_encode($fileName));
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
        $product->img = ("/assets/img/" . filter_var($_PUT['img'], FILTER_SANITIZE_SPECIAL_CHARS)) ?: null;
        if($product->img && mb_strlen($product->img) > 255)
            $errors[] = ProductControllerException::INVALID_IMG;
        $product->price = filter_var($_PUT['price'], FILTER_VALIDATE_FLOAT) ?: null;
        if($product->price && ($product->price <= 0 || $product->price > 10000))
            $errors[] = ProductControllerException::INVALID_PRICE;
        $product->stock = filter_var($_PUT['stock'], FILTER_VALIDATE_INT) ?: null;
        if($product->stock && $product->stock < 0)
            $errors[] = ProductControllerException::INVALID_STOCK;
        $product->gender = filter_var($_PUT['gender'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product?->gender && (!($product?->gender === "F") && !($product?->gender === "M")))
            $errors[] = ProductControllerException::INVALID_GENDER;
        $product->species = filter_var($_PUT['species'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->species && (mb_strlen($product->species) > 200 || mb_strlen($product->species) < 3))
            $errors[] = ProductControllerException::INVALID_SPECIES; 
        $product->race = filter_var($_PUT['race'], FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->race && (mb_strlen($product->race) > 200))
                $errors[] = ProductControllerException::INVALID_RACE;
        $product->birth = filter_var((int)$_PUT['birth'], FILTER_VALIDATE_INT) ?: null;
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
        } else {
            // Remplir la réponse à envoyer au client.
            $success = false;
            $message = "Impossible de modifier le produit !";
            $results['errors'] = $errors;
        }
        $results['product'] = $product;
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

    /**
     * Convertir et enregistrer une image base64 dans le dossier assets/img/.
     *
     * @param string $img
     * @return string
     */
    private static function createImage(string $img): string
    {
        $path = "C:/Users/qdeca/OneDrive/Bureau/projets/ap_formation/DP/sf-api/assets/img/";
        $image_parts = explode(";base64,", $img);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        if($image_type !== 'png' && $image_type !== 'jpg' && $image_type !== 'jpeg')
            Router::json(ProductControllerException::INVALID_IMG);
        $image_en_base64 = base64_decode($image_parts[1]);
        $fileName = uniqid() . '.' . $image_type;
        $file = $path . $fileName;

        file_put_contents($file, $image_en_base64);
        return $fileName;
    }
}