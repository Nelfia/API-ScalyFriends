<?php

declare(strict_types=1);

namespace entities;

use peps\core\Entity;
use peps\core\Router;
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
    protected ?array $commands = [];
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
     * Panier du user.
     * (Lignes de la commande avec le status "panier").
     * Chargement en lazy loading.
     *
     * @var array|null
     */
    protected ?Command $cart = null;
    

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
     * @return User|null User logué ou null si token invalide.
     */
    public static function getLoggedUser() : ?User {
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
     * Retourne les commandes du user.
     *
     * @return ?array Tableau des commandes du user| NULL si aucune commande trouvée.
     */
    public function getCommands(string $status = null): ?array {
        if($this->isGranted("ROLE_ADMIN")) {
            // Si aucun status passé en paramètre, retourne TOUTES les commandes.
            if($status === null)
                return $this->commands = Command::findAllBy([], ['orderDate' => 'DESC'])?:[];
            else
                return $this->commands = Command::findAllBy(['status' => $status ], ['orderDate' => 'DESC'])?:[];
        }
        if($this->isGranted("ROLE_USER")){
            // Si aucun status passé en paramètre, retourne TOUTES les commandes.
            if(!$status)
                return $this->commands = Command::findAllBy(['idCustomer' => $this->idUser], ['orderDate' => 'DESC']);
            else
                return $this->commands = Command::findAllBy(['idCustomer' => $this->idUser, 'status' => 'DESC'], ['orderDate' => 'DESC']);
        }
        return null;
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
        if ($this->products === [])
            $this->products = Product::findAllBy(['idAuthor' => $this->idUser], []);
        return $this->products;
    }
    /**
     * Retourne le panier du user en lazy loading.
     *
     * @return Command|null Commande avec status "cart" du user.
     */
    public function getCart(): ?Command {
        if ($this->cart === null) {
            $this->cart = Command::findOneBy(['idCustomer' => $this->idUser,'status' => "cart"], []);
        }
        return $this->cart;
    }

    /**
     * Retire les propriétés pwd et role du User pour ne pas transmettre ces données au client.
     *
     * @return User
     */
    public function secureReturnedUser() : User {
        unset($this->pwd);
        unset($this->roles);
        return $this;
    }
    /**
     * Vérifie si le nom d'utilisateur est valide.
     *
     * @return bool TRUE si valide, sinon FALSE.
     */
    public function isValidUsername() : bool {
        return mb_strlen($this->username) < 255 && mb_strlen($this->username) > 3;
    }
    /**
     * Vérifie si l'adresse mail du user est valide.
     *
     * @return bool TRUE si valide, sinon FALSE.
     */
    public function isValidEmail() : bool {
        $this->email = filter_var($this->email, FILTER_VALIDATE_EMAIL) ?: null;
        return ($this->email && mb_strlen($this->email) < 255 && mb_strlen($this->email) > 0);
    }
    /**
     * Vérifie si le nom de famille du user est valide.
     *
     * @return bool TRUE si valide, sinon FALSE.
     */
    public function isValidLastName() : bool {
        return (mb_strlen($this->lastName) < 255 && mb_strlen($this->lastName) > 2);
    }
    /**
     * Vérifie si le prénom du user est valide.
     *
     * @return bool TRUE si valide, sinon FALSE.
     */
    public function isValidFirstName() : bool {
        return (mb_strlen($this->firstName) < 200 && mb_strlen($this->firstName) > 2);
    }
    /**
     * Nettoie et vérifie si le numéro de téléphone du user est valide.
     *
     * @return bool TRUE si numéro de téléphone valide, 
     * sinon FALSE.
     */
    public function isValidMobile() : bool {
        $this->mobile = preg_replace('`[^0-9]`', '', $this->mobile);
        return (bool)preg_match('`^0[1-9]([0-9]{2}){4}$`', $this->mobile);
    }
    /**
     * Vérifie si une adresse est valide.
     *
     * @return bool TRUE si valide, sinon FALSE.
     */
    public function isValidPostMail() : bool {
        return (mb_strlen($this->postMail) < 255 && mb_strlen($this->postMail) > 6);
    }
    /**
     * Vérifie si le complément d'adresse est valide.
     *
     * @return bool TRUE si valide, sinon FALSE.
     */
    public function isValidPostMailComplement() : bool {
        return (mb_strlen($this->postMailComplement) < 255 && mb_strlen($this->postMailComplement) > 2);
    }
    /**
     * Vérifie si le code postal du user est valide.
     *
     * @return bool TRUE si valide, sinon FALSE.
     */
    public function isValidZipCode() : bool {
        return (bool)preg_match('`^[0-9]{4}0$`', $this->zipCode);
    }
    /**
     * Vérifie si la Ville/Commune est valide.
     *
     * @return bool TRUE si valide, sinon FALSE.
     */
    public function isValidCity() : bool {
        return (mb_strlen($this->city) < 255);
    }
}