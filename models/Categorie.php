<?php
/**
 * Modèle Categorie
 * Gère les opérations liées aux catégories de tâches
 */
class Categorie {
    private $id;
    private $nom;
    private $couleur;
    private $type;
    private $id_utilisateur;
    
    private $db;
    
    /**
     * Constructeur
     */
    public function __construct($id = null) {
        $this->db = Database::getInstance();
        
        if ($id !== null) {
            $this->charger($id);
        }
    }
    
    /**
     * Charger une catégorie depuis la base de données
     * @param int $id Identifiant de la catégorie
     * @return bool Succès du chargement
     */
    public function charger($id) {
        $query = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $this->id = $row['id'];
            $this->nom = $row['nom'];
            $this->couleur = $row['couleur'];
            $this->type = $row['type'];
            $this->id_utilisateur = $row['id_utilisateur'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Créer une nouvelle catégorie
     * @param array $donnees Données de la catégorie
     * @return bool Succès de la création
     */
    public function creer($donnees) {
        // Vérifier les champs obligatoires
        if (empty($donnees['nom'])) {
            return false;
        }
        
        // Préparer les données
        $nom = $donnees['nom'];
        $couleur = isset($donnees['couleur']) ? $donnees['couleur'] : '#CCCCCC'; // Couleur par défaut
        $type = isset($donnees['type']) ? $donnees['type'] : null;
        $id_utilisateur = isset($donnees['id_utilisateur']) ? $donnees['id_utilisateur'] : null;
        
        // Insérer la catégorie
        $query = "INSERT INTO categories (nom, couleur, type, id_utilisateur) 
                  VALUES (:nom, :couleur, :type, :id_utilisateur)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
        $stmt->bindParam(':couleur', $couleur, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $this->id = $this->db->lastInsertId();
            $this->nom = $nom;
            $this->couleur = $couleur;
            $this->type = $type;
            $this->id_utilisateur = $id_utilisateur;
            return true;
        }
        
        return false;
    }
    
    /**
     * Modifier une catégorie existante
     * @param array $donnees Données à modifier
     * @return bool Succès de la modification
     */
    public function modifier($donnees) {
        if ($this->id === null) {
            return false;
        }
        
        $champs = [];
        $params = [];
        
        // Construire dynamiquement la requête en fonction des champs fournis
        if (isset($donnees['nom'])) {
            $champs[] = "nom = :nom";
            $params[':nom'] = $donnees['nom'];
            $this->nom = $donnees['nom'];
        }
        
        if (isset($donnees['couleur'])) {
            $champs[] = "couleur = :couleur";
            $params[':couleur'] = $donnees['couleur'];
            $this->couleur = $donnees['couleur'];
        }
        
        if (isset($donnees['type'])) {
            $champs[] = "type = :type";
            $params[':type'] = $donnees['type'];
            $this->type = $donnees['type'];
        }
        
        if (isset($donnees['id_utilisateur'])) {
            $champs[] = "id_utilisateur = :id_utilisateur";
            $params[':id_utilisateur'] = $donnees['id_utilisateur'];
            $this->id_utilisateur = $donnees['id_utilisateur'];
        }
        
        // Si aucun champ à modifier
        if (empty($champs)) {
            return true;
        }
        
        // Construire la requête
        $query = "UPDATE categories SET " . implode(", ", $champs) . " WHERE id = :id";
        $params[':id'] = $this->id;
        
        // Exécuter la requête
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }
    
    /**
     * Supprimer une catégorie
     * @return bool Succès de la suppression
     */
    public function supprimer() {
        if ($this->id === null) {
            return false;
        }
        
        // Mettre à jour les tâches associées (id_categorie à NULL)
        $query = "UPDATE taches SET id_categorie = NULL WHERE id_categorie = :id_categorie";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_categorie', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Supprimer la catégorie
        $query = "DELETE FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Récupérer toutes les tâches associées à cette catégorie
     * @return array Liste des tâches
     */
    public function listerTaches() {
        if ($this->id === null) {
            return [];
        }
        
        $query = "SELECT * FROM taches WHERE id_categorie = :id_categorie";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_categorie', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        
        $taches = [];
        while ($row = $stmt->fetch()) {
            $tache = new Tache();
            $tache->charger($row['id']);
            $taches[] = $tache;
        }
        
        return $taches;
    }
    
    /**
     * Récupérer toutes les catégories d'un utilisateur
     * @param int $id_utilisateur ID de l'utilisateur
     * @param string $type Type de catégorie (optionnel)
     * @return array Liste des catégories
     */
    public static function getCategoriesUtilisateur($id_utilisateur, $type = null) {
        $db = Database::getInstance();
        
        $query = "SELECT * FROM categories WHERE id_utilisateur = :id_utilisateur";
        $params = [':id_utilisateur' => $id_utilisateur];
        
        if ($type !== null) {
            $query .= " AND type = :type";
            $params[':type'] = $type;
        }
        
        $query .= " ORDER BY nom ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $categories = [];
        while ($row = $stmt->fetch()) {
            $categorie = new Categorie();
            $categorie->id = $row['id'];
            $categorie->nom = $row['nom'];
            $categorie->couleur = $row['couleur'];
            $categorie->type = $row['type'];
            $categorie->id_utilisateur = $row['id_utilisateur'];
            
            $categories[] = $categorie;
        }
        
        return $categories;
    }
    
    /**
     * Récupérer les types de catégories disponibles
     * @return array Liste des types de catégories
     */
    public static function getTypesCategories() {
        $db = Database::getInstance();
        
        $query = "SELECT DISTINCT type FROM categories WHERE type IS NOT NULL ORDER BY type ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $types = [];
        while ($row = $stmt->fetch()) {
            $types[] = $row['type'];
        }
        
        return $types;
    }
    
    // Getters
    public function getId() {
        return $this->id;
    }
    
    public function getNom() {
        return $this->nom;
    }
    
    public function getCouleur() {
        return $this->couleur;
    }
    
    public function getType() {
        return $this->type;
    }
    
    public function getIdUtilisateur() {
        return $this->id_utilisateur;
    }
} 