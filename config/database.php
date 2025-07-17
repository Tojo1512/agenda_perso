<?php
/**
 * Configuration de la base de données
 */

// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'bdd_agenda_perso');
define('DB_USER', 'root');  // À changer en production
define('DB_PASS', '');      // À changer en production
define('DB_CHARSET', 'utf8mb4');

/**
 * Classe de gestion de la connexion à la base de données
 */
class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Constructeur privé pour le pattern Singleton
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En développement, on affiche l'erreur
            // En production, on redirige vers une page d'erreur
            die('Erreur de connexion à la base de données : ' . $e->getMessage());
        }
    }
    
    /**
     * Obtenir l'instance unique de la connexion (Singleton)
     * @return PDO Instance de PDO
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }
    
    /**
     * Méthode magique pour la désérialisation
     */
    public function __wakeup() {
        $this->__construct();
    }
    
    /**
     * Empêcher le clonage de l'objet
     */
    private function __clone() {}
} 