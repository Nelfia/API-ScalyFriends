<?php

declare(strict_types=1);

namespace controllers;

use Exception;

/**
 * Gestion des Exceptions en lien avec CommandController.
 * Classe 100% statique.
 */
final class CommandControllerException extends Exception{
   public const INVALID_ID = "Identifiant commande incorrect.";
   public const NO_CHANGE_ALLOWED = "La commande ne peut plus être modifiée.";
   public const NO_MATCH_FOUND = "Aucune commande trouvée.";
}