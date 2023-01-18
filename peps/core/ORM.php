<?php

declare(strict_types=1);

namespace peps\core;

use Error;
use JsonSerializable;

/**
 * Abstraction ORM de la persistance des entités.
 * Indépentante du type de système de stockage.
 * Si une propriété 'trucChose' est inaccessible, la méthode 'getTrucChose()' sera invoquée si elle existe. Sinon, null sera retourné.
 * Implémente JsonSerializable pour préciser les propriétés NON PUBLIC à sérialiser. Ici, par défaut, toutes les propriétés.
 */
abstract class ORM implements JsonSerializable
{
	/**
	 * Hydrate l'entité depuis le système de stockage.
	 *
	 * @return boolean True si l'hydratation a réussi.
	 */
	public abstract function hydrate(): bool;

	/**
	 * Persiste l'entité vers le système de stockage.
	 *
	 * @return boolean True systématiquement.
	 */
	public abstract function persist(): bool;

	/**
	 * Supprime l'entité du système de stockage.
	 *
	 * @return boolean True si la suppression a réussi.
	 */
	public abstract function remove(): bool;

	/**
	 * Sélectionne des entités correspondant aux critères dans le système de stockage.
	 * Retourne un tableau d'instances (implémentant ORM).
	 *
	 * @param array $filters Tableau associatif de filtres d'égalité reliées par 'AND' sous la forme 'champ' = 'valeur'. Ex: ['name' => 'truc', 'idCategory' => 3].
	 * @param array $sortKeys Tableau associatif de clés de tri sous la forme 'champ' => 'ASC' | 'DESC'. Ex: ['name' => 'DESC', 'price' => 'ASC'].
	 * @param string $limit Limite de la sélection.
	 *                      Ex: '3' signifie 3 entités à partir de la première.
	 *                      Ex: '2,5' signifie 5 entités à partir de la troisième incluse.
	 * @return ORM[] Tableau d'instances.
	 */
	public abstract static function findAllBy(array $filters = [], array $sortKeys = [], string $limit = ''): array;

	/**
	 * Sélectionne une entité correspondant aux critères dans le système de stockage.
	 * Retourne une instance (implémentant ORM) ou null si aucune correspondance.
	 *
	 * @param array $filters Tableau associatif de filtres d'égalité reliées par 'AND' sous la forme 'champ' = 'valeur'. Ex: ['name' => 'truc', 'idCategory' => 3].
	 * @return static|null L'instance ou null.
	 */
	public abstract static function findOneBy(array $filters = []): ?static;

	/**
	 * Retourne le résultat de l'invocation de la méthode get{PropertyName}() si elle existe.
	 * Sinon retourne null.
	 *
	 * @param string $propertyName Nom de la propriété.
	 * @return mixed Dépend de la classe enfant et de la propriété.
	 */
	public function __get(string $propertyName): mixed {
		// Construire le nom de la méthode à invoquer.
        $methodName = "get" . ucfirst($propertyName);
		// Tenter de l'invoquer.
        try {
            return $this->$methodName();
        } catch (Error) {
            return null;
        }
	}

	/**
	 * Invoque la méthode set{PropertyName}(value) si elle existe.
	 * Sinon ne fait rien.
	 *
	 * @param string $propertyName Nom de la propriété.
     * @param mixed $value
	 * @return void
	 */
	public function __set(string $propertyName, mixed $value): void {
		// Construire le nom de la méthode à invoquer.
        $methodName = "set" . ucfirst($propertyName);
		// Tenter de l'invoquer.
        try {
            $this->$methodName($value);
        } catch (Error) {}
	}

	/**
	 * Méthode appelée automatiquement par json_encode().
	 * Par défaut, seules les propriétés PUBLIC D'INSTANCE sont sérialisées.
	 * Avec cette méthode par défaut, les propriétés PUBLIC ET PROTECTED seront sérialisées en JSON.
	 * Elle les retourne sous la forme d'un tableau associatif comme attendu par json_encode().
	 * Elle peut être redéfinie dans les classes entités si nécessaire.
	 *
	 * @return array Tableau associatif des propriétés et de leur valeur.
	 */
	public function jsonSerialize(): array {
		return get_object_vars($this);
	}
}
