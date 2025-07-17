<?php
// Traitement du formulaire d'inscription
$message_erreur = '';
$message_succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';
    $confirmation = isset($_POST['confirmation']) ? $_POST['confirmation'] : '';
    
    // Validation des données
    if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($confirmation)) {
        $message_erreur = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message_erreur = 'L\'adresse email n\'est pas valide.';
    } elseif ($mot_de_passe !== $confirmation) {
        $message_erreur = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($mot_de_passe) < 6) {
        $message_erreur = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        // Connexion à la base de données
        $db = connectDB();
        
        // Vérifier si l'email existe déjà
        $query = "SELECT COUNT(*) FROM utilisateurs WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            $message_erreur = 'Cette adresse email est déjà utilisée.';
        } else {
            // Hachage du mot de passe
            $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            
            // Définir le thème par défaut (récupérer de la session actuelle si disponible)
            $theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'clair';
            
            // Insertion dans la base de données
            $query = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, theme) 
                      VALUES (:nom, :prenom, :email, :mot_de_passe, :theme)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':mot_de_passe', $mot_de_passe_hash);
            $stmt->bindParam(':theme', $theme);
            
            if ($stmt->execute()) {
                $message_succes = 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.';
                
                // Rediriger vers la page de connexion après 3 secondes
                header('refresh:3;url=index.php?page=connexion');
            } else {
                $message_erreur = 'Une erreur est survenue lors de la création du compte.';
            }
        }
    }
}
?>

<div class="row mt-5">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-user-plus"></i> Inscription</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($message_erreur)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($message_erreur); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($message_succes)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo htmlspecialchars($message_succes); ?>
                    </div>
                <?php else: ?>
                    <form method="post" action="index.php?page=inscription">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" required 
                                       value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required 
                                       value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="mot_de_passe" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                            <div class="form-text">Le mot de passe doit contenir au moins 6 caractères.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmation" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" class="form-control" id="confirmation" name="confirmation" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> S'inscrire
                            </button>
                            <a href="index.php?page=connexion" class="btn btn-outline-secondary">
                                <i class="fas fa-sign-in-alt"></i> J'ai déjà un compte
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> 