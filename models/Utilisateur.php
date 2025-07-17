<?php
/**
 * Modèle Utilisateur
 * Gère les opérations liées aux utilisateurs
 */
class Utilisateur {
    private $id;
    private $nom;
    private $prenom;
    private $email;
    private $mot_de_passe;
    private $theme;
    private $preferences;
    private $date_creation;
    
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
     * Charger un utilisateur depuis la base de données
     * @param int $id Identifiant de l'utilisateur
     * @return bool Succès du chargement
     */
    public function charger($id) {
        $query = "SELECT * FROM utilisateurs WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $this->id = $row['id'];
            $this->nom = $row['nom'];
            $this->prenom = $row['prenom'];
            $this->email = $row['email'];
            $this->mot_de_passe = $row['mot_de_passe'];
            $this->theme = $row['theme'];
            $this->preferences = json_decode($row['preferences'], true);
            $this->date_creation = $row['date_creation'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Charger un utilisateur par son email
     * @param string $email Email de l'utilisateur
     * @return bool Succès du chargement
     */
    public function chargerParEmail($email) {
        $query = "SELECT * FROM utilisateurs WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $this->id = $row['id'];
            $this->nom = $row['nom'];
            $this->prenom = $row['prenom'];
            $this->email = $row['email'];
            $this->mot_de_passe = $row['mot_de_passe'];
            $this->theme = $row['theme'];
            $this->preferences = json_decode($row['preferences'], true);
            $this->date_creation = $row['date_creation'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Créer un nouveau compte utilisateur
     * @param string $nom Nom de l'utilisateur
     * @param string $prenom Prénom de l'utilisateur
     * @param string $email Email de l'utilisateur
     * @param string $mot_de_passe Mot de passe (non crypté)
     * @return bool Succès de la création
     */
    public function creerCompte($nom, $prenom, $email, $mot_de_passe) {
        // Vérifier si l'email existe déjà
        $query = "SELECT COUNT(*) FROM utilisateurs WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return false; // Email déjà utilisé
        }
        
        // Hasher le mot de passe
        $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        
        // Définir les préférences par défaut
        $preferences = json_encode([
            'notifications' => true,
            'rappels' => true
        ]);
        
        // Insérer le nouvel utilisateur
        $query = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, theme, preferences) 
                  VALUES (:nom, :prenom, :email, :mot_de_passe, 'clair', :preferences)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
        $stmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':mot_de_passe', $mot_de_passe_hash, PDO::PARAM_STR);
        $stmt->bindParam(':preferences', $preferences, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            $this->id = $this->db->lastInsertId();
            $this->nom = $nom;
            $this->prenom = $prenom;
            $this->email = $email;
            $this->mot_de_passe = $mot_de_passe_hash;
            $this->theme = 'clair';
            $this->preferences = json_decode($preferences, true);
            $this->date_creation = date('Y-m-d H:i:s');
            return true;
        }
        
        return false;
    }
    
    /**
     * Connecter un utilisateur
     * @param string $email Email de l'utilisateur
     * @param string $mot_de_passe Mot de passe
     * @return bool Succès de la connexion
     */
    public function connecter($email, $mot_de_passe) {
        if ($this->chargerParEmail($email)) {
            if (password_verify($mot_de_passe, $this->mot_de_passe)) {
                // Enregistrer les informations dans la session
                $_SESSION['utilisateur_id'] = $this->id;
                $_SESSION['utilisateur_nom'] = $this->nom;
                $_SESSION['utilisateur_prenom'] = $this->prenom;
                $_SESSION['utilisateur_email'] = $this->email;
                $_SESSION['utilisateur_theme'] = $this->theme;
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Déconnecter l'utilisateur courant
     */
    public function deconnecter() {
        // Détruire toutes les variables de session
        $_SESSION = array();
        
        // Détruire la session
        session_destroy();
        
        // Rediriger vers la page d'accueil
        header('Location: index.php');
        exit;
    }
    
    /**
     * Modifier le profil de l'utilisateur
     * @param array $donnees Données à modifier
     * @return bool Succès de la modification
     */
    public function modifierProfil($donnees) {
        $champs = [];
        $params = [];
        
        // Construire dynamiquement la requête en fonction des champs fournis
        if (isset($donnees['nom'])) {
            $champs[] = "nom = :nom";
            $params[':nom'] = $donnees['nom'];
            $this->nom = $donnees['nom'];
        }
        
        if (isset($donnees['prenom'])) {
            $champs[] = "prenom = :prenom";
            $params[':prenom'] = $donnees['prenom'];
            $this->prenom = $donnees['prenom'];
        }
        
        if (isset($donnees['email'])) {
            // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
            $query = "SELECT COUNT(*) FROM utilisateurs WHERE email = :email AND id != :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $donnees['email'], PDO::PARAM_STR);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                return false; // Email déjà utilisé
            }
            
            $champs[] = "email = :email";
            $params[':email'] = $donnees['email'];
            $this->email = $donnees['email'];
        }
        
        if (isset($donnees['mot_de_passe']) && !empty($donnees['mot_de_passe'])) {
            $mot_de_passe_hash = password_hash($donnees['mot_de_passe'], PASSWORD_DEFAULT);
            $champs[] = "mot_de_passe = :mot_de_passe";
            $params[':mot_de_passe'] = $mot_de_passe_hash;
            $this->mot_de_passe = $mot_de_passe_hash;
        }
        
        // Si aucun champ à modifier
        if (empty($champs)) {
            return true;
        }
        
        // Construire la requête
        $query = "UPDATE utilisateurs SET " . implode(", ", $champs) . " WHERE id = :id";
        $params[':id'] = $this->id;
        
        // Exécuter la requête
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }
    
    /**
     * Changer le thème de l'utilisateur
     * @param string $theme Nouveau thème ('clair' ou 'sombre')
     * @return bool Succès du changement
     */
    public function changerTheme($theme) {
        if ($theme !== 'clair' && $theme !== 'sombre') {
            return false;
        }
        
        $query = "UPDATE utilisateurs SET theme = :theme WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':theme', $theme, PDO::PARAM_STR);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $this->theme = $theme;
            $_SESSION['utilisateur_theme'] = $theme;
            return true;
        }
        
        return false;
    }
    
    /**
     * Définir les préférences de l'utilisateur
     * @param array $preferences Tableau des préférences
     * @return bool Succès de la mise à jour
     */
    public function definirPreferences($preferences) {
        $preferences_json = json_encode($preferences);
        
        $query = "UPDATE utilisateurs SET preferences = :preferences WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':preferences', $preferences_json, PDO::PARAM_STR);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $this->preferences = $preferences;
            return true;
        }
        
        return false;
    }
    
    // Getters
    public function getId() {
        return $this->id;
    }
    
    public function getNom() {
        return $this->nom;
    }
    
    public function getPrenom() {
        return $this->prenom;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getTheme() {
        return $this->theme;
    }
    
    public function getPreferences() {
        return $this->preferences;
    }
    
    public function getDateCreation() {
        return $this->date_creation;
    }
    
    public function getNomComplet() {
        return $this->prenom . ' ' . $this->nom;
    }
} 