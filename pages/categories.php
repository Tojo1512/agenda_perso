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

// Traitement des actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$type_message = '';
$categorie_a_modifier = null;

// Traiter l'ajout ou la modification d'une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['ajouter']) || isset($_POST['modifier']))) {
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $couleur = isset($_POST['couleur']) ? $_POST['couleur'] : '#CCCCCC';
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    
    // Validation du nom
    if (empty($nom)) {
        $message = 'Le nom de la catégorie est obligatoire.';
        $type_message = 'danger';
    } else {
        if (isset($_POST['ajouter'])) {
            // Ajout d'une nouvelle catégorie
            $query = "INSERT INTO categories (nom, couleur, type, id_utilisateur) 
                      VALUES (:nom, :couleur, :type, :id_utilisateur)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':couleur', $couleur);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':id_utilisateur', $id_utilisateur);
            
            if ($stmt->execute()) {
                $message = 'La catégorie a été ajoutée avec succès.';
                $type_message = 'success';
            } else {
                $message = 'Une erreur est survenue lors de l\'ajout de la catégorie.';
                $type_message = 'danger';
            }
        } elseif (isset($_POST['modifier']) && isset($_POST['id'])) {
            // Modification d'une catégorie existante
            $id = $_POST['id'];
            
            // Vérifier que la catégorie appartient bien à l'utilisateur
            $query_verify = "SELECT COUNT(*) FROM categories WHERE id = :id AND id_utilisateur = :id_utilisateur";
            $stmt_verify = $db->prepare($query_verify);
            $stmt_verify->bindParam(':id', $id);
            $stmt_verify->bindParam(':id_utilisateur', $id_utilisateur);
            $stmt_verify->execute();
            
            if ($stmt_verify->fetchColumn() > 0) {
                $query = "UPDATE categories 
                          SET nom = :nom, couleur = :couleur, type = :type
                          WHERE id = :id AND id_utilisateur = :id_utilisateur";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':couleur', $couleur);
                $stmt->bindParam(':type', $type);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':id_utilisateur', $id_utilisateur);
                
                if ($stmt->execute()) {
                    $message = 'La catégorie a été modifiée avec succès.';
                    $type_message = 'success';
                } else {
                    $message = 'Une erreur est survenue lors de la modification de la catégorie.';
                    $type_message = 'danger';
                }
            } else {
                $message = 'Vous n\'êtes pas autorisé à modifier cette catégorie.';
                $type_message = 'danger';
            }
        }
    }
} 
// Traiter la suppression d'une catégorie
elseif ($action === 'supprimer' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Vérifier que la catégorie appartient bien à l'utilisateur
    $query_verify = "SELECT COUNT(*) FROM categories WHERE id = :id AND id_utilisateur = :id_utilisateur";
    $stmt_verify = $db->prepare($query_verify);
    $stmt_verify->bindParam(':id', $id);
    $stmt_verify->bindParam(':id_utilisateur', $id_utilisateur);
    $stmt_verify->execute();
    
    if ($stmt_verify->fetchColumn() > 0) {
        // Vérifier si la catégorie est utilisée par des tâches
        $query_tasks = "SELECT COUNT(*) FROM taches WHERE id_categorie = :id_categorie AND id_utilisateur = :id_utilisateur";
        $stmt_tasks = $db->prepare($query_tasks);
        $stmt_tasks->bindParam(':id_categorie', $id);
        $stmt_tasks->bindParam(':id_utilisateur', $id_utilisateur);
        $stmt_tasks->execute();
        
        $nb_tasks = $stmt_tasks->fetchColumn();
        
        if ($nb_tasks > 0) {
            // Mettre à jour les tâches pour enlever la catégorie
            $query_update = "UPDATE taches SET id_categorie = NULL WHERE id_categorie = :id_categorie AND id_utilisateur = :id_utilisateur";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->bindParam(':id_categorie', $id);
            $stmt_update->bindParam(':id_utilisateur', $id_utilisateur);
            $stmt_update->execute();
        }
        
        // Supprimer la catégorie
        $query = "DELETE FROM categories WHERE id = :id AND id_utilisateur = :id_utilisateur";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':id_utilisateur', $id_utilisateur);
        
        if ($stmt->execute()) {
            $message = 'La catégorie a été supprimée avec succès.';
            $type_message = 'success';
        } else {
            $message = 'Une erreur est survenue lors de la suppression de la catégorie.';
            $type_message = 'danger';
        }
    } else {
        $message = 'Vous n\'êtes pas autorisé à supprimer cette catégorie.';
        $type_message = 'danger';
    }
}
// Charger une catégorie pour modification
elseif ($action === 'modifier' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $query = "SELECT * FROM categories WHERE id = :id AND id_utilisateur = :id_utilisateur";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':id_utilisateur', $id_utilisateur);
    $stmt->execute();
    
    $categorie_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$categorie_a_modifier) {
        $message = 'Catégorie introuvable ou vous n\'êtes pas autorisé à la modifier.';
        $type_message = 'danger';
    }
}

// Récupérer toutes les catégories de l'utilisateur
$query_categories = "SELECT c.*, COUNT(t.id) as nb_taches
                    FROM categories c
                    LEFT JOIN taches t ON c.id = t.id_categorie AND t.id_utilisateur = :id_utilisateur
                    WHERE c.id_utilisateur = :id_utilisateur
                    GROUP BY c.id
                    ORDER BY c.nom ASC";
$stmt_categories = $db->prepare($query_categories);
$stmt_categories->bindParam(':id_utilisateur', $id_utilisateur);
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les types de catégories existants
$query_types = "SELECT DISTINCT type FROM categories WHERE type IS NOT NULL AND type != '' ORDER BY type ASC";
$stmt_types = $db->prepare($query_types);
$stmt_types->execute();
$types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">
            <i class="fas fa-tags"></i> Gestion des catégories
        </h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $type_message; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <?php if ($action === 'modifier' && $categorie_a_modifier): ?>
                                <i class="fas fa-edit"></i> Modifier la catégorie
                            <?php else: ?>
                                <i class="fas fa-plus"></i> Ajouter une catégorie
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="index.php?page=categories">
                            <?php if ($action === 'modifier' && $categorie_a_modifier): ?>
                                <input type="hidden" name="id" value="<?php echo $categorie_a_modifier['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom*</label>
                                <input type="text" class="form-control" id="nom" name="nom" required
                                       value="<?php echo $categorie_a_modifier ? htmlspecialchars($categorie_a_modifier['nom']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="couleur" class="form-label">Couleur</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color color-picker" id="couleur" name="couleur"
                                           value="<?php echo $categorie_a_modifier ? $categorie_a_modifier['couleur'] : '#CCCCCC'; ?>">
                                    <div class="color-preview p-2 border" style="background-color: <?php echo $categorie_a_modifier ? $categorie_a_modifier['couleur'] : '#CCCCCC'; ?>; width: 40px;"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Type</label>
                                <input type="text" class="form-control" id="type" name="type" list="types-list"
                                       value="<?php echo $categorie_a_modifier ? htmlspecialchars($categorie_a_modifier['type']) : ''; ?>">
                                <datalist id="types-list">
                                    <?php foreach ($types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <div class="form-text">Ex: scientifique, littéraire, personnel, etc.</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <?php if ($action === 'modifier' && $categorie_a_modifier): ?>
                                    <button type="submit" name="modifier" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Enregistrer les modifications
                                    </button>
                                    <a href="index.php?page=categories" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Annuler
                                    </a>
                                <?php else: ?>
                                    <button type="submit" name="ajouter" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Ajouter la catégorie
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Liste des catégories</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <p class="text-center text-muted my-5">Aucune catégorie trouvée.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Couleur</th>
                                            <th>Nom</th>
                                            <th>Type</th>
                                            <th>Tâches</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $categorie): ?>
                                            <tr>
                                                <td>
                                                    <div class="color-box" style="width: 24px; height: 24px; background-color: <?php echo $categorie['couleur']; ?>; border-radius: 4px;"></div>
                                                </td>
                                                <td><?php echo htmlspecialchars($categorie['nom']); ?></td>
                                                <td>
                                                    <?php if (!empty($categorie['type'])): ?>
                                                        <?php echo htmlspecialchars($categorie['type']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Non défini</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="index.php?page=taches&filtre_categorie=<?php echo $categorie['id']; ?>" 
                                                       class="badge bg-primary text-decoration-none">
                                                        <?php echo $categorie['nb_taches']; ?> tâche(s)
                                                    </a>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="index.php?page=categories&action=modifier&id=<?php echo $categorie['id']; ?>" 
                                                           class="btn btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="index.php?page=categories&action=supprimer&id=<?php echo $categorie['id']; ?>" 
                                                           class="btn btn-outline-danger"
                                                           onclick="return confirmDelete('Êtes-vous sûr de vouloir supprimer cette catégorie ? Les tâches associées seront décatégorisées.');">
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
            </div>
        </div>
    </div>
</div> 