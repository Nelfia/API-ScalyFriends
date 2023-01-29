<?php

declare(strict_types=1);

namespace entities;

use peps\core\Entity;

/**
 * Entité Favorite.
 * Toutes les propriétés sont initialisées par défaut pour les éventuels formulaires de saisie.
 * Chargement en Lazy Loading.
 * 
 * @see Entity
 */
class Favorite extends Entity  {
    /**
     * PK.
     *
     * @var integer|null
     */
    public ?int $idFavorite = null;
    /**
     * FK du user.
     *
     * @var integer|null
     */
    public ?int $idUser = null;
    /**
     * FK du produit.
     *
     * @var integer|null
     */
    public ?int $idProduct = null;
    
    /**
     * Instance du User à qui appartient le favori.
     * Chargement en lazy loading.
     *
     * @var User|null
     */
    protected ?User $user = null;
    /**
     * Instance du Produit inséré dans le favori.
     * Chargement en lazy loading.
     *
     * @var Product|null
     */
    protected ?Product $product = null;

    /**
     * Constructeur.
     *
     * @param integer|null $idFavorite PK.
     */
    public function __construct(?int $idFavorite = null) {
        $this->idFavorite = $idFavorite;
    }

    /**
     * Retourne l'instance du User à qui appartient le favori en lazy loading.
     *
     * @return User|null Instance du User à qui appartient le favori si trouvé, sinon retourne null.
     */
    public function getUser(): ?User {
        if ($this->user === null) {
            $this->user = User::findOneBy(['idUser' => $this->idUser], []);
        }
        return $this->user;
    }
    
    /**
     * Retourne l'instance du Produit inséré dans la ligne en lazy loading.
     *
     * @return Product|null Instance du Produit inséré dans la ligne si trouvé, sinon null.
     */
    public function getProduct(): ?Product {
        if ($this->product === null) {
            $this->product = Product::findOneBy(['idProduct' => $this->idProduct], []);
        }
        return $this->product;
    }
}