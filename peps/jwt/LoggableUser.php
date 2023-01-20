<?php

declare(strict_types=1);

namespace peps\session;

/**
 * Interface de connexion des utilisateurs.
 * Pour profiter de l'extension "session", la classe entité des utilisateurs DOIT implémenter cette interface.
 */
interface LoggableUser 
{
	/**
     * Tente de loguer le LoggableUser. 
     * Retourne true ou false selon que le login a réussi ou pas.
     * 
     * @param string $pwd Mot de passe en clair.
     * @return boolean Echec ou réussite.
     */
    public function login(string $pwd) : bool;

	/**
     * Retourne le user logué ou null si absent.
     * DEVRAIT utiliser le Lazy loading.
     *
     * @return self|null User logué ou null si aucun user en session.
     */
    public static function getLoggedUser() : ?self ;

}
