<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: index.php?page=connexion');
    exit;
}

// Connexion à la base de données
$db = connectDB();

// Récupérer l'ID de l'utilisateur connecté
$id_utilisateur = $_SESSION['utilisateur_id'];

// Initialiser les variables
$message = '';
$type_message = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Récupérer les informations de l'utilisateur
$query = "SELECT * FROM utilisateurs WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id_utilisateur);
$stmt->execute();
$utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement de la modification du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_profil'])) {
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mot_de_passe_actuel = isset($_POST['mot_de_passe_actuel']) ? $_POST['mot_de_passe_actuel'] : '';
    $nouveau_mot_de_passe = isset($_POST['nouveau_mot_de_passe']) ? $_POST['nouveau_mot_de_passe'] : '';
    $confirmation_mot_de_passe = isset($_POST['confirmation_mot_de_passe']) ? $_POST['confirmation_mot_de_passe'] : '';
    
    // Validation des données
    if (empty($nom) || empty($prenom) || empty($email)) {
        $message = 'Les champs nom, prénom et email sont obligatoires.';
        $type_message = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'L\'adresse email n\'est pas valide.';
        $type_message = 'danger';
    } else {
        // Vérifier si l'email existe déjà (pour un autre utilisateur)
        $query_email = "SELECT COUNT(*) FROM utilisateurs WHERE email = :email AND id != :id";
        $stmt_email = $db->prepare($query_email);
        $stmt_email->bindParam(':email', $email);
        $stmt_email->bindParam(':id', $id_utilisateur);
        $stmt_email->execute();
        
        if ($stmt_email->fetchColumn() > 0) {
            $message = 'Cette adresse email est déjà utilisée par un autre utilisateur.';
            $type_message = 'danger';
        } else {
            // Préparer la requête de mise à jour
            $query_update = "UPDATE utilisateurs SET nom = :nom, prenom = :prenom, email = :email";
            $params = [
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':id' => $id_utilisateur
            ];
            
            // Traiter le changement de mot de passe si demandé
            if (!empty($mot_de_passe_actuel) && !empty($nouveau_mot_de_passe)) {
                // Vérifier le mot de passe actuel
                if (!password_verify($mot_de_passe_actuel, $utilisateur['mot_de_passe'])) {
                    $message = 'Le mot de passe actuel est incorrect.';
                    $type_message = 'danger';
                } elseif ($nouveau_mot_de_passe !== $confirmation_mot_de_passe) {
                    $message = 'Les nouveaux mots de passe ne correspondent pas.';
                    $type_message = 'danger';
                } elseif (strlen($nouveau_mot_de_passe) < 6) {
                    $message = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
                    $type_message = 'danger';
                } else {
                    // Hasher le nouveau mot de passe
                    $mot_de_passe_hash = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
                    $query_update .= ", mot_de_passe = :mot_de_passe";
                    $params[':mot_de_passe'] = $mot_de_passe_hash;
                }
            }
            
            // Finaliser et exécuter la requête si pas d'erreur
            if (empty($message)) {
                $query_update .= " WHERE id = :id";
                $stmt_update = $db->prepare($query_update);
                
                if ($stmt_update->execute($params)) {
                    // Mettre à jour les informations de session
                    $_SESSION['utilisateur_nom'] = $nom;
                    $_SESSION['utilisateur_prenom'] = $prenom;
                    $_SESSION['utilisateur_email'] = $email;
                    
                    $message = 'Votre profil a été mis à jour avec succès.';
                    $type_message = 'success';
                    
                    // Recharger les informations de l'utilisateur
                    $stmt->execute();
                    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = 'Une erreur est survenue lors de la mise à jour du profil.';
                    $type_message = 'danger';
                }
            }
        }
    }
}

// Traitement de la modification des préférences
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_preferences'])) {
    $theme = isset($_POST['theme']) ? $_POST['theme'] : 'clair';
    
    $query_update = "UPDATE utilisateurs SET theme = :theme WHERE id = :id";
    $stmt_update = $db->prepare($query_update);
    $stmt_update->bindParam(':theme', $theme);
    $stmt_update->bindParam(':id', $id_utilisateur);
    
    if ($stmt_update->execute()) {
        // Mettre à jour la session
        $_SESSION['theme'] = $theme;
        
        $message = 'Vos préférences ont été mises à jour avec succès.';
        $type_message = 'success';
        
        // Recharger les informations de l'utilisateur
        $stmt->execute();
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = 'Une erreur est survenue lors de la mise à jour des préférences.';
        $type_message = 'danger';
    }
}

// Récupérer les statistiques de l'utilisateur
$query_stats = "SELECT COUNT(*) as total_taches, 
                SUM(CASE WHEN statut = 'terminee' THEN 1 ELSE 0 END) as taches_terminees
                FROM taches 
                WHERE id_utilisateur = :id_utilisateur";
$stmt_stats = $db->prepare($query_stats);
$stmt_stats->bindParam(':id_utilisateur', $id_utilisateur);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

$total_taches = $stats['total_taches'] ?? 0;
$taches_terminees = $stats['taches_terminees'] ?? 0;
$taux_completion = $total_taches > 0 ? round(($taches_terminees / $total_taches) * 100) : 0;

// Récupérer le nombre de catégories
$query_categories = "SELECT COUNT(*) FROM categories WHERE id_utilisateur = :id_utilisateur";
$stmt_categories = $db->prepare($query_categories);
$stmt_categories->bindParam(':id_utilisateur', $id_utilisateur);
$stmt_categories->execute();
$nb_categories = $stmt_categories->fetchColumn();

// Récupérer le nombre d'événements
$query_evenements = "SELECT COUNT(*) FROM evenements e 
                    JOIN emplois_du_temps edt ON e.id_emploi_du_temps = edt.id 
                    WHERE edt.id_utilisateur = :id_utilisateur";
$stmt_evenements = $db->prepare($query_evenements);
$stmt_evenements->bindParam(':id_utilisateur', $id_utilisateur);
$stmt_evenements->execute();
$nb_evenements = $stmt_evenements->fetchColumn();
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">
            <i class="fas fa-user-circle"></i> Profil utilisateur
        </h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $type_message; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informations personnelles</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="display-1 text-primary">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <h4><?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($utilisateur['email']); ?></p>
                            <p class="small text-muted">Membre depuis <?php echo date('d/m/Y', strtotime($utilisateur['date_creation'])); ?></p>
                        </div>
                        
                        <hr>
                        
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="h4 mb-0"><?php echo $total_taches; ?></div>
                                <div class="small text-muted">Tâches</div>
                            </div>
                            <div class="col-4">
                                <div class="h4 mb-0"><?php echo $nb_categories; ?></div>
                                <div class="small text-muted">Catégories</div>
                            </div>
                            <div class="col-4">
                                <div class="h4 mb-0"><?php echo $nb_evenements; ?></div>
                                <div class="small text-muted">Événements</div>
                            </div>
                        </div>
                        
                        <?php if ($total_taches > 0): ?>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Taux de complétion</span>
                                    <span><?php echo $taux_completion; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $taux_completion; ?>%" aria-valuenow="<?php echo $taux_completion; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-edit"></i> Modifier le profil</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="index.php?page=profil">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nom" class="form-label">Nom*</label>
                                    <input type="text" class="form-control" id="nom" name="nom" required
                                           value="<?php echo htmlspecialchars($utilisateur['nom']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="prenom" class="form-label">Prénom*</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" required
                                           value="<?php echo htmlspecialchars($utilisateur['prenom']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email*</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo htmlspecialchars($utilisateur['email']); ?>">
                            </div>
                            
                            <hr>
                            <h6>Changer le mot de passe</h6>
                            <p class="text-muted small">Laissez vide si vous ne souhaitez pas changer votre mot de passe.</p>
                            
                            <div class="mb-3">
                                <label for="mot_de_passe_actuel" class="form-label">Mot de passe actuel</label>
                                <input type="password" class="form-control" id="mot_de_passe_actuel" name="mot_de_passe_actuel">
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nouveau_mot_de_passe" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe">
                                    <div class="form-text">Minimum 6 caractères</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirmation_mot_de_passe" class="form-label">Confirmer le mot de passe</label>
                                    <input type="password" class="form-control" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="modifier_profil" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Préférences</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="index.php?page=profil">
                            <div class="mb-3">
                                <label for="theme" class="form-label">Thème</label>
                                <select class="form-select" id="theme" name="theme">
                                    <option value="clair" <?php echo $utilisateur['theme'] === 'clair' ? 'selected' : ''; ?>>Clair</option>
                                    <option value="sombre" <?php echo $utilisateur['theme'] === 'sombre' ? 'selected' : ''; ?>>Sombre</option>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="modifier_preferences" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer les préférences
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 