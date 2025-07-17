<?php
/**
 * Contrôleur AccueilController
 * Gère l'affichage et les actions de la page d'accueil
 */
class AccueilController {
    /**
     * Affiche la page d'accueil
     */
    public function index() {
        // Vérifier si l'utilisateur est connecté
        $connecte = isset($_SESSION['utilisateur_id']);
        
        if ($connecte) {
            // Rediriger vers le tableau de bord si connecté
            header('Location: index.php?controller=Tableau&action=index');
            exit;
        } else {
            // Afficher la page d'accueil pour les utilisateurs non connectés
            require_once(VIEW_PATH . 'accueil/index.php');
        }
    }
    
    /**
     * Affiche le formulaire de connexion
     */
    public function connexion() {
        $erreur = null;
        
        // Traitement du formulaire de connexion
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['email']) && isset($_POST['mot_de_passe'])) {
                $email = $_POST['email'];
                $mot_de_passe = $_POST['mot_de_passe'];
                
                $utilisateur = new Utilisateur();
                
                if ($utilisateur->connecter($email, $mot_de_passe)) {
                    // Rediriger vers le tableau de bord après connexion réussie
                    header('Location: index.php?controller=Tableau&action=index');
                    exit;
                } else {
                    $erreur = "Email ou mot de passe incorrect";
                }
            } else {
                $erreur = "Veuillez remplir tous les champs";
            }
        }
        
        // Afficher le formulaire de connexion
        require_once(VIEW_PATH . 'accueil/connexion.php');
    }
    
    /**
     * Affiche le formulaire d'inscription
     */
    public function inscription() {
        $erreur = null;
        $succes = null;
        
        // Traitement du formulaire d'inscription
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (
                isset($_POST['nom']) && 
                isset($_POST['prenom']) && 
                isset($_POST['email']) && 
                isset($_POST['mot_de_passe']) && 
                isset($_POST['confirmation_mot_de_passe'])
            ) {
                $nom = $_POST['nom'];
                $prenom = $_POST['prenom'];
                $email = $_POST['email'];
                $mot_de_passe = $_POST['mot_de_passe'];
                $confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'];
                
                // Validation des données
                if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe)) {
                    $erreur = "Veuillez remplir tous les champs";
                } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $erreur = "L'adresse email n'est pas valide";
                } else if ($mot_de_passe !== $confirmation_mot_de_passe) {
                    $erreur = "Les mots de passe ne correspondent pas";
                } else if (strlen($mot_de_passe) < 8) {
                    $erreur = "Le mot de passe doit contenir au moins 8 caractères";
                } else {
                    // Créer le compte utilisateur
                    $utilisateur = new Utilisateur();
                    
                    if ($utilisateur->creerCompte($nom, $prenom, $email, $mot_de_passe)) {
                        $succes = "Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.";
                    } else {
                        $erreur = "Cette adresse email est déjà utilisée";
                    }
                }
            } else {
                $erreur = "Veuillez remplir tous les champs";
            }
        }
        
        // Afficher le formulaire d'inscription
        require_once(VIEW_PATH . 'accueil/inscription.php');
    }
    
    /**
     * Déconnecte l'utilisateur
     */
    public function deconnexion() {
        // Vérifier si l'utilisateur est connecté
        if (isset($_SESSION['utilisateur_id'])) {
            $utilisateur = new Utilisateur($_SESSION['utilisateur_id']);
            $utilisateur->deconnecter();
        }
        
        // Rediriger vers la page d'accueil
        header('Location: index.php');
        exit;
    }
} 