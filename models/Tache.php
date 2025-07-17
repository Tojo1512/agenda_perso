<?php
/**
 * Modèle Tache
 * Gère les opérations liées aux tâches
 */
class Tache {
    private $id;
    private $titre;
    private $description;
    private $date_creation;
    private $date_echeance;
    private $priorite;
    private $statut;
    private $temps_estime;
    private $id_utilisateur;
    private $id_categorie;
    
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
     * Charger une tâche depuis la base de données
     * @param int $id Identifiant de la tâche
     * @return bool Succès du chargement
     */
    public function charger($id) {
        $query = "SELECT * FROM taches WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $this->id = $row['id'];
            $this->titre = $row['titre'];
            $this->description = $row['description'];
            $this->date_creation = $row['date_creation'];
            $this->date_echeance = $row['date_echeance'];
            $this->priorite = $row['priorite'];
            $this->statut = $row['statut'];
            $this->temps_estime = $row['temps_estime'];
            $this->id_utilisateur = $row['id_utilisateur'];
            $this->id_categorie = $row['id_categorie'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Créer une nouvelle tâche
     * @param array $donnees Données de la tâche
     * @return bool Succès de la création
     */
    public function creer($donnees) {
        // Vérifier les champs obligatoires
        if (empty($donnees['titre']) || empty($donnees['id_utilisateur'])) {
            return false;
        }
        
        // Préparer les données
        $titre = $donnees['titre'];
        $description = isset($donnees['description']) ? $donnees['description'] : null;
        $date_echeance = isset($donnees['date_echeance']) ? $donnees['date_echeance'] : null;
        $priorite = isset($donnees['priorite']) ? $donnees['priorite'] : 'moyenne';
        $statut = isset($donnees['statut']) ? $donnees['statut'] : 'a_faire';
        $temps_estime = isset($donnees['temps_estime']) ? $donnees['temps_estime'] : null;
        $id_utilisateur = $donnees['id_utilisateur'];
        $id_categorie = isset($donnees['id_categorie']) ? $donnees['id_categorie'] : null;
        
        // Insérer la tâche
        $query = "INSERT INTO taches (titre, description, date_echeance, priorite, statut, temps_estime, id_utilisateur, id_categorie) 
                  VALUES (:titre, :description, :date_echeance, :priorite, :statut, :temps_estime, :id_utilisateur, :id_categorie)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':titre', $titre, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':date_echeance', $date_echeance, PDO::PARAM_STR);
        $stmt->bindParam(':priorite', $priorite, PDO::PARAM_STR);
        $stmt->bindParam(':statut', $statut, PDO::PARAM_STR);
        $stmt->bindParam(':temps_estime', $temps_estime, PDO::PARAM_INT);
        $stmt->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        $stmt->bindParam(':id_categorie', $id_categorie, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $this->id = $this->db->lastInsertId();
            $this->titre = $titre;
            $this->description = $description;
            $this->date_creation = date('Y-m-d H:i:s');
            $this->date_echeance = $date_echeance;
            $this->priorite = $priorite;
            $this->statut = $statut;
            $this->temps_estime = $temps_estime;
            $this->id_utilisateur = $id_utilisateur;
            $this->id_categorie = $id_categorie;
            
            // Créer une notification pour la tâche
            if ($date_echeance !== null) {
                $this->creerNotification();
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Modifier une tâche existante
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
        if (isset($donnees['titre'])) {
            $champs[] = "titre = :titre";
            $params[':titre'] = $donnees['titre'];
            $this->titre = $donnees['titre'];
        }
        
        if (isset($donnees['description'])) {
            $champs[] = "description = :description";
            $params[':description'] = $donnees['description'];
            $this->description = $donnees['description'];
        }
        
        if (isset($donnees['date_echeance'])) {
            $champs[] = "date_echeance = :date_echeance";
            $params[':date_echeance'] = $donnees['date_echeance'];
            $this->date_echeance = $donnees['date_echeance'];
        }
        
        if (isset($donnees['priorite'])) {
            $champs[] = "priorite = :priorite";
            $params[':priorite'] = $donnees['priorite'];
            $this->priorite = $donnees['priorite'];
        }
        
        if (isset($donnees['statut'])) {
            $champs[] = "statut = :statut";
            $params[':statut'] = $donnees['statut'];
            $this->statut = $donnees['statut'];
        }
        
        if (isset($donnees['temps_estime'])) {
            $champs[] = "temps_estime = :temps_estime";
            $params[':temps_estime'] = $donnees['temps_estime'];
            $this->temps_estime = $donnees['temps_estime'];
        }
        
        if (isset($donnees['id_categorie'])) {
            $champs[] = "id_categorie = :id_categorie";
            $params[':id_categorie'] = $donnees['id_categorie'];
            $this->id_categorie = $donnees['id_categorie'];
        }
        
        // Si aucun champ à modifier
        if (empty($champs)) {
            return true;
        }
        
        // Construire la requête
        $query = "UPDATE taches SET " . implode(", ", $champs) . " WHERE id = :id";
        $params[':id'] = $this->id;
        
        // Exécuter la requête
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute($params);
        
        // Mettre à jour la notification si la date d'échéance a changé
        if ($success && isset($donnees['date_echeance'])) {
            $this->mettreAJourNotification();
        }
        
        return $success;
    }
    
    /**
     * Supprimer une tâche
     * @return bool Succès de la suppression
     */
    public function supprimer() {
        if ($this->id === null) {
            return false;
        }
        
        // Supprimer les notifications associées
        $query = "DELETE FROM notifications WHERE id_tache = :id_tache";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_tache', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Supprimer la tâche
        $query = "DELETE FROM taches WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Changer le statut d'une tâche
     * @param string $statut Nouveau statut
     * @return bool Succès du changement
     */
    public function changerStatut($statut) {
        $statuts_valides = ['a_faire', 'en_cours', 'terminee'];
        
        if (!in_array($statut, $statuts_valides)) {
            return false;
        }
        
        return $this->modifier(['statut' => $statut]);
    }
    
    /**
     * Définir la priorité d'une tâche
     * @param string $priorite Nouvelle priorité
     * @return bool Succès du changement
     */
    public function definirPriorite($priorite) {
        $priorites_valides = ['basse', 'moyenne', 'haute'];
        
        if (!in_array($priorite, $priorites_valides)) {
            return false;
        }
        
        return $this->modifier(['priorite' => $priorite]);
    }
    
    /**
     * Définir le temps estimé pour une tâche
     * @param int $temps Temps estimé en minutes
     * @return bool Succès du changement
     */
    public function definirTempsEstime($temps) {
        if (!is_numeric($temps) || $temps < 0) {
            return false;
        }
        
        return $this->modifier(['temps_estime' => $temps]);
    }
    
    /**
     * Récupérer toutes les tâches d'un utilisateur
     * @param int $id_utilisateur ID de l'utilisateur
     * @param array $filtres Filtres à appliquer
     * @return array Liste des tâches
     */
    public static function getTachesUtilisateur($id_utilisateur, $filtres = []) {
        $db = Database::getInstance();
        
        $conditions = ["id_utilisateur = :id_utilisateur"];
        $params = [':id_utilisateur' => $id_utilisateur];
        
        // Appliquer les filtres
        if (isset($filtres['statut'])) {
            $conditions[] = "statut = :statut";
            $params[':statut'] = $filtres['statut'];
        }
        
        if (isset($filtres['priorite'])) {
            $conditions[] = "priorite = :priorite";
            $params[':priorite'] = $filtres['priorite'];
        }
        
        if (isset($filtres['id_categorie'])) {
            $conditions[] = "id_categorie = :id_categorie";
            $params[':id_categorie'] = $filtres['id_categorie'];
        }
        
        // Filtre sur la date d'échéance
        if (isset($filtres['date_debut']) && isset($filtres['date_fin'])) {
            $conditions[] = "date_echeance BETWEEN :date_debut AND :date_fin";
            $params[':date_debut'] = $filtres['date_debut'];
            $params[':date_fin'] = $filtres['date_fin'];
        } else if (isset($filtres['date_debut'])) {
            $conditions[] = "date_echeance >= :date_debut";
            $params[':date_debut'] = $filtres['date_debut'];
        } else if (isset($filtres['date_fin'])) {
            $conditions[] = "date_echeance <= :date_fin";
            $params[':date_fin'] = $filtres['date_fin'];
        }
        
        // Construire la requête
        $query = "SELECT * FROM taches";
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        // Tri
        $query .= " ORDER BY ";
        if (isset($filtres['tri']) && $filtres['tri'] === 'priorite') {
            $query .= "FIELD(priorite, 'haute', 'moyenne', 'basse')";
        } else {
            $query .= "date_echeance IS NULL, date_echeance";
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $taches = [];
        while ($row = $stmt->fetch()) {
            $tache = new Tache();
            $tache->id = $row['id'];
            $tache->titre = $row['titre'];
            $tache->description = $row['description'];
            $tache->date_creation = $row['date_creation'];
            $tache->date_echeance = $row['date_echeance'];
            $tache->priorite = $row['priorite'];
            $tache->statut = $row['statut'];
            $tache->temps_estime = $row['temps_estime'];
            $tache->id_utilisateur = $row['id_utilisateur'];
            $tache->id_categorie = $row['id_categorie'];
            
            $taches[] = $tache;
        }
        
        return $taches;
    }
    
    /**
     * Créer une notification pour la tâche
     * @return bool Succès de la création
     */
    private function creerNotification() {
        if ($this->id === null || $this->date_echeance === null) {
            return false;
        }
        
        // Créer une notification pour le jour même
        $query = "INSERT INTO notifications (titre, message, date_envoi, id_utilisateur, id_tache) 
                  VALUES (:titre, :message, :date_envoi, :id_utilisateur, :id_tache)";
        
        $titre = "Échéance de tâche";
        $message = "La tâche \"" . $this->titre . "\" doit être terminée aujourd'hui.";
        $date_envoi = date('Y-m-d', strtotime($this->date_echeance)) . ' 08:00:00'; // Notification à 8h le jour de l'échéance
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':titre', $titre, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':date_envoi', $date_envoi, PDO::PARAM_STR);
        $stmt->bindParam(':id_utilisateur', $this->id_utilisateur, PDO::PARAM_INT);
        $stmt->bindParam(':id_tache', $this->id, PDO::PARAM_INT);
        
        // Créer également une notification de rappel 1 jour avant
        if ($stmt->execute()) {
            $titre_rappel = "Rappel de tâche";
            $message_rappel = "La tâche \"" . $this->titre . "\" arrive à échéance demain.";
            $date_rappel = date('Y-m-d', strtotime($this->date_echeance . ' -1 day')) . ' 18:00:00'; // Notification la veille à 18h
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':titre', $titre_rappel, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message_rappel, PDO::PARAM_STR);
            $stmt->bindParam(':date_envoi', $date_rappel, PDO::PARAM_STR);
            $stmt->bindParam(':id_utilisateur', $this->id_utilisateur, PDO::PARAM_INT);
            $stmt->bindParam(':id_tache', $this->id, PDO::PARAM_INT);
            
            return $stmt->execute();
        }
        
        return false;
    }
    
    /**
     * Mettre à jour les notifications pour la tâche
     * @return bool Succès de la mise à jour
     */
    private function mettreAJourNotification() {
        if ($this->id === null) {
            return false;
        }
        
        // Supprimer les notifications existantes
        $query = "DELETE FROM notifications WHERE id_tache = :id_tache";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_tache', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Créer de nouvelles notifications si une date d'échéance est définie
        if ($this->date_echeance !== null) {
            return $this->creerNotification();
        }
        
        return true;
    }
    
    // Getters
    public function getId() {
        return $this->id;
    }
    
    public function getTitre() {
        return $this->titre;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function getDateCreation() {
        return $this->date_creation;
    }
    
    public function getDateEcheance() {
        return $this->date_echeance;
    }
    
    public function getPriorite() {
        return $this->priorite;
    }
    
    public function getStatut() {
        return $this->statut;
    }
    
    public function getTempsEstime() {
        return $this->temps_estime;
    }
    
    public function getIdUtilisateur() {
        return $this->id_utilisateur;
    }
    
    public function getIdCategorie() {
        return $this->id_categorie;
    }
    
    /**
     * Obtenir le nom de la catégorie
     * @return string Nom de la catégorie ou null si aucune
     */
    public function getNomCategorie() {
        if ($this->id_categorie === null) {
            return null;
        }
        
        $query = "SELECT nom FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $this->id_categorie, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Obtenir la couleur de la catégorie
     * @return string Couleur de la catégorie ou null si aucune
     */
    public function getCouleurCategorie() {
        if ($this->id_categorie === null) {
            return null;
        }
        
        $query = "SELECT couleur FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $this->id_categorie, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
} 