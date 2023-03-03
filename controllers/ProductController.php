<?php

declare(strict_types=1);

namespace controllers;

use classes\Utils;
use entities\Product;
use entities\User;
use peps\core\Router;

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
     * Enregistre un produit en DB.
     *
     * POST /api/products
     *
     * Accès Admin.
     *
     * @return void
     */
    public static function save() : void {
        // Vérifier si user connecté.
        $user = User::getLoggedUser();
        if(!$user)
            Router::json(json_encode(UserControllerException::NO_LOGGED_USER));
        // Vérifier les droits d'accès du user.
        if(!$user->isGranted("ROLE_ADMIN"))
            Router::json(json_encode(UserControllerException::ACCESS_DENIED));

        // Créer un produit.
        $product = new Product();
        // Initialiser les tableaux de résultats et d'erreurs.
        $errors = [];
        //Récupérer les données reçues du client.
        $inputData = Utils::getInputData();
        //Récupérer l'image reçue en base64.
        $imageSrc = $inputData['imageSrc'];
        // Convertir l'image et l'enregistrer dans le dossier assets/img/ du serveur.
        $fileName = Utils::createImage($imageSrc);
        // Récupérer le nom du fichier à enregistrer en DB.
        $product->img = '/assets/img/' . $fileName;
        if(!$product->img)
            $errors[] = ProductControllerException::INVALID_IMG;

        // Récupérer et valider les données saisies par l'utilisateur.
        $receivedProduct = $inputData['product'];
        if (isset($receivedProduct->idProduct)) {
            $product->idProduct = filter_var($receivedProduct->idProduct, FILTER_VALIDATE_INT) ?: null;
            if (!$product->idProduct || $product->idProduct <= 0)
                $errors[] = ProductControllerException::INVALID_ID;
        }

        $product->category = filter_var($receivedProduct->category, FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$product->category || (mb_strlen($product->category) > 50 || mb_strlen($product->category) < 6))
            $errors[] = ProductControllerException::INVALID_CATEGORY;

        $product->type = filter_var($receivedProduct->type, FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$product->type || (mb_strlen($product->type) > 100 || mb_strlen($product->type) < 4))
            $errors[] = ProductControllerException::INVALID_TYPE;

        $product->name = filter_var($receivedProduct->name, FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$product->name || (mb_strlen($product->name) > 255 || mb_strlen($product->name) < 3))
            $errors[] = ProductControllerException::INVALID_NAME;

        $product->description = filter_var($receivedProduct->description, FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$product->description || mb_strlen($product->description) < 10)
            $errors[] = ProductControllerException::INVALID_DESCRIPTION;

        $product->price = filter_var($receivedProduct->price, FILTER_VALIDATE_FLOAT) ?: null;
        if(!$product->price || ($product->price <= 0 || $product->price > 10000))
            $errors[] = ProductControllerException::INVALID_PRICE;

        $product->stock = filter_var($receivedProduct->stock, FILTER_VALIDATE_INT) ?: null;
        if(!$product->stock || $product->stock < 0)
            $errors[] = ProductControllerException::INVALID_STOCK;

        $product->gender = filter_var($receivedProduct->gender, FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->gender && (!($product->gender === "F") && !($product->gender === "M")))
            $errors[] = ProductControllerException::INVALID_GENDER;

        $product->species = filter_var($receivedProduct->species, FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if(!$product->species || (mb_strlen($product->species) > 200 || mb_strlen($product->species) < 3))
            $errors[] = ProductControllerException::INVALID_SPECIES_OR_BRAND;

        $product->race = filter_var($receivedProduct->race, FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->race && (mb_strlen($product->race) > 200))
            $errors[] = ProductControllerException::INVALID_RACE;

        $product->birth = filter_var((int)$receivedProduct->birth, FILTER_VALIDATE_INT) ?: null;
        if($product->birth && ($product->birth < 2010|| $product->birth > date('Y')))
            $errors[] = ProductControllerException::INVALID_BIRTH;

        $product->requiresCertification = filter_var($receivedProduct->requiresCertification, FILTER_VALIDATE_BOOL) !== null ?: null;
        if($product->requiresCertification === null)
            $errors[] = ProductControllerException::INVALID_REQUIRES_CERTIFICATION;

        $product->dimensionsMax = filter_var($receivedProduct->dimensionsMax, FILTER_VALIDATE_FLOAT) ?: null;
        if($product->dimensionsMax && ($product->dimensionsMax <= 0 || $product->dimensionsMax > 10000))
            $errors[] = ProductControllerException::INVALID_DIMENSION;

        $product->dimensionsUnit = filter_var($receivedProduct->dimensionsUnit, FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->dimensionsUnit && mb_strlen($product->dimensionsUnit) > 10)
            $errors[] = ProductControllerException::INVALID_DIMENSION_UNIT;

        $product->specification = filter_var($receivedProduct->specification, FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->specification && mb_strlen($product->specification) > 50)
            $errors[] = ProductControllerException::INVALID_SPECIFICATION;

        $product->specificationValue = filter_var($receivedProduct->specificationValue, FILTER_VALIDATE_FLOAT) ?: null;
        if($product->specificationValue && $product->specificationValue <= 0 )
            $errors[] = ProductControllerException::INVALID_SPECIFICATION_VALUE;

        $product->specificationUnit = filter_var($receivedProduct->specificationUnit, FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        if($product->specificationUnit && mb_strlen($product->specificationUnit) > 3)
            $errors[] = ProductControllerException::INVALID_SPECIFICATION_UNIT;
        // Si aucune erreur, ajouter les informations manquantes et persister le produit.
        if(!$errors) {
            $product->idAuthor = $user->idUser;
            // Générer une référence unique.
            $catRef = strtoupper(substr($product->category, 0, 4));
            $dateRef = date('Ymdhis');
            $nbRef = substr((string)Product::getCount(),-3);
            $product->ref = $catRef . $dateRef . $nbRef;
            // Faire apparaître le produit.
            $product->isVisible = true;
            // Persister le produit.
            $product->persist();
            $categoryProducts = Product::findAllBy(['isVisible' => true, 'category' => $product->category]);
            Router::json(json_encode($categoryProducts));
        }
        Router::json(json_encode($errors));
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
            Router::json(json_encode(UserControllerException::NO_LOGGED_USER));
        // Vérifier si a les droits d'accès.
        if(!$user->isGranted("ROLE_ADMIN"))
            Router::json(json_encode(UserControllerException::ACCESS_DENIED));
        $product = Product::findOneBy(['idProduct' => (int)$assocParams['id']]);
        $product->isVisible = false;
        $product->persist();
        $categoryProducts = Product::findAllBy(['isVisible' => true, 'category' => $product->category]);
        Router::json(json_encode($categoryProducts));
    }



}