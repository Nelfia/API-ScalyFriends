<?php

declare(strict_types=1);

namespace peps\jwt;

use DateTime;

require_once '.env.local';

/**
 * Classe 100% statique de gestion des Tokens.
 * NÉCESSITE une constante secrète dans un fichier NON PARTAGÉ (par défaut: nommée 'SECRET' dans le fichier .env.local) et ignoré par git lors des commits.
 * Pour des raisons de sécurité, cette constante DOIT rester totalement SECRETE.
 * Elle ne sera JAMAIS transmise au client.
 * (Idéalement, SECRET est une phrase codée).
 */
final class JWT {

    /**
     * Header par défault.
     * Algorithme HS256 => hash_hmac, SHA256.
     *
     * @var array
     */
    private static array $header = [
        'typ' => 'JWT',
        'alg'=> 'HS256'
    ];

    /**
     * Constructeur privé.
     */
    private function __construct() {}

    /**
     * Méthode statique pour génèrer un Token JWT.
     *
     * @param array $header Header du Token. Si tableau vide passé en paramètre, par défaut => self::$header (HS256).
     * @param array $payload Données passées dans le token.
     * @param string $secret Phrase secrète pour encoder JWT. Par défaut => SECRET dans .env.local.
     * @param int $validity Durée de validité du Token en secondes (par défaut: 1 jour).
     * @return string JWT
     */
    public static function generate(array $header, array $payload, string $secret = SECRET, int $timeout = 86400): string {
        // Si tableau vide passé en paramètre, par défaut utiliser self::$header ('alg' => 'HS256').
        if($header ===  [])
            $header = self::$header;
        // Vérifier si timeout valide.
        if($timeout > 0){
            // Récupérer la date actuelle.
            $now = new DateTime();
            // Définir la date d'expiration.
            $expiration = $now->getTimestamp() + $timeout;
            // Ajout de la date d'émission (issuedAt) au tableau $payload.
            $payload['iat'] = $now->getTimestamp();
            // Ajout de la date d'expiration.
            $payload['exp'] = $expiration;
        }
        // Encoder en base64 le header et le payload.
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload)); 
        // "Nettoyage" des valeurs encodées (on remplace les +, / et = par des valeurs supportées)
        $base64Header = str_replace(['+','/','='], ['-','_',''], $base64Header);
        $base64Payload = str_replace(['+','/','='], ['-','_',''], $base64Payload);
        // Générer la signature
        $secret = base64_encode($secret);
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);
        // Encoder en base64 la signature + "nettoyage" des valeurs encodées
        $base64Signature = base64_encode($signature);
        $signature = str_replace(['+','/','='], ['-','_',''], $base64Signature);
        // Créer le token
        $jwt = $base64Header . '.' . $base64Payload . '.' . $signature;
        // Retourner le token
        return $jwt;
    }

    /**
     * Vérifie si le contenu (la forme) du token est valide.
     *
     * @param string $token Token à vérifier.
     * @return boolean TRUE si le contenu du token est valide, sinon FALSE.
     */
    public static function isClean(string $token) : bool {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }
    
    /**
     * Vérifie si le token passé en paramètre correspond a un token valide.
     *
     * @param string $token Token dont on veut vérifier la conformité.
     * @param string $secret Phrase secrète.
     * @return boolean Retourne TRUE si le token est valide, FALSE dans le cas contraire.
     */
    public static function check(string $token, string $secret): bool {
        // On récupère le header et le payload
        $header = self::getHeader($token);
        $payload = self::getPayload($token);

        // On génère un token de vérification
        $veriftoken = self::generate($header, $payload, $secret, 0);

        return $token === $veriftoken;
    }

    /**
     * Vérifie si le token a expiré ou non.
     *
     * @param string $token Token à vérifier.
     * @return boolean Retourne TRUE si expiré, FALSE si toujours actif.
     */
    public static function isExpired(string $token) : bool {
        // On récupère le payload du token.
        $payload = self::getPayload($token);
        // On récupère la date actuelle.
        $now = new DateTime();
        // Vérifie si le token est expiré.
        return $payload['exp'] < $now->getTimestamp();
    }

    /**
     * Vérifie la présence d'un token et sa validité.
     *
     * @return string $token Si Token reçu et valide.
     * @return bool false Si token non trouvé ou invalide.
     */
    public static function isValidJWT() : mixed {
        // On initialise le token.
        $token = '';
        // On vérifie si on reçoit un token
        if(isset($_SERVER['Authorization']))
            $token = trim($_SERVER['Authorization']);
        elseif(isset($_SERVER['HTTP_AUTHORIZATION']))
            $token = trim($_SERVER['HTTP_AUTHORIZATION']);
        elseif(function_exists('apache_request_headers')){
            $requestHeaders = apache_request_headers();
            if(isset($requestHeaders['Authorization'])){
                $token = trim($requestHeaders['Authorization']);
            }
        }
        // On vérifie que le token reçu commence par 'Bearer'
        if($token !== '' && preg_match('/Bearer\s(\S+)/', $token, $matches)) {
            // On extrait le JWT.
            $token = str_replace('Bearer ', '', $token);
            // On vérifie la forme, la conformité et l'expiration du Token.
            if(JWT::isClean($token) && JWT::check($token, SECRET) && !JWT::isExpired($token))
                return $token;
        } 
        return false;
    } 

    /**
     * Retourne le header du token.
     *
     * @param string $token Token dont on veut récupérer le header.
     * @return array Header du token.
     */
    public static function getHeader(string $token): array {
        // Démontage token
        $array = explode('.', $token);
        // On décode le header
        $header = json_decode(base64_decode($array[0]), true);
        // On retourne le header
        return $header;
    }

    /**
     * Retourne le payload du token.
     *
     * @param string $token Token dont on veut récupérer le payload.
     * @return array Payload décodé du token.
     */
    public static function getPayload(string $token): array {
        // Démontage token
        $array = explode('.', $token);
        // On décode le header
        $payload = json_decode(base64_decode($array[1]), true);
        // On retourne le header
        return $payload;
    }

}