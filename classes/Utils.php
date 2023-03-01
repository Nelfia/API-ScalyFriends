<?php

declare(strict_types=1);

namespace classes;

use controllers\ProductControllerException;
use peps\core\Router;

/**
 * Classe 100% statique d'outils.
 */
final class Utils {
    /**
     * Récupère les données reçues du client.
     * @return array Données reçues par le formulaire du client.
     */
    public static function getInputData() : array {
        $inputs = json_decode(json_encode(file_get_contents("php://input")));
        return (array)json_decode($inputs);
    }

    /**
     * Convertir et enregistrer une image base64 dans le dossier assets/img/.
     *
     * @param string $img
     * @return string
     */
    public static function createImage(string $img): string
    {
        $path = "C:/Users/qdeca/OneDrive/Bureau/projets/ap_formation/DP/sf-api/assets/img/";
        $image_parts = explode(";base64,", $img);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        if($image_type !== 'png' && $image_type !== 'jpg' && $image_type !== 'jpeg')
            Router::json(ProductControllerException::INVALID_IMG);
        $image_en_base64 = base64_decode($image_parts[1]);
        $fileName = uniqid() . '.' . $image_type;
        $file = $path . $fileName;

        file_put_contents($file, $image_en_base64);
        return $fileName;
    }
}