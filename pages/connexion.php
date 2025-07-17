<?php
// Traitement du formulaire de connexion
$message_erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';
    
    if (empty($email) || empty($mot_de_passe)) {
        $message_erreur = 'Veuillez remplir tous les champs.';
    } else {
        // Connexion à la base de données
        $db = connectDB();
        
        // Rechercher l'utilisateur par email
        $query = "SELECT * FROM utilisateurs WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($utilisateur = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Vérifier le mot de passe
            if (password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
                // Connexion réussie, enregistrer dans la session
                $_SESSION['utilisateur_id'] = $utilisateur['id'];
                $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
                $_SESSION['utilisateur_prenom'] = $utilisateur['prenom'];
                $_SESSION['utilisateur_email'] = $utilisateur['email'];
                $_SESSION['theme'] = $utilisateur['theme'] ?: 'clair';
                
                // Rediriger vers le tableau de bord
                header('Location: index.php?page=tableau_bord');
                exit;
            } else {
                $message_erreur = 'Email ou mot de passe incorrect.';
            }
        } else {
            $message_erreur = 'Email ou mot de passe incorrect.';
        }
    }
}
?>

<div class="row mt-5">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-sign-in-alt"></i> Connexion</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($message_erreur)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($message_erreur); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="index.php?page=connexion">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </button>
                        <a href="index.php?page=inscription" class="btn btn-outline-secondary">
                            <i class="fas fa-user-plus"></i> Créer un compte
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> 