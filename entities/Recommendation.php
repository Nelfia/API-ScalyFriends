<?php

declare(strict_types=1);

namespace entities;

use peps\core\Entity;

/**
 * Entité Recommendation.
 * Toutes les propriétés sont initialisées par défaut pour les éventuels formulaires de saisie.
 * Chargement en Lazy Loading.
 * 
 * @see Entity
 */
class Recommendation extends Entity  {
    /**
     * PK.
     *
     * @var integer|null
     */
    public ?int $idRecommendation = null;
    /**
     * FK du produit1.
     *
     * @var integer|null
     */
    public ?int $idProduct1 = null;
    /**
     * FK du produit2.
     *
     * @var integer|null
     */
    public ?int $idProduct2 = null;

    /**
     * Instance du Produit1 inséré dans la ligne.
     * Chargement en lazy loading.
     *
     * @var Product|null
     */
    protected ?Product $product1 = null;
    /**
     * Instance du Produit2 inséré dans la ligne.
     * Chargement en lazy loading.
     *
     * @var Product|null
     */
    protected ?Product $product2 = null;

    /**
     * Constructeur.
     *
     * @param integer|null $idLine PK.
     */
    public function __construct(?int $idLine = null) {
        $this->idLine = $idLine;
    }

    /**
     * Retourne l'instance du Produit1 inséré dans la ligne en lazy loading.
     *
     * @return Product Instance du Produit1 inséré dans la ligne.
     */
    public function getProduct1(): ?Product {
        if ($this->product1 === null) {
            $this->product1 = Product::findOneBy(['idProduct' => $this->idProduct1], []);
        }
        return $this->product1;
    }
    /**
     * Retourne l'instance du Produit2 inséré dans la ligne en lazy loading.
     *
     * @return Product Instance du Produit2 inséré dans la ligne.
     */
    public function getProduct2(): ?Product {
        if ($this->product2 === null) {
            $this->product2 = Product::findOneBy(['idProduct' => $this->idProduct2], []);
        }
        return $this->product2;
    }
}