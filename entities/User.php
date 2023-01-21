<?php

declare(strict_types=1);

namespace entities;

use peps\core\Entity;
use peps\jwt\JWT;
use peps\jwt\LoggableUser;

/**
 * Entité User.
 * Toutes les propriétés sont initialisées par défaut pour les éventuels formulaires de saisie.
 * Chargement en Lazy Loading.
 * 
 * @see Entity
 */
class User extends Entity implements LoggableUser {
    /**
     * PK.
     *
     * @var integer|null
     */
    public ?int $idUser = null;
    /**
     * Accès du User (public|user|admin).
     *
     * @var string|null
     */
    public ?string $roles = null;
    /**
     * Identifiant.
     *
     * @var string|null
     */
    public ?string $username = null;
    /**
     * Mot de passe TOUJOURS chiffré.
     *
     * @var string|null
     */
    public ?string $pwd = null;
    /**
     * Email.
     *
     * @var string|null
     */
    public ?string $email = null;
    /**
     * Nom.
     *
     * @var string|null
     */
    public ?string $lastName = null;
    /**
     * Prénom.
     *
     * @var string|null
     */
    public ?string $firstName = null;
    /**
     * Numéro de téléphone.
     *
     * @var string|null
     */
    public ?string $mobile = null;
    /**
     * Adresse postale.
     *
     * @var string|null
     */
    public ?string $postMail = null;
    /**
     * Complément adresse postale.
     *
     * @var string|null
     */
    public ?string $postMailComplement = null;
    /**
     * Code postal.
     *
     * @var string|null
     */
    public ?string $zipCode = null;
    /**
     * Commune/ville.
     *
     * @var string|null
     */
    public ?string $city = null;

    /**
     * Tableau des commandes du user.
     * Chargement en lazy loading.
     *
     * @var array|null
     */
    protected ?array $orders = [];
    /**
     * Tableau des favoris du user.
     * Chargement en lazy loading.
     *
     * @var array|null
     */
    protected ?array $favorites = [];
    /**
     * Tableau des produits créés par le user (ADMIN).
     * Chargement en lazy loading.
     *
     * @var array|null
     */
    protected ?array $products = [];
    /**
     * Identifiant de la commande avec le status "panier".
     * (Commande avec le status "panier").
     * Chargement en lazy loading.
     *
     * @var Commande|null
     */
    protected ?int $idCart = null;
    /**
     * Panier du user.
     * (Lignes de la commande avec le status "panier").
     * Chargement en lazy loading.
     *
     * @var array|null
     */
    protected ?array $cart = [];

    /**
     * Instance du User logué.
     * Lazy loading.
     *
     * @var self|null
     */
    private static ?self $loggedUser = null;

    /**
     * Constructeur.
     *
     * @param integer|null $idUser PK.
     */
    public function __construct(?int $idUser = null) {
        $this->idUser = $idUser;
    }

    /**
     * Tente de loguer $this. 
     * Retourne true ou false selon que le login a réussi ou pas.
     * 
     * @param string $pwd Mot de passe clair.
     * @return boolean Echec ou réussite.
     */
    public function login(string $pwd) : bool {
        // Retrouver le user d'après son username - Requête SELECT préparée
        $user = User::findOneBy(['username' => $this->username]);
        if(!$user) return false;
        // Si username/pwd corrects, hydrater $this, le placer en session et retourner true.
        if (password_verify($pwd, $user->pwd)) {
            // Définir l'idUser et $this sur l'idUser de $user.
            $this->idUser = $user->idUser;
            // Hydrater $this.
            return $this->hydrate();
        }
        // Sinon retourner false.
        return false;             
    }

    /**
     * Retourne le user logué ou null si absent.
     * Lazy loading.
     *
     * @return self|null User logué ou null si token invalide.
     */
    public static function getLoggedUser() : ?self {
        // Vérifier la présence et la validité d'un token.
        $token = JWT::isValidJWT();
        // Si $loggedUser non renseigné mais token valide, récupérer la varibale contenant user_id, créer le user, l'hydrater et le stocker.
        if(!self::$loggedUser && $token) {
            $idUser = (int)JWT::getPayload($token)['user_id'];
            self::$loggedUser = new User($idUser);
            self::$loggedUser->hydrate();
        }
        // Sinon, si $loggedUser renseigné, le retourner.
        return self::$loggedUser ?: null;
    }

    /**
     * Vérifie les droits d'accès du user logué.
     *
     * @param string $role Role du user logué.
     * @return boolean TRUE si accès autorisé, sinon FALSE.
     */
    public function isGranted(string $role) : bool {
        // Si user logué
        if(User::getLoggedUser())
            $userRoles = json_decode(User::getLoggedUser()?->roles)?: null;
            // Vérifier s'il a les droits d'accès et retourner.
            foreach($userRoles as $userRole)
                if($userRole === $role)
                    return true;
        return false;
    }

    /**
     * Retourne le tableau des commandes du user en lazy loading.
     *
     * @return array Tableau des commandes du user.
     */
    public function getOrders(): array {
        if ($this->orders === []) {
            if(json_decode($this->role) === "ROLE_ADMIN")
                $this->orders = Order::findAllBy(['idAgent' => $this->idUser], []);
            else
                $this->orders = Order::findAllBy(['idClient' => $this->idUser], []);
        }
        return $this->orders;
    }
    /**
     * Retourne le tableau des favoris du user en lazy loading.
     *
     * @return array Tableau des favoris.
     */
    public function getFavorites(): array {
        if ($this->favorites === []) {
            $this->favorites = Favorite::findAllBy(['idUser' => $this->idUser], []);
        }
        return $this->favorites;
    }
    /**
     * Retourne le tableau des produits créés par le user en lazy loading.
     *
     * @return array Tableau des produits créés.
     */
    public function getProducts(): array {
        if ($this->products === []) {
            $this->products = Favorite::findAllBy(['idUser' => $this->idUser], []);
        }
        return $this->products;
    }
    /**
     * Retourne l'idOrder du panier du user en lazy loading.
     *
     * @return int|null Identifiant de la commande avec status "cart" du user.
     */
    public function getIdCart(): ?int {
        if ($this->idCart === null) {
            $cart = Order::findOneBy(['idClient' => $this->idUser,'status' => "panier"], []);
            $this->idCart = $cart->idOrder;
        }
        return $this->idCart;
    }
    /**
     * Retourne les lignes du panier du user en lazy loading.
     *
     * @return array Tableau des produits créés.
     */
    public function getCartLines(): array {
        if ($this->cart === []) {
            $this->cart = Line::findAllBy(['idCommande' => $this->idCart], []);
        }
        return $this->cart;
    }
}