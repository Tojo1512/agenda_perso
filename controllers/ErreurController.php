<?php
/**
 * Contrôleur ErreurController
 * Gère l'affichage des pages d'erreur
 */
class ErreurController {
    /**
     * Affiche la page d'erreur 404 (Page non trouvée)
     */
    public function notFound() {
        http_response_code(404);
        require_once(VIEW_PATH . 'erreur/404.php');
    }
    
    /**
     * Affiche la page d'erreur 403 (Accès refusé)
     */
    public function accessDenied() {
        http_response_code(403);
        require_once(VIEW_PATH . 'erreur/403.php');
    }
    
    /**
     * Affiche la page d'erreur 500 (Erreur serveur)
     */
    public function serverError() {
        http_response_code(500);
        require_once(VIEW_PATH . 'erreur/500.php');
    }
    
    /**
     * Affiche la page pour une maintenance
     */
    public function maintenance() {
        http_response_code(503);
        require_once(VIEW_PATH . 'erreur/maintenance.php');
    }
} 