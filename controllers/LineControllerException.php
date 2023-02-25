<?php

declare(strict_types=1);

namespace controllers;

use mysql_xdevapi\Exception;

/**
 * Classe 100% statique de gestion des exceptions.
 */
final class LineControllerException extends Exception {
    public const INVALID_PRODUCT = "Produit invalide.";
    public const INVALID_QUANTITY = "Quantité invalide.";
}
