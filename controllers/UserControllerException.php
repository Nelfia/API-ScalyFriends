<?php

declare(strict_types=1);


namespace controllers;

use Exception;

/**
 * Exceptions en lien avec UserController.
 * Classe 100% statique.
 * @see Entity
 */
final class UserControllerException extends Exception{
   public const NO_LOGGED_USER = "Aucun utilisateur connecté.";
   public const ACCESS_DENIED = "Vous n'êtes pas autorisé à accéder à cette page";
}
