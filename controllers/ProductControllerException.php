<?php

declare(strict_types=1);

namespace controllers;

use Exception;

/**
 * Exceptions en lien avec ProductControllerException.
 * Classe 100% statique.
 * @see Entity
 */
final class ProductControllerException extends Exception {
    // Messages d'erreurs.
    public const INVALID_ID = "Invalid id.";
    public const INVALID_CATEGORY = "Invalid category.";
    public const INVALID_TYPE = "Invalid type.";
    public const INVALID_NAME = "Invalid name.";
    public const INVALID_DESCRIPTION = "Invalid description.";
    public const INVALID_IMG = "Invalid image's path.";
    public const INVALID_PRICE = "Invalid price.";
    public const INVALID_DUPLICATED_REF = "Invalid duplicated ref.";
    public const INVALID_STOCK = "Invalid stock.";
    public const INVALID_GENDER = "Invalid gender.";
    public const INVALID_SPECIES_OR_BRAND = "Invalid species/brand.";
    public const INVALID_RACE = "Invalid race.";
    public const INVALID_BIRTH = "Invalid year of birth.";
    public const INVALID_REQUIRES_CERTIFICATION = "Invalid requires certification";
    public const INVALID_DIMENSION = "Invalid dimensions.";
    public const INVALID_DIMENSION_UNIT = "Invalid dimensions'unit.";
    public const INVALID_SPECIFICATION = "Invalid specification name.";
    public const INVALID_SPECIFICATION_VALUE = "Invalid specification value.";
    public const INVALID_SPECIFICATION_UNIT = "Invalid specification unit.";

}