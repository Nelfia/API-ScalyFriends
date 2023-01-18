<?php

declare(strict_types=1);

namespace peps\core;

use Error;
use PDO;
use PDOException;
use PDOStatement;

/**
 * DBAL via PDO.
 * Design Pattern Singleton.
 */
final class DBAL {
    /**
     * Options de connexion PDO commune à toutes les applis.
     *   - Gestion des erreurs basées sur des exceptions.
     *   - Typage des colonnes respecté.
     *   - Requêtes réellement préparées plutôt que simplement simulée.
     */
    private const OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    /**
     * Instance singleton.
     *
     * @var self|null
     */
    private static ?self $instance = null;
    /**
     * Instance de PDO.
     *
     * @var PDO|null
     */
    private ?PDO $db = null;
    /**
     * Instance de PDOStatement.
     *
     * @var PDOStatement|null
     */
    private ?PDOStatement $stmt = null;
    /**
     * Nombre d'enregistrements retrouvés (SELECT) ou affectés (NON SELECT) par la dernière requête
     *
     * @var int|null
     */
    private ?int $nb = null;
    /**
     * Constructeur privé.
     */
    private function __construct() {}

    /**
     *  Crée l'instance singleton et l'instance de PDO encapsulée.
     *
     * @throws DBALException Si la connexion PDO échoue.
     * @param string $driver Driver DB (ex: 'mysql').
     * @param string $host Hôte DB.
     * @param integer $port Port de l'hôte.
     * @param string $name Nom de la DB.
     * @param string $log Identifiant de l'utilisateur de la DB.
     * @param string $pwd Mot de passe de l'utilisateur de la DB.
     * @param string $charset Jeu de caractères de la connexion DB.
     * @return void
     */
    public static function init(
        string $driver,
        string $host,
        int $port,
        string $name,
        string $log,
        string $pwd,
        string $charset
    ) : void { 
        // Si déjà initialisée, ne rien faire.
        if(self::$instance) 
            return;
        // Créer le DSN.
        $dsn = "{$driver}:dbname={$name};host={$host};port={$port};charset={$charset}";
        // Créer l'instance Singleton.
        self::$instance = new self();
        // Créer l'instance de PDO.
        try {
            self::$instance->db = new PDO($dsn, $log, $pwd, self::OPTIONS);
        } catch (PDOException $e) {
            PEPS::e($e, DBALException::DB_CONNECTION_FAILED);
        } 
    }

    /**
     * Retourne l'instance Singleton.
     * La méthode init() DEVRAIT avoir été appelée au préalable.
     *
     * @return static|null Instance de Singleton (ou null si init() pas encore appelée)
     */
    public static function get() : ?static {
        return self::$instance;
    }

    /**
     * Exécute une requête SQL.
     * Retourne $this pour chaînage.
     *
     * @throws DBALException Si requête ou paramètres incorrects.
     * @param string $q La requête à exécuter.
     * @param array $params Tableau associatif des paramètres (facultatif).
     * @return static $this pour chaînage.
     */
    public function xeq(string $q, array $params = []) : static {
        // Journaliser la requête.
        PEPS::addQuery($q, $params);
        // Si paramètres présents
        if($params) {
            // Préparer la requête
            try {
                $this->stmt = $this->db->prepare($q);
            } catch (PDOException $e) {
                PEPS::e($e, DBALException::WRONG_PREPARED_SQL_QUERY);
            }
            // Exécuter la requête
            try {
                $this->stmt->execute($params);
            } catch (PDOException $e) {
                PEPS::e($e, DBALException::WRONG_SQL_QUERY_PARAMETERS);
            }
            // Récupérer le nombre d'enregistrements retrouvés ou affectés.
            $this->nb = $this->stmt->rowCount();
        } 
        // Sinon, si requête SELECT, l'exécuter sans préparation.
        elseif (stripos(ltrim($q), 'SELECT') === 0) {
            try {
                $this->stmt = $this->db->query($q);
            } catch (PDOException $e) {
                PEPS::e($e, DBALException::WRONG_SELECT_SQL_QUERY);
            }
            // Récupérer le nombre d'enregistrements retrouvés.
            $this->nb = $this->stmt->rowCount();
        }
        // Sinon, (requête NON SELECT), l'exécuter sans la préparer et récupérer le nombre d'enregistrements affectés.
        else {
            try {
                $this->nb = $this->db->exec($q);
            } catch (PDOException $e) {
                PEPS::e($e, DBALException::WRONG_NON_SELECT_SQL_QUERY);
            }
            // Par sécurité, RAZ de l'instance PDOStatement $stmt.
            $this->stmt = null;
        }
        return $this;
    }

    /**
     * Retourne le nb d'enregistrements retrouvés ou affectés par la dernière requête.
     *
     * @return int|null Nombre d'enregistrements.
     */
    public function getNb(): ?int {
        return $this->nb;
    }

    /**
     * Retourne un tableau d'instances d'une classe donnée en exploitant le dernier jeu d'enregistrements.
     * Une requête SELECt DEVRAIT avoir été exécutée préalablement.
     *
     * @throws DBALException Si la classe n'existe pas.
     * @param string $className La classe donnée.
     * @return array Tableau d'instances de la classe donnée.
     */
    public function findAll(string $className = 'stdClass') : array {
        // Si pas de jeu d'enregistrements, on retourne un tableau vide.
        if(!$this->stmt) 
            return [];
        // Définir le mode de récupération.
        try {
            $this->stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $className);
        } catch (Error $e) {
            PEPS::e($e, DBALException::FETCH_CLASS_UNAVAILABLE);
        } 
        // Récupérer le jeu d'enregistrements.
        return $this->stmt->fetchAll();
    }

    /**
     * Retourne une instance d'une classe donnée en exploitant le premier enregistrement du dernier jeu d'enregistrements.
     * Une requête SELECT DEVRAIT avoir été exécutée préalablement.
     * Retourne null si aucun jeu d'enregistrement ou jeu d'enregistrement vide.
     *
     * @throws DBALException Si la classe n'existe pas.
     * @param string $className La classe donnée.
     * @return object|null Instance de la classe donnée.
     */
    public function findOne(string $className = 'stdClass') : ?object {
        // Si pas de jeu d'enregistrements, retourner null.
        if(!$this->stmt) 
            return null;
        // Définir le mode de récupération.
        try {
            $this->stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $className);
        } catch (Error $e) {
            PEPS::e($e, DBALException::FETCH_CLASS_UNAVAILABLE);
        } 
        // Récupérer le jeu d'enregistrements.
        return $this->stmt->fetch() ?: null;
    }

    /**
     * Hydrate une instance donnée en exploitant le premier enregistrement du dernier jeu d'enregistrements. 
     * Une requête SELECT (typiquement retrouvant au maximum un enregistrement) DEVRAIT avoir été exécutée préalablement.
     *
     * @param object $obj Instance donnée.
     * @return boolean True si hydratation a réussi.
     */
    public function into(object $obj) : bool {
        // Si pas de jeu d'enregistrements, retourner false.
        if(!$this->stmt) 
            return false;
        // Définir le mode de récupération.
        $this->stmt->setFetchMode(PDO::FETCH_INTO, $obj);
        // Hydrater.
        return (bool)$this->stmt->fetch();
    }

    /**
     * Retourne la dernière clé primaire auto-incrémentée.
     * Retourne 0 si aucune PK.
     *
     * @return integer
     */
    public function pk() : int {
        return (int)$this->db->lastInsertId();
    }

    /**
     * Démarre une transaction.
     *
     * @return static $this pour chaînage.
     */
    public function start() : static {
        $this->db->beginTransaction();
        return $this;
    }

    /**
     * Définit un point de restauration dans la transaction en cours.
     *
     * @param string $label Nom du point de restauration.
     * @return static $this pour chaînage.
     */
    public function savepoint(string $label) : static {
        $q = "SAVEPOINT {$label}";
        return $this->xeq($q);
    }

    /**
     * Effectue un rollback au point de restauration donné ou au départ si absent.
     *
     * @param string|null $label Nom du point de restauration (facultatif).
     * @return static $this pour chaînage.
     */
    public function rollback(string $label = null) : static {
        if(!$label){
            $q = "ROLLBACK";
            return $this->xeq($q);
        } 
        $q = "ROLLBACK TO {$label}";
        return $this->xeq($q);
    }

    /**
     * Valide la transaction en cours.
     *
     * @return static $this pour chaînage.
     */
    public function commit() : static {
        $this->db->commit();
        return $this;
    }
}