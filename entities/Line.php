<?php

declare(strict_types=1);

namespace entities;

use peps\core\Entity;

/**
 * Entité Line.
 * Toutes les propriétés sont initialisées par défaut pour les éventuels formulaires de saisie.
 * Chargement en Lazy Loading.
 * 
 * @see Entity
 */
class Line extends Entity  {
    /**
     * PK.
     *
     * @var integer|null
     */
    public ?int $idLine = null;
    /**
     * FK de la commande.
     *
     * @var integer|null
     */
    public ?int $idOrder = null;
    /**
     * FK du produit.
     *
     * @var integer|null
     */
    public ?int $idProduct = null;
    /**
     * Quantité.
     *
     * @var integer|null
     */
    public ?int $quantity = null;
    /**
     * Prix du produit.
     *
     * @var float|null
     */
    public ?float $price = null;

    /**
     * Instance de la commande dans laquelle est la ligne.
     * Chargement en lazy loading.
     *
     * @var Order|null
     */
    protected ?Order $order = null;
    /**
     * Instance du Produit inséré dans la ligne.
     * Chargement en lazy loading.
     *
     * @var Product|null
     */
    protected ?Product $product = null;

    /**
     * Constructeur.
     *
     * @param integer|null $idLine PK.
     */
    public function __construct(?int $idLine = null) {
        $this->idLine = $idLine;
    }

    /**
     * Retourne l'instance de la commande dans laquelle est la ligne en lazy loading.
     *
     * @return Order Instance de la commande dans laquelle est la ligne.
     */
    public function getOrder(): ?Order {
        if ($this->order === null) {
            $this->order = Order::findOneBy(['idOrder' => $this->idOrder], []);
        }
        return $this->order;
    } 
    /**
     * Retourne l'instance du Produit inséré dans la ligne en lazy loading.
     *
     * @return Product Instance du Produit inséré dans la ligne.
     */
    public function getProduct(): ?Product {
        if ($this->product === null) {
            $this->product = Product::findOneBy(['idProduct' => $this->idProduct], []);
        }
        return $this->product;
    }
}