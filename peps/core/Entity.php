<?php

declare(strict_types=1);

namespace peps\core;

use ReflectionClass;
use ReflectionProperty;

/**
 * Implémentation de la persistance ORM pour les classes entités via DBAL.
 * Les classes entités DEVRAIENT étendre cette classe.
 * Trois règles à respecter pour profiter de cette implémentation par défaut.
 * Sinon, redéfinir la méthode describe() dans les classes entités.
 *   -1- Classes et tables portent le même nom selon cet exemple : classe 'TrucChose', table 'TrucChose'.
 *   -2- PK auto-incrémentée nommée selon cet exemple : table 'TrucChose', PK 'idTrucChose'.
 *   -3- Chaque colonne correspond à une propriété PUBLIC du même nom. Les autres propriétés NE sont PAS PUBLIC.
 * Toutes et seulement toutes les propriétés PUBLIC sont persistées.
 * 
 * @see ORM
 */
class Entity extends ORM {
    // Constructeur privé.
    private function __construct() {}

    /**
     * Retourne un tableau associatif 2D décrivant la correspondance ORM d'une entité comme suit:
     * ['tableName' => {nomTable}, 'pkName' => {nomPK}, 'propertiesAndColumns' => [{nomPropriete1} => {nomColonne1}, ...]]
     * Repose sur les 3 règles ci-dessus.
     * Redéfinir dans les classes entités si nécessaire.
     * 
     *@throws EntityException Si describe appelée sur Entity elle-même.
     * @return array Tableau associatif.
     */
    protected static function describe() : array {
        // Si describe appelé sur la classe Entity elle-même, exception.
        if(static::class === self::class)
            PEPS::e(new EntityException(EntityException::WRONG_USAGE_OF_ENTITY_CLASS_ITSELF));
        // Récupérer le nom court (pas pleinement qualifié), de la classe utilisée donc de la table correspondante.
        $rc = new ReflectionClass(static::class);
        $tableName = $rc->getShortName();
        // Construire le nom de la PK à partir du nom de la table.
        $pkName = "id{$tableName}";
        // Récupérer le tableau des propriétés publiques de la classe.
        $properties = $rc->getProperties(ReflectionProperty::IS_PUBLIC);
        // Initialiser le tableau associatif des noms des propriétés et colonnes:
        $propertiesAndColumns = [];
        // Pour chaque propriété
        foreach($properties as $property) {
            $propertiesAndColumns[$property->getName()] = $property->getName();
        }
        return ['tableName' => $tableName, 'pkName' => $pkName, 'propertiesAndColumns' => $propertiesAndColumns ];
    }

    /**
	 * {@inheritDoc}
	 */
	public function hydrate(): bool {
        // Récupérer la description ORM nécessaire.
        ['tableName' => $tableName, 'pkName' => $pkName] = static::describe();
        // Si PK non renseignée, retourner false.
        if(!$this->$pkName) 
            return false;
        $q = "SELECT * FROM {$tableName} WHERE {$pkName} = :id";
        $params = [':id' => $this->$pkName];
        return DBAL::get()->xeq($q, $params)->into($this);
    }

    /**
	 * {@inheritDoc}
	 */
	public function persist(): bool {

        // Récupérer la description ORM nécessaire.
        ['tableName' => $tableName, 'pkName' => $pkName, 'propertiesAndColumns' => $propertiesAndColumns] = static::describe();
        // Initialiser les éléments de requêtes et le tableau des paramètres.
        $keys = $values = $params = [];
        $updateList = "";
        // Pour chaque champ ORM, construire les éléments de requêtes et les paramètres.
        foreach($propertiesAndColumns as $property => $colums) {
            // Si la colonne n'est pas la PK.
            if($property !== $pkName) {
                $keys[] = $property;
                $values[$property] = ":{$colums}";
                $params[":{$colums}"] = $this->$property;
                $updateList .= "{$property} = :{$colums}, ";
            }
        }
        // Créer les requêtes INSERT ou UPDATE.
        if($this->$pkName === null) {
            $keysStr = implode(", ", $keys);
            $valuesStr = implode(", ", $values);
            $q = "INSERT INTO {$tableName} ({$keysStr}) VALUES ({$valuesStr})";
        } else {
            // Supprimer la dernière virgule.
            $updateList = substr($updateList, 0, -2);
            $q = "UPDATE {$tableName} SET {$updateList} WHERE {$pkName} = {$this->$pkName}";
        }
        // Exécuter la requête INSERT ou UPDATE et, si INSERT récupérer la PK auto-incrémentée. Retourner true.
        return $this->$pkName ? (bool)DBAL::get()->xeq($q, $params) : (bool)$this->$pkName = DBAL::get()->xeq($q, $params)->pk();
    }

    /**
	 * {@inheritDoc}
	 */
	public function remove(): bool {
        // Récupérer la description ORM nécessaire.
        ['tableName' => $tableName, 'pkName' => $pkName] = static::describe();
        // Si PK non renseignée, retourner false.
        if(!$this->$pkName) 
            return false;
        $q = "DELETE FROM {$tableName} WHERE {$pkName} = :id";
        $params = [':id' => $this->$pkName];
        return (bool)DBAL::get()->xeq($q, $params)->getNb();
    }

    /**
	 * {@inheritDoc}
	 */
	public static function findAllBy(array $filters = [], array $sortKeys = [], string $limit = ''): array {
        // Récupérer la description ORM nécessaire.
        ['tableName' => $tableName] = static::describe();
        // Initialiser la requête SQL et ses paramètres.
        $q = "SELECT * FROM {$tableName}";
        $params = [];
        // Si filtres présents... (dans $filters)
        if($filters) {
            // Construire la clause WHERE.
            $q .= " WHERE";
            foreach($filters as $col => $val) {
                $q .= " {$col} = :{$col} AND";
                $params[":{$col}"] = $val;
            }
            // Supprimer le dernier AND.
            $q = substr($q, 0, -4);
        }
        // Si les clés de tri présentes...
        if($sortKeys) {
            // Construire la clause ORDER BY.
            $q .= " ORDER BY";
            foreach($sortKeys as $col => $sortOrder) {
                $q .= " {$col} {$sortOrder},";
            }
            // Supprimer le dernier AND.
            $q = substr($q, 0, -1);
            // $q = rtrim($q, ' AND');
        }
        // Si limite, ajouter la clause LIMIT.
        if($limit) 
            $q .= " LIMIT {$limit}";
        // Exécuter la requête et retourner le tableau d'instances.
        return DBAL::get()->xeq($q, $params)->findAll(static::class);
    }

    /**
	 * {@inheritDoc}
	 */
	public static function findOneBy(array $filters = []): ?static {
        return static::findAllBy($filters, [], '1')[0] ?? null;
    }
}