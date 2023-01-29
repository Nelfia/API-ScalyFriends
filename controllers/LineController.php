<?php

declare(strict_types=1);

namespace controllers;

use entities\Command;
use entities\Line;
use peps\core\Router;
use peps\jwt\JWT;
use entities\Product;
use entities\User;
use Exception;

/**
 * Classe 100% statique de gestion des produits.
 */
final class LineController {
    /**
     * Constructeur privé
     */
    private function __construct() {}

    /**
     * Contrôle les données reçues & insère une nouvelle ligne en DB.
     * Se fait uniquement lorsque la commande a un status 'cart'.
     * Si le produit a déjà été inséré dans une des lignes de la commande, pas de création de ligne -> ajout du/des produits à la ligne existante si le stock le permet.
     *
     * POST /api/orders/{id}/lines
     * Accès: PUBLIC.
     *
     * @param array $assocParams
     * @return void
     */
    public static function create(array $assocParams) : void {
        // Initialiser le tableau des erreurs et des résultats.
        $errors = $results = [];
        // Ajouter le token dans les résultats.
        $results['jwt_token'] = JWT::isValidJWT();
        // Récupérer le user si logué.
        $user = User::getLoggedUser();
        // Créer une nouvelle ligne.
        $line = new Line();
        $line->idCommand = (int)$assocParams['id'];
        // Récupérer la commande.
        $command = $line->getCommand();
        $nbBeforeAdd = count($command->getLines());
        // Vérifier les droits d'accès du User.
        if($user?->isGranted('ROLE_ADMIN') || $user?->idUser !== $command?->idCustomer)
            Router::responseJson(false, "Vous n'êtes pas autorisé à accéder à cette page", $results);
        if(!$command)
            Router::responseJson(false, "Aucune commande trouvée.", $results);
        if($command->status !== 'cart')
            Router::responseJson(false, 'Vous ne pouvez plus modifier cette commande.', $results );
        // Récupérer et valider les données
        $line->idProduct = filter_input(INPUT_POST, 'idProduct', FILTER_VALIDATE_INT) ?: null;
        if(!$line->idProduct || $line->idProduct <= 0)
            $errors[] = "Produit invalide.";
        $existingLine = Line::findOneBy(['idCommand' => $line->idCommand, 'idProduct' => $line->idProduct])?: null;
        if($existingLine) $line = $existingLine;
        $line->quantity = $line->quantity + filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: null;
        if(!$line->quantity || !$line->isValidQuantity())
            $errors[] = "Quantité invalide.";
        $line->price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT) ?: null;
        if(!$line->price || !$line->isValidPrice())
            $errors[] = "Prix invalide.";
        if(!$errors) {
            // Si aucune erreur, persister.
            $line->persist();
            // Remplir la réponse à envoyer au client.
            $success = true;
            $message = "Nouvelle ligne créée avec succès";
            $results['nbLinesBeforeNewAdded'] = $nbBeforeAdd;
        } else {
            // Remplir la réponse à envoyer au client.
            $success = false;
            $message = "Impossible de créer la ligne !";
            $results['errors'] = $errors;            
        }
        $results['newLine'] = $line;
        // Envoyer la réponse au client.
        Router::responseJson($success, $message, $results);
    }
    /**
     * Modifie les données d'une ligne existante.
     * Modifie la quantité de produits insérés dans une ligne.
     *
     * PUT /api/orders/([1-9][0-9]*)/lines
     * Accès: ADMIN.
     * 
     * @param array $assocParams Tableau associatif des paramètres.
     * @return void
     */
    public static function update(array $assocParams) : void {
        // Initialiser le tableau des erreurs et des résultats.
        $errors = $results = [];
        // Ajouter le token dans les résultats.
        $results['jwt_token'] = JWT::isValidJWT();
        // Récupérer l'id de la commande et celui du produit de la ligne à modifier.

    }
    /**
     * Supprime une ligne de la commande.
     *
     * DELETE /api/orders/{idCommand}/lines/{idLine}
     * Accès: ADMIN.
     * 
     * @param array $assocParams Tableau des paramètres.
     * @return void
     */
    public static function delete(array $assocParams) : void {
    }
}