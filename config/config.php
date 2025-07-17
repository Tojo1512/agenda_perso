<?php
/**
 * Configuration générale de l'application
 */

// Informations de base
define('APP_NAME', 'Agenda Étudiant');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/agenda_perso');
define('APP_EMAIL', 'contact@agenda-etudiant.com');

// Configuration des dossiers
define('ASSETS_URL', APP_URL . '/assets');
define('CSS_URL', ASSETS_URL . '/css');
define('JS_URL', ASSETS_URL . '/js');
define('IMG_URL', ASSETS_URL . '/img');

// Configuration de la session
define('SESSION_LIFETIME', 7200); // 2 heures en secondes
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);

// Configuration des messages d'erreur
define('DISPLAY_ERRORS', true); // À définir à false en production
if (DISPLAY_ERRORS) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Locale pour les formats de date
setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr.UTF-8', 'fr_FR', 'fr'); 