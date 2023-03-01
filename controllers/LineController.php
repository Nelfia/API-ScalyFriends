<?php

declare(strict_types=1);

namespace controllers;

use classes\Utils;
use entities\Command;
use entities\Line;
use entities\User;
use peps\core\Router;

/**
 * Classe 100% statique de gestion des lignes.
 */
final class LineController {
    /**
     * Constructeur privé
     */
    private function __construct() {}

    /**
     * Contrôle l'accès et les données reçues & insère une nouvelle ligne en DB.
     * Possible UNIQUEMENT lorsque la commande a un status 'cart'.
     *
     * Si le produit a déjà été inséré dans une des lignes de la commande,
     * pas de création de ligne -> ajout du/des produits à la ligne existante si
     * le stock le permet (sinon stock max).
     *
     * Envoie du panier au client.
     *
     * POST /api/orders/{idCommand}/lines
     * PUT /api/orders/{idCommand}/lines
     * Accès: USER.
     *
     * @param array $assocParams
     * @return void
     */
    public static function updateLine(array $assocParams) : void {
        // Créer une nouvelle ligne.
        $line = new Line();
        $line->idCommand = (int)$assocParams['idCommand'];
        $verb = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_SPECIAL_CHARS) ?: filter_var($_SERVER['REQUEST_METHOD'], FILTER_SANITIZE_SPECIAL_CHARS);
        $line = self::processingDataLine($line, ($verb === "POST"));
        $panier = $line->getCommand();
        $panier->getLines();
        $panier->lastChange = date('Y-m-d H:i:s');
        $panier->persist();
        Router::json(json_encode($panier));
    }

    /**
     * Supprime une ligne de la commande.
     * Envoie le nouveau panier au client.
     *
     * DELETE /api/orders/{idCommand}/lines/{idLine}
     * Accès: USER.
     * 
     * @param array $assocParams Tableau des paramètres.
     * @return void
     */
    public static function removeLine(array $assocParams) : void {
        // Vérifier si commande existante et si status "cart"
        $line = Line::findOneBy(['idLine' => (int)$assocParams['idLine']]);
        $command = $line->getCommand();
        if(!$command->isCart())
            Router::json(CommandControllerException::NO_CHANGE_ALLOWED);
        // Vérifier si user logué est autorisé à modifier le panier
        $user = User::getLoggedUser();
        if(!$user || !$user->isGranted("ROLE_USER") || ($user->idUser !== $command->idCustomer))
            Router::json(UserControllerException::ACCESS_DENIED);
        $line->remove();
        $newCart = Command::findOneBy(['idCommand' => $command->idCommand]);
        $newCart->getLines();
        Router::json(json_encode($newCart));
    }


    /**
     * Vérifie les droits d'accès et les données reçues par le client.
     * Si pas d'erreurs: persiste et retourne la ligne.
     * Sinon, retourne le tableau des erreurs.
     *
     * @param Line $line
     * @param bool $addingLine
     * @return Line|array
     */
    static function processingDataLine(Line $line, bool $addingLine): ?Line {
        // Récupérer le user si logué.
        $user = User::getLoggedUser();
        // Récupérer la commande.
        $panier = $line->getCommand();
        // Vérifier les droits d'accès du User.
        if(!$user || !$user->isGranted('ROLE_USER') || $user->idUser != $panier?->idCustomer)
            Router::json(UserControllerException::ACCESS_DENIED);
        if(!$panier)
            Router::json(CommandControllerException::INVALID_ID);
        if($panier->status !== 'cart')
            Router::json(CommandControllerException::NO_CHANGE_ALLOWED);
        //Initialiser le tableau des erreurs.
        $errors = [];
        // Récupérer et valider les données
        $inputValues = Utils::getInputData();
        $line->idProduct = filter_var((int)$inputValues['idProduct'], FILTER_VALIDATE_INT) ?: null;
        if(!$line->idProduct || $line->idProduct <= 0)
            $errors[] = LineControllerException::INVALID_PRODUCT;
        if($addingLine)
            $existingLine = Line::findOneBy(['idCommand' => $line->idCommand, 'idProduct' => $line->idProduct])?: null;
        else {
            $line->idLine = filter_var($inputValues['idLine'], FILTER_VALIDATE_INT) ?: null;
            $existingLine = line::findOneBy(['idLine' => $line->idLine]);
        }
        $line->getProduct();
        if($existingLine) $line = $existingLine;
        $line->quantity = ($addingLine? $line->quantity : 0) + filter_var($inputValues['quantity'], FILTER_VALIDATE_INT) ?: null;
        $product = $line->getProduct();
        if($line->quantity > $product->stock)
            $line->quantity = $product->stock;
        if(!$line->quantity || !$line->isValidQuantity())
            $errors[] = LineControllerException::INVALID_QUANTITY;
        $line->price = filter_var($inputValues['price'], FILTER_VALIDATE_FLOAT) ?: null;
        if(!$line->price || !$line->isValidPrice())
            $errors[] = ProductControllerException::INVALID_PRICE;
        if(!$errors) {
            // Si aucune erreur, persister.
            $line->persist();
            return $line;
        }
        return null;
    }

}
