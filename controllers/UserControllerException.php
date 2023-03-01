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
   public const USERNAME_INVALID = "Le nom d'utilisateur n'est pas valide";
   public const PASSWORD_INVALID = "Le mot de passe n'est pas valide";
   public const ERROR_LOGIN = "Identifiant et/ou mot de passe invalide(s)";

}
