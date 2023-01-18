<?php

declare(strict_types=1);

namespace classes;

use DateTime;

/**
 * Classe 100% statique de génération de Token.
 */
final class JWT {

    /**
     * Constructeur privé.
     */
    private function __construct() {}

    /**
     * Méthode statique pour génèrer un Token JWT.
     *
     * @param array $header Header du Token.
     * @param array $payload Données passées dans le token.
     * @param string $secret Phrase secrète pour encode JWT.
     * @param int $validity Durée de validité du Token en secondes (par défaut: 1 jour).
     * @return string JWT
     */
    public static function generate(array $header, array $payload, string $secret, int $validity = 86400): string {
        if($validity > 0){
            $now = new DateTime();
            $expiration = $now->getTimestamp() + $validity;
            // Ajout date d'émission (issuedAt)
            $payload['iat'] = $now->getTimestamp();
            // Ajout date d'expiration
            $payload['exp'] = $expiration;
        }

        // On encode en base64
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload)); 

        // On "nettoie" les valeurs encodées
        // On retire les +, / et =
        $base64Header = str_replace(['+','/','='], ['-','_',''], $base64Header);
        $base64Payload = str_replace(['+','/','='], ['-','_',''], $base64Payload);

        // On génère la signature
        $secret = base64_encode($secret);

        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);

        $base64Signature = base64_encode($signature);

        $signature = str_replace(['+','/','='], ['-','_',''], $base64Signature);

        // On crée le token
        $jwt = $base64Header . '.' . $base64Payload . '.' . $signature;

        return $jwt;
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
     * Vérifie si le contenu (la forme) du token est valide.
     *
     * @param string $token Token à vérifier.
     * @return boolean TRUE si le contenu du token est valide, sinon FALSE.
     */
    public static function isValid(string $token) : bool {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }
}