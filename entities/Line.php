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
class Line extends Entity
{
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
    public ?int $idCommand = null;
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
    protected ?Command $command = null;
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
    public function __construct(?int $idLine = null)
    {
        $this->idLine = $idLine;
    }

    /**
     * Retourne l'instance de la commande dans laquelle est la ligne en lazy loading.
     *
     * @return Command Instance de la commande dans laquelle est la ligne.
     */
    public function getCommand(): ?Command
    {
        if ($this->command === null) {
            $this->command = Command::findOneBy(['idCommand' => $this->idCommand]);
        }
        return $this->command;
    }

    /**
     * Retourne l'instance du Produit inséré dans la ligne en lazy loading.
     *
     * @return Product Instance du Produit inséré dans la ligne.
     */
    public function getProduct(): ?Product
    {
        if ($this->product === null) {
            $this->product = Product::findOneBy(['idProduct' => $this->idProduct], []);
        }
        return $this->product;
    }

    /**
     * Vérifie que la quantité de produits insérés est valide.
     *
     * @return bool TRUE si valide, sinon FALSE.
     */
    public function isValidQuantity(): bool {
        $product = $this->getProduct();
        return $this->quantity > 0 && $this->quantity <= $product->stock;
    }

    /**
     * Vérifie si le prix est valide et s'il correspond au prix du produit inséré.
     *
     * @return bool TRUE si valide, FALSE sinon.
     */
    public function isValidPrice(): bool {
        $product = $this->getProduct();
        return ($this->price === $product->price) && $this->price > 0;
    }
}