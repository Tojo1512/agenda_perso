<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- TRAITEMENT AJAX EN PREMIER ---
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['action']) && 
    $_POST['action'] === 'update_status' && 
    !headers_sent()
) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['utilisateur_id'])) {
        echo json_encode(['success' => false, 'error' => 'Session expirée ou utilisateur non connecté']);
        exit;
    }
    // Connexion à la base de données
    $db = connectDB();
    $id_utilisateur = $_SESSION['utilisateur_id'];

    // Charger les templates prédéfinis de l'utilisateur
    $query_templates = "SELECT * FROM templates WHERE id_utilisateur = :id_utilisateur AND type = 'tache' ORDER BY nom ASC";
    $stmt_templates = $db->prepare($query_templates);
    $stmt_templates->bindParam(':id_utilisateur', $id_utilisateur);
    $stmt_templates->execute();
    $templates = $stmt_templates->fetchAll(PDO::FETCH_ASSOC);

    $task_id = isset($_POST['task_id']) ? $_POST['task_id'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    if (!empty($task_id) && !empty($status)) {
        // Vérifier que la tâche appartient bien à l'utilisateur
        $query_verify = "SELECT COUNT(*) FROM taches WHERE id = :id AND id_utilisateur = :id_utilisateur";
        $stmt_verify = $db->prepare($query_verify);
        $stmt_verify->bindParam(':id', $task_id);
        $stmt_verify->bindParam(':id_utilisateur', $id_utilisateur);
        $stmt_verify->execute();
        if ($stmt_verify->fetchColumn() > 0) {
            $query = "UPDATE taches SET statut = :statut WHERE id = :id AND id_utilisateur = :id_utilisateur";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':statut', $status);
            $stmt->bindParam(':id', $task_id);
            $stmt->bindParam(':id_utilisateur', $id_utilisateur);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    }
    exit;
}

// --- CODE NORMAL DE LA PAGE ---
// Vérifier si une redirection est en attente
if (isset($_SESSION['redirect_url'])) {
    $redirect_url = $_SESSION['redirect_url'];
    unset($_SESSION['redirect_url']);
    header('Location: ' . $redirect_url);
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    $_SESSION['redirect_url'] = 'index.php?page=connexion';
    header('Location: index.php?page=connexion');
    exit;
}

// ----------------------------------
// TRAITEMENT POST (avant tout HTML !)
// ----------------------------------
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['utilisateur_id'])) {
    $_SESSION['redirect_url'] = 'index.php?page=connexion';
    header('Location: index.php?page=connexion');
    exit;
}

$id_utilisateur = $_SESSION['utilisateur_id'];
require_once __DIR__ . '/../includes/db.php';
$db = connectDB();

// Suppression d'un template
if (isset($_POST['supprimer_template']) && isset($_POST['template_id'])) {
    $query_del_tpl = "DELETE FROM templates WHERE id = :id AND id_utilisateur = :id_utilisateur";
    $stmt_del_tpl = $db->prepare($query_del_tpl);
    $stmt_del_tpl->bindParam(':id', $_POST['template_id']);
    $stmt_del_tpl->bindParam(':id_utilisateur', $id_utilisateur);
    $stmt_del_tpl->execute();
    header('Location: index.php?page=taches');
    exit;
}
// Création d’un template à partir du formulaire tâche
if (isset($_POST['enregistrer_template'])) {
    $contenu = [
        'titre' => $_POST['titre'],
        'description' => $_POST['description'],
        'date_echeance' => $_POST['date_echeance'],
        'priorite' => $_POST['priorite'],
        'statut' => $_POST['statut'],
        'temps_estime' => $_POST['temps_estime'],
        'id_categorie' => $_POST['id_categorie']
    ];
    $nom_tpl = !empty($_POST['nom_template']) ? $_POST['nom_template'] : $_POST['titre'];
    $desc_tpl = !empty($_POST['desc_template']) ? $_POST['desc_template'] : '';
    $query_tpl = "INSERT INTO templates (id_utilisateur, nom, description, contenu, type) VALUES (:id_utilisateur, :nom, :description, :contenu, 'tache')";
    $stmt_tpl = $db->prepare($query_tpl);
    $stmt_tpl->bindParam(':id_utilisateur', $id_utilisateur);
    $stmt_tpl->bindParam(':nom', $nom_tpl);
    $stmt_tpl->bindParam(':description', $desc_tpl);
    $stmt_tpl->bindValue(':contenu', json_encode($contenu));
    $stmt_tpl->execute();
    header('Location: index.php?page=taches');
    exit;
}
// ----------------------------------
// FIN TRAITEMENT POST
// ----------------------------------


// Charger les templates personnels de l'utilisateur
$query_templates = "SELECT * FROM templates WHERE id_utilisateur = :id_utilisateur AND type = 'tache' ORDER BY nom ASC";
$stmt_templates = $db->prepare($query_templates);
$stmt_templates->bindParam(':id_utilisateur', $id_utilisateur);
$stmt_templates->execute();
$templates = $stmt_templates->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------------
// TRAITEMENT POST : AJOUT OU MODIF
// ----------------------------------
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' && 
    (isset($_POST['ajouter']) || isset($_POST['modifier']))
) {
    $titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $date_echeance = isset($_POST['date_echeance']) && !empty($_POST['date_echeance']) ? $_POST['date_echeance'] : null;
    $priorite = isset($_POST['priorite']) ? $_POST['priorite'] : 'moyenne';
    $statut = isset($_POST['statut']) ? $_POST['statut'] : 'a_faire';
    $temps_estime = isset($_POST['temps_estime']) && is_numeric($_POST['temps_estime']) ? $_POST['temps_estime'] : null;
    $id_categorie = isset($_POST['id_categorie']) && !empty($_POST['id_categorie']) ? $_POST['id_categorie'] : null;
    
    // Validation du titre
    if (empty($titre)) {
        $message = 'Le titre de la tâche est obligatoire.';
        $type_message = 'danger';
    } else {
        if (isset($_POST['ajouter'])) {
            // Ajout d'une nouvelle tâche
            $query = "INSERT INTO taches (titre, description, date_echeance, priorite, statut, temps_estime, id_utilisateur, id_categorie) 
                      VALUES (:titre, :description, :date_echeance, :priorite, :statut, :temps_estime, :id_utilisateur, :id_categorie)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':date_echeance', $date_echeance);
            $stmt->bindParam(':priorite', $priorite);
            $stmt->bindParam(':statut', $statut);
            $stmt->bindParam(':temps_estime', $temps_estime);
            $stmt->bindParam(':id_utilisateur', $id_utilisateur);
            $stmt->bindParam(':id_categorie', $id_categorie);
            
            if ($stmt->execute()) {
                $message = 'La tâche a été ajoutée avec succès.';

                // Ajout automatique dans emplois_du_temps
                $id_tache = $db->lastInsertId();
                $date_debut = $date_echeance ? date('Y-m-d', strtotime($date_echeance)) : date('Y-m-d');
                $date_fin = $date_debut;
                $query_edt = "INSERT INTO emplois_du_temps (titre, date_debut, date_fin, id_utilisateur) VALUES (:titre, :date_debut, :date_fin, :id_utilisateur)";
                $stmt_edt = $db->prepare($query_edt);
                $stmt_edt->bindParam(':titre', $titre);
                $stmt_edt->bindParam(':date_debut', $date_debut);
                $stmt_edt->bindParam(':date_fin', $date_fin);
                $stmt_edt->bindParam(':id_utilisateur', $id_utilisateur);
                $stmt_edt->execute();
                $id_emploi_du_temps = $db->lastInsertId();

                // Ajout automatique dans evenements (calendrier)
                try {
                    $date_debut_event = $date_echeance ? $date_echeance : date('Y-m-d H:i:s');
                    $date_fin_event = $date_debut_event;
                    $query_event = "INSERT INTO evenements (titre, description, date_debut, date_fin, id_emploi_du_temps, id_categorie) VALUES (:titre, :description, :date_debut, :date_fin, :id_emploi_du_temps, :id_categorie)";
                    $stmt_event = $db->prepare($query_event);
                    $stmt_event->bindParam(':titre', $titre);
                    $stmt_event->bindParam(':description', $description);
                    $stmt_event->bindParam(':date_debut', $date_debut_event);
                    $stmt_event->bindParam(':date_fin', $date_fin_event);
                    $stmt_event->bindParam(':id_emploi_du_temps', $id_emploi_du_temps);
                    $stmt_event->bindParam(':id_categorie', $id_categorie);
                    $stmt_event->execute();
                } catch (PDOException $e) {
                    error_log('Erreur ajout calendrier : ' . $e->getMessage());
                }
                $type_message = 'success';
                // Stocker l'URL de redirection
                $_SESSION['redirect_url'] = 'index.php?page=taches&message=' . urlencode($message) . '&type=' . $type_message;
            } else {
                $message = 'Une erreur est survenue lors de l\'ajout de la tâche.';
                $type_message = 'danger';
            }
        } elseif (isset($_POST['modifier']) && isset($_POST['id'])) {
            // ... (le reste du code modification inchangé)
        }
    }
}

// Traitement des actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$type_message = '';
$tache_a_modifier = null;

// Traiter la suppression d'une tâche
if ($action === 'supprimer' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Vérifier que la tâche appartient bien à l'utilisateur
    $query_verify = "SELECT COUNT(*) FROM taches WHERE id = :id AND id_utilisateur = :id_utilisateur";
    $stmt_verify = $db->prepare($query_verify);
    $stmt_verify->bindParam(':id', $id);
    $stmt_verify->bindParam(':id_utilisateur', $id_utilisateur);
    $stmt_verify->execute();
    
    if ($stmt_verify->fetchColumn() > 0) {
        $query = "DELETE FROM taches WHERE id = :id AND id_utilisateur = :id_utilisateur";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':id_utilisateur', $id_utilisateur);
        
        if ($stmt->execute()) {
            $message = 'La tâche a été supprimée avec succès.';
            $type_message = 'success';
        } else {
            $message = 'Une erreur est survenue lors de la suppression de la tâche.';
            $type_message = 'danger';
        }
    } else {
        $message = 'Vous n\'êtes pas autorisé à supprimer cette tâche.';
        $type_message = 'danger';
    }
} 
// Charger une tâche pour modification
elseif ($action === 'modifier' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $query = "SELECT * FROM taches WHERE id = :id AND id_utilisateur = :id_utilisateur";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':id_utilisateur', $id_utilisateur);
    $stmt->execute();
    
    $tache_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tache_a_modifier) {
        $message = 'Tâche introuvable ou vous n\'êtes pas autorisé à la modifier.';
        $type_message = 'danger';
    }
}

// Récupérer le message de la redirection si présent
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = $_GET['message'];
    $type_message = $_GET['type'];
}

// Traiter la mise à jour du statut par AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status' && !headers_sent()) {
    header('Content-Type: application/json');
    // Vérifier la session AVANT tout traitement
    if (!isset($_SESSION['utilisateur_id'])) {
        echo json_encode(['success' => false, 'error' => 'Session expirée ou utilisateur non connecté']);
        exit;
    }
    $task_id = isset($_POST['task_id']) ? $_POST['task_id'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    if (!empty($task_id) && !empty($status)) {
        // Vérifier que la tâche appartient bien à l'utilisateur
        $query_verify = "SELECT COUNT(*) FROM taches WHERE id = :id AND id_utilisateur = :id_utilisateur";
        $stmt_verify = $db->prepare($query_verify);
        $stmt_verify->bindParam(':id', $task_id);
        $stmt_verify->bindParam(':id_utilisateur', $id_utilisateur);
        $stmt_verify->execute();
        if ($stmt_verify->fetchColumn() > 0) {
            $query = "UPDATE taches SET statut = :statut WHERE id = :id AND id_utilisateur = :id_utilisateur";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':statut', $status);
            $stmt->bindParam(':id', $task_id);
            $stmt->bindParam(':id_utilisateur', $id_utilisateur);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    }
    exit;
}

// Récupérer les catégories de l'utilisateur
$query_categories = "SELECT * FROM categories WHERE id_utilisateur = :id_utilisateur OR id_utilisateur IS NULL ORDER BY nom ASC";
$stmt_categories = $db->prepare($query_categories);
$stmt_categories->bindParam(':id_utilisateur', $id_utilisateur);
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les tâches de l'utilisateur avec filtres
$where_clauses = ["t.id_utilisateur = :id_utilisateur"];
$params = [':id_utilisateur' => $id_utilisateur];

// Filtres
$filtre_statut = isset($_GET['filtre_statut']) ? $_GET['filtre_statut'] : '';
$filtre_priorite = isset($_GET['filtre_priorite']) ? $_GET['filtre_priorite'] : '';
$filtre_categorie = isset($_GET['filtre_categorie']) ? $_GET['filtre_categorie'] : '';

if (!empty($filtre_statut)) {
    $where_clauses[] = "statut = :statut";
    $params[':statut'] = $filtre_statut;
}

if (!empty($filtre_priorite)) {
    $where_clauses[] = "priorite = :priorite";
    $params[':priorite'] = $filtre_priorite;
}

if (!empty($filtre_categorie)) {
    $where_clauses[] = "t.id_categorie = :id_categorie";
    $params[':id_categorie'] = $filtre_categorie;
}

$query_taches = "SELECT t.*, c.nom as categorie_nom, c.couleur as categorie_couleur
                FROM taches t
                LEFT JOIN categories c ON t.id_categorie = c.id
                WHERE " . implode(" AND ", $where_clauses) . "
                ORDER BY 
                    CASE WHEN t.statut = 'terminee' THEN 1 ELSE 0 END,
                    CASE WHEN t.date_echeance IS NULL THEN 1 ELSE 0 END,
                    t.date_echeance ASC,
                    FIELD(t.priorite, 'haute', 'moyenne', 'basse')";

$stmt_taches = $db->prepare($query_taches);
foreach ($params as $key => $value) {
    $stmt_taches->bindValue($key, $value);
}
$stmt_taches->execute();
$taches = $stmt_taches->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">
            <i class="fas fa-tasks"></i> Gestion des tâches
            <?php if ($action === 'ajouter'): ?>
            - Ajout d'une nouvelle tâche
            <?php elseif ($action === 'modifier'): ?>
            - Modification d'une tâche
            <?php endif; ?>
        </h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $type_message; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'ajouter' || $action === 'modifier'): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php if ($action === 'modifier' && $tache_a_modifier): ?>
                        <i class="fas fa-edit"></i> Modifier la tâche
                    <?php else: ?>
                        <i class="fas fa-plus"></i> Ajouter une tâche
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="post" action="index.php?page=taches" class="task-form">
    <div class="row mb-2">
        <div class="col-md-6">
            <input type="text" class="form-control mb-2" name="nom_template" placeholder="Nom du template (optionnel)">
        </div>
        <div class="col-md-6">
            <input type="text" class="form-control mb-2" name="desc_template" placeholder="Description du template (optionnel)">
        </div>
    </div>
    <button type="submit" name="enregistrer_template" class="btn btn-outline-primary mb-3"><i class="fas fa-magic"></i> Enregistrer comme template</button>

                    <?php if ($action === 'modifier' && $tache_a_modifier): ?>
                        <input type="hidden" name="id" value="<?php echo $tache_a_modifier['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="titre" class="form-label">Titre*</label>
                            <input type="text" class="form-control" id="titre" name="titre" required
                                   value="<?php echo $tache_a_modifier ? htmlspecialchars($tache_a_modifier['titre']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="date_echeance" class="form-label">Date d'échéance</label>
                            <input type="date" class="form-control date-input" id="date_echeance" name="date_echeance"
                                   value="<?php echo $tache_a_modifier && $tache_a_modifier['date_echeance'] ? date('Y-m-d', strtotime($tache_a_modifier['date_echeance'])) : ''; ?>">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $tache_a_modifier ? htmlspecialchars($tache_a_modifier['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="priorite" class="form-label">Priorité</label>
                            <select class="form-select" id="priorite" name="priorite">
                                <option value="basse" <?php echo $tache_a_modifier && $tache_a_modifier['priorite'] === 'basse' ? 'selected' : ''; ?>>Basse</option>
                                <option value="moyenne" <?php echo !$tache_a_modifier || $tache_a_modifier && $tache_a_modifier['priorite'] === 'moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                                <option value="haute" <?php echo $tache_a_modifier && $tache_a_modifier['priorite'] === 'haute' ? 'selected' : ''; ?>>Haute</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <option value="a_faire" <?php echo !$tache_a_modifier || $tache_a_modifier && $tache_a_modifier['statut'] === 'a_faire' ? 'selected' : ''; ?>>À faire</option>
                                <option value="en_cours" <?php echo $tache_a_modifier && $tache_a_modifier['statut'] === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                <option value="terminee" <?php echo $tache_a_modifier && $tache_a_modifier['statut'] === 'terminee' ? 'selected' : ''; ?>>Terminée</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="temps_estime" class="form-label">Temps estimé (minutes)</label>
                            <input type="number" class="form-control" id="temps_estime" name="temps_estime" min="0"
                                   value="<?php echo $tache_a_modifier && $tache_a_modifier['temps_estime'] ? $tache_a_modifier['temps_estime'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_categorie" class="form-label">Catégorie</label>
                        <select class="form-select" id="id_categorie" name="id_categorie">
                            <option value="">Aucune catégorie</option>
                            <?php foreach ($categories as $categorie): ?>
                                <option value="<?php echo $categorie['id']; ?>" 
                                        <?php echo $tache_a_modifier && $tache_a_modifier['id_categorie'] == $categorie['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categorie['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if ($action === 'modifier' && $tache_a_modifier): ?>
                            <button type="submit" name="modifier" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                            <a href="index.php?page=taches" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        <?php else: ?>
                            <button type="submit" name="ajouter" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Ajouter la tâche
                            </button>
                            <a href="index.php?page=taches" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($action !== 'ajouter'): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list"></i> Liste des tâches</h5>
                <div>
                    <a href="index.php?page=taches&action=ajouter"<?php if (!empty($templates)): ?>
                        <div class="mb-4">
                            <div class="fw-bold mb-2"><i class="fas fa-magic"></i> Mes templates de tâche :</div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($templates as $tpl): ?>
                                    <button type="button" class="btn btn-outline-secondary btn-sm template-btn" data-template='<?php echo htmlspecialchars($tpl['contenu'], ENT_QUOTES); ?>'>
                                        <i class="fas fa-copy me-1"></i><?php echo htmlspecialchars($tpl['nom']); ?>
                                    </button>
                                    <form method="post" action="" style="display:inline;">
                                        <input type="hidden" name="template_id" value="<?php echo $tpl['id']; ?>">
                                        <button type="submit" name="supprimer_template" class="btn btn-outline-danger btn-sm" onclick="return confirm('Supprimer ce template ?');"><i class="fas fa-trash"></i></button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <a href="index.php?page=taches&action=ajouter" class="btn btn-sm btn-success me-2">
                        <i class="fas fa-plus"></i> Nouvelle tâche
                    </a>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilters">
                        <i class="fas fa-filter"></i> Filtres
                    </button>
                </div>
            </div>
            
            <div class="collapse" id="collapseFilters">
                <div class="card-body bg-light">
                    <form method="get" action="index.php">
                        <input type="hidden" name="page" value="taches">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label for="filtre_statut" class="form-label">Statut</label>
                                <select class="form-select" id="filtre_statut" name="filtre_statut">
                                    <option value="">Tous</option>
                                    <option value="a_faire" <?php echo $filtre_statut === 'a_faire' ? 'selected' : ''; ?>>À faire</option>
                                    <option value="en_cours" <?php echo $filtre_statut === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                    <option value="terminee" <?php echo $filtre_statut === 'terminee' ? 'selected' : ''; ?>>Terminée</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="filtre_priorite" class="form-label">Priorité</label>
                                <select class="form-select" id="filtre_priorite" name="filtre_priorite">
                                    <option value="">Toutes</option>
                                    <option value="basse" <?php echo $filtre_priorite === 'basse' ? 'selected' : ''; ?>>Basse</option>
                                    <option value="moyenne" <?php echo $filtre_priorite === 'moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                                    <option value="haute" <?php echo $filtre_priorite === 'haute' ? 'selected' : ''; ?>>Haute</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="filtre_categorie" class="form-label">Catégorie</label>
                                <select class="form-select" id="filtre_categorie" name="filtre_categorie">
                                    <option value="">Toutes</option>
                                    <?php foreach ($categories as $categorie): ?>
                                        <option value="<?php echo $categorie['id']; ?>" 
                                                <?php echo $filtre_categorie == $categorie['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categorie['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                            <a href="index.php?page=taches" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (empty($taches)): ?>
                    <p class="text-center text-muted my-5">Aucune tâche trouvée.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Échéance</th>
                                    <th>Catégorie</th>
                                    <th>Priorité</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($taches as $tache): ?>
                                    <tr class="<?php echo $tache['statut'] === 'terminee' ? 'tache-terminee' : ''; ?>">
                                        <td><?php echo htmlspecialchars($tache['titre']); ?></td>
                                        <td>
                                            <?php if ($tache['date_echeance']): ?>
                                                <?php 
                                                    $date = new DateTime($tache['date_echeance']);
                                                    echo $date->format('d/m/Y'); 
                                                ?>
                                            <?php else: ?>
                                                <span class="text-muted">Non définie</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($tache['categorie_nom']): ?>
                                                <span class="categorie-badge" style="background-color: <?php echo $tache['categorie_couleur']; ?>">
                                                    <?php echo htmlspecialchars($tache['categorie_nom']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Non catégorisée</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                if ($tache['priorite'] === 'haute') echo 'danger';
                                                elseif ($tache['priorite'] === 'moyenne') echo 'warning';
                                                else echo 'info';
                                            ?>">
                                                <?php echo ucfirst($tache['priorite']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm status-select" 
                                                    data-task-id="<?php echo $tache['id']; ?>"
                                                    data-original-value="<?php echo $tache['statut']; ?>">
                                                <option value="a_faire" <?php echo $tache['statut'] === 'a_faire' ? 'selected' : ''; ?>>À faire</option>
                                                <option value="en_cours" <?php echo $tache['statut'] === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                                <option value="terminee" <?php echo $tache['statut'] === 'terminee' ? 'selected' : ''; ?>>Terminée</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="index.php?page=taches&action=modifier&id=<?php echo $tache['id']; ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="index.php?page=taches&action=supprimer&id=<?php echo $tache['id']; ?>" 
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirmDelete('Êtes-vous sûr de vouloir supprimer cette tâche ?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>