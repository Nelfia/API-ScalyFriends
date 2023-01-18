<?php

declare(strict_types=1);

namespace peps\core;

/**
 * Enumération des modes de fonctionnement.
 */
enum ExecutionMode {
    case DEBUG;     // Afffiche les requêtes SQL et exceptions complètes.
    case DEV;       // Affiche les exceptions complètes.
    case PROD;      // Affiche uniquement le message des exceptions.
}