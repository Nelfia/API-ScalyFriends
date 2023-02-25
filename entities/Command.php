<?php

declare(strict_types=1);

namespace entities;

use peps\core\Entity;

/**
 * Entité Order.
 * Toutes les propriétés sont initialisées par défaut pour les éventuels formulaires de saisie.
 * Chargement en Lazy Loading.
 * 
 * @see Entity
 */
class Command extends Entity  {
    /**
     * PK.
     *
     * @var integer|null
     */
    public ?int $idCommand = null;
    /**
     * FK du client.
     *
     * @var integer|null
     */
    public ?int $idCustomer = null;
    /**
     * FK de l'agent qui est en charge de la commande.
     *
     * @var integer|null
     */
    public ?int $idAgent = null;
    /**
     * Date de la commande.
     *
     * @var string|null
     */
    public ?string $orderDate = null;
    /**
     * Numéro de la commande (unique).
     *
     * @var string|null
     */
    public ?string $ref = null;
    /**
     * Status de la commande.
     *
     * @var string|null
     */
    public ?string $status = null;
    /**
     * Date du dernier changement de status de la commande.
     *
     * @var string|null
     */
    public ?string $lastChange = null;
    /**
     * Tableau des lignes de la commande
     * @var array|null
     */
    protected ?array $lines = null;

    /**
     * Constructeur.
     *
     * @param integer|null $idCommand PK.
     */
    public function __construct(?int $idCommand = null) {
        $this->idCommand = $idCommand;
    }

    /**
     * Retourne le tableau des lignes de la commande.
     *
     * @return array|null Tableau des lignes de la commande ou null si non trouvées.
     */
    public function getLines(): ?array {
        $this->lines = Line::findAllBy(['idCommand' => $this->idCommand], []);
        foreach (($this->lines) as $line) $line->getProduct();
        return $this->lines;
    }

    /**
     * Vérifie si la commande a le status "cart".
     * @return bool TRUE si la commande a le status "cart", sinon FALSE.
     */
    public function isCart() : bool {
        return $this->status === "cart";
    }
}