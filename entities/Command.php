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
     * Instance du User qui a créé la commande.
     * Chargement en lazy loading.
     *
     * @var User|null
     */
    protected ?User $customer = null;
    /**
     * Instance du User qui a pris en charge la commande.
     * Chargement en lazy loading.
     *
     * @var User|null
     */
    protected ?User $agent = null;
    /**
     * Lignes de la commande.
     *
     * @var array|null
     */
    protected ?array $lines = null;

    /**
     * Constructeur.
     *
     * @param integer|null $idOrder PK.
     */
    public function __construct(?int $idOrder = null) {
        $this->idOrder = $idOrder;
    }

    /**
     * Retourne l'instance du client en lazy loading.
     *
     * @return User Instance du client.
     */
    public function getCustomer(): ?User {
        if ($this->customer === null) {
            $this->customer = User::findOneBy(['idUser' => $this->idCustomer], []);
        }
        return $this->customer;
    }
    
    /**
     * Retourne l'instance de l'agent en lazy loading.
     *
     * @return User|null Instance de l'agent ou null si non trouvé.
     */
    public function getAgent(): ?User {
        if ($this->agent === null) {
            $this->agent = User::findOneBy(['idUser' => $this->idAgent], []);
        }
        return $this->agent;
    }
    /**
     * Retourne le tableau des lignes de la commande en lazy loading.
     *
     * @return array|null Tableau des lignes de la commande ou null si non trouvées.
     */
    public function getLines(): ?array {
        if ($this->lines === []) {
            $this->lines = Line::findAllBy(['idCommand' => $this->idOrder], []);
        }
        return $this->lines;
    }
}