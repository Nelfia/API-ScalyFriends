<?php

declare(strict_types=1);

namespace entities;

use peps\core\Entity;

/**
 * Entité Product.
 * Toutes les propriétés sont initialisées par défaut pour les éventuels formulaires de saisie.
 * Chargement en Lazy Loading.
 * 
 * @see Entity
 */
class Product extends Entity  {
    /**
     * PK.
     *
     * @var integer|null
     */
    public ?int $idProduct = null;
    /**
     * FK de l'auteur.
     *
     * @var integer|null
     */
    public ?int $idAuthor = null;
    /**
     * Catégorie du produit.
     *
     * @var string|null
     */
    public ?string $category = null;
    /**
     * Type de produit.
     *
     * @var string|null
     */
    public ?string $type = null;
    /**
     * Nom du produit.
     *
     * @var string|null
     */
    public ?string $name = null;
    /**
     * Description du produit.
     *
     * @var string|null
     */
    public ?string $description = null;
    /**
     * Prix du produit.
     *
     * @var float|null
     */
    public ?float $price = null;
    /**
     * Stock produit.
     *
     * @var integer|null
     */
    public ?int $stock = null;
    /**
     * Sexe de l'animal.
     *
     * @var string|null
     */
    public ?string $gender = null;
    /**
     * Espèce de l'animal.
     *
     * @var string|null
     */
    public ?string $species = null;
    /**
     * Race de l'animal.
     *
     * @var string|null
     */
    public ?string $race = null;
    /**
     * Année de naissance de l'animal.
     *
     * @var string|null
     */
    public ?string $birth = null;
    /**
     * Le produit nécessite d'avoir une certification.
     *
     * @var bool|null TRUE si besoin, sinon FALSE .
     */
    public ?bool $requiresCertification = null;
    /**
     * Dimensions maximum du produit.
     *
     * @var float|null
     */
    public ?float $dimensionsMax = null;
    /**
     * Unité de mesure des dimensions maximum du produit.
     *
     * @var string|null
     */
    public ?string $dimensionsUnit = null;
    /**
     * Caractéristique spécifique du produit du produit.
     *
     * @var string|null
     */
    public ?string $specification = null;
    /**
     * Valeur de la caractéristique spécifique du produit du produit.
     *
     * @var float|null
     */
    public ?float $specificationValue = null;
    /**
     * Unité de mesure de la valeur de la caractéristique spécifique du produit du produit.
     *
     * @var string|null
     */
    public ?string $specificationUnit = null;
    /**
     * Produit "supprimé" ou non.
     *
     * @var bool|null TRUE si visible, FALSE si "supprimé".
     */
    public ?bool $isVisible = null;

    /**
     * Instance du User qui a créé le produit.
     * Chargement en lazy loading.
     *
     * @var User|null
     */
    protected ?User $author = null;

    /**
     * Constructeur.
     *
     * @param integer|null $idProduct PK.
     */
    public function __construct(?int $idProduct = null) {
        $this->idProduct = $idProduct;
    }

    /**
     * Retourne l'auteur du produit en lazy loading.
     *
     * @return User Instance de l'auteur.
     */
    public function getAuthor(): ?User {
        if ($this->author === null) {
            $this->author = User::findOneBy(['idUser' => $this->idAuthor], []);
        }
        return $this->author;
    }
}