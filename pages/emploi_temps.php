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
$evenement_a_modifier = null;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Récupérer les catégories pour les utiliser dans les formulaires
$query_categories = "SELECT * FROM categories WHERE id_utilisateur = :id_utilisateur OR id_utilisateur IS NULL ORDER BY nom ASC";
$stmt_categories = $db->prepare($query_categories);
$stmt_categories->bindParam(':id_utilisateur', $id_utilisateur);
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

// Récupérer ou créer l'emploi du temps de l'utilisateur
$query_emploi = "SELECT * FROM emplois_du_temps WHERE id_utilisateur = :id_utilisateur LIMIT 1";
$stmt_emploi = $db->prepare($query_emploi);
$stmt_emploi->bindParam(':id_utilisateur', $id_utilisateur);
$stmt_emploi->execute();
$emploi_du_temps = $stmt_emploi->fetch(PDO::FETCH_ASSOC);

if (!$emploi_du_temps) {
    // Créer un emploi du temps par défaut pour l'utilisateur
    $query_create = "INSERT INTO emplois_du_temps (titre, date_debut, date_fin, id_utilisateur) 
                    VALUES ('Mon emploi du temps', CURRENT_DATE(), DATE_ADD(CURRENT_DATE(), INTERVAL 1 YEAR), :id_utilisateur)";
    $stmt_create = $db->prepare($query_create);
    $stmt_create->bindParam(':id_utilisateur', $id_utilisateur);
    
    if ($stmt_create->execute()) {
        $id_emploi_du_temps = $db->lastInsertId();
        
        // Récupérer l'emploi du temps créé
        $query_emploi = "SELECT * FROM emplois_du_temps WHERE id = :id";
        $stmt_emploi = $db->prepare($query_emploi);
        $stmt_emploi->bindParam(':id', $id_emploi_du_temps);
        $stmt_emploi->execute();
        $emploi_du_temps = $stmt_emploi->fetch(PDO::FETCH_ASSOC);
    }
}

// Récupérer les tâches actives de l'utilisateur pour formulaire
$query_taches_select = "SELECT id, titre FROM taches WHERE id_utilisateur = :id_utilisateur AND statut != 'terminee' ORDER BY date_echeance ASC, titre ASC";
$stmt_taches_select = $db->prepare($query_taches_select);
$stmt_taches_select->bindParam(':id_utilisateur', $id_utilisateur);
$stmt_taches_select->execute();
$taches_select = $stmt_taches_select->fetchAll(PDO::FETCH_ASSOC);

// Traitement des actions sur les événements
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajouter_evenement']) || isset($_POST['modifier_evenement'])) {
        $titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $date_debut = isset($_POST['date_debut']) ? $_POST['date_debut'] : '';
        $heure_debut = isset($_POST['heure_debut']) ? $_POST['heure_debut'] : '00:00';
        $date_fin = isset($_POST['date_fin']) ? $_POST['date_fin'] : '';
        $heure_fin = isset($_POST['heure_fin']) ? $_POST['heure_fin'] : '00:00';
        $lieu = isset($_POST['lieu']) ? trim($_POST['lieu']) : '';
        $id_categorie = isset($_POST['id_categorie']) && !empty($_POST['id_categorie']) ? $_POST['id_categorie'] : null;
        
        // Validation des données
        if (empty($titre) || empty($date_debut) || empty($date_fin)) {
            $message = 'Veuillez remplir tous les champs obligatoires.';
            $type_message = 'danger';
        } else {
            // Formater les dates et heures
            $datetime_debut = $date_debut . ' ' . $heure_debut . ':00';
            $datetime_fin = $date_fin . ' ' . $heure_fin . ':00';
            
            if (isset($_POST['ajouter_evenement'])) {
                // Ajout d'un nouvel événement
                $query = "INSERT INTO evenements (titre, description, date_debut, date_fin, lieu, id_emploi_du_temps, id_categorie) 
                          VALUES (:titre, :description, :date_debut, :date_fin, :lieu, :id_emploi_du_temps, :id_categorie)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':titre', $titre);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':date_debut', $datetime_debut);
                $stmt->bindParam(':date_fin', $datetime_fin);
                $stmt->bindParam(':lieu', $lieu);
                $stmt->bindParam(':id_emploi_du_temps', $emploi_du_temps['id']);
                $stmt->bindParam(':id_categorie', $id_categorie);
                
                if ($stmt->execute()) {
                    $message = 'L\'événement a été ajouté avec succès.';
                    $type_message = 'success';

                    // Associer les tâches sélectionnées
                    if (!empty($_POST['taches_associees']) && is_array($_POST['taches_associees'])) {
                        foreach ($_POST['taches_associees'] as $id_tache_associee) {
                            $query_assoc = "INSERT IGNORE INTO emploi_temps_taches (id_emploi_du_temps, id_tache) VALUES (:id_edt, :id_tache)";
                            $stmt_assoc = $db->prepare($query_assoc);
                            $stmt_assoc->bindParam(':id_edt', $emploi_du_temps['id']);
                            $stmt_assoc->bindParam(':id_tache', $id_tache_associee);
                            $stmt_assoc->execute();
                        }
                    }
                } else {
                    $message = 'Une erreur est survenue lors de l\'ajout de l\'événement.';
                    $type_message = 'danger';
                }
            } elseif (isset($_POST['modifier_evenement']) && isset($_POST['id'])) {
                // Mettre à jour les associations de tâches
                $id_edt = $emploi_du_temps['id'];
                // Supprimer les anciennes associations
                $query_del = "DELETE FROM emploi_temps_taches WHERE id_emploi_du_temps = :id_edt";
                $stmt_del = $db->prepare($query_del);
                $stmt_del->bindParam(':id_edt', $id_edt);
                $stmt_del->execute();
                // Ajouter les nouvelles associations
                if (!empty($_POST['taches_associees']) && is_array($_POST['taches_associees'])) {
                    foreach ($_POST['taches_associees'] as $id_tache_associee) {
                        $query_assoc = "INSERT IGNORE INTO emploi_temps_taches (id_emploi_du_temps, id_tache) VALUES (:id_edt, :id_tache)";
                        $stmt_assoc = $db->prepare($query_assoc);
                        $stmt_assoc->bindParam(':id_edt', $id_edt);
                        $stmt_assoc->bindParam(':id_tache', $id_tache_associee);
                        $stmt_assoc->execute();
                    }
                }


                // Modification d'un événement existant
                $id = $_POST['id'];
                
                // Vérifier que l'événement appartient bien à l'utilisateur
                $query_verify = "SELECT COUNT(*) FROM evenements e 
                                JOIN emplois_du_temps edt ON e.id_emploi_du_temps = edt.id 
                                WHERE e.id = :id AND edt.id_utilisateur = :id_utilisateur";
                $stmt_verify = $db->prepare($query_verify);
                $stmt_verify->bindParam(':id', $id);
                $stmt_verify->bindParam(':id_utilisateur', $id_utilisateur);
                $stmt_verify->execute();
                
                if ($stmt_verify->fetchColumn() > 0) {
                    $query = "UPDATE evenements 
                              SET titre = :titre, description = :description, date_debut = :date_debut, 
                                  date_fin = :date_fin, lieu = :lieu, id_categorie = :id_categorie 
                              WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':titre', $titre);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':date_debut', $datetime_debut);
                    $stmt->bindParam(':date_fin', $datetime_fin);
                    $stmt->bindParam(':lieu', $lieu);
                    $stmt->bindParam(':id_categorie', $id_categorie);
                    $stmt->bindParam(':id', $id);
                    
                    if ($stmt->execute()) {
                        $message = 'L\'événement a été modifié avec succès.';
                        $type_message = 'success';
                    } else {
                        $message = 'Une erreur est survenue lors de la modification de l\'événement.';
                        $type_message = 'danger';
                    }
                } else {
                    $message = 'Vous n\'êtes pas autorisé à modifier cet événement.';
                    $type_message = 'danger';
                }
            }
        }
    }
}

// Traiter la suppression d'un événement
if ($action === 'supprimer' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Vérifier que l'événement appartient bien à l'utilisateur
    $query_verify = "SELECT COUNT(*) FROM evenements e 
                    JOIN emplois_du_temps edt ON e.id_emploi_du_temps = edt.id 
                    WHERE e.id = :id AND edt.id_utilisateur = :id_utilisateur";
    $stmt_verify = $db->prepare($query_verify);
    $stmt_verify->bindParam(':id', $id);
    $stmt_verify->bindParam(':id_utilisateur', $id_utilisateur);
    $stmt_verify->execute();
    
    if ($stmt_verify->fetchColumn() > 0) {
        $query = "DELETE FROM evenements WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $message = 'L\'événement a été supprimé avec succès.';
            $type_message = 'success';
        } else {
            $message = 'Une erreur est survenue lors de la suppression de l\'événement.';
            $type_message = 'danger';
        }
    } else {
        $message = 'Vous n\'êtes pas autorisé à supprimer cet événement.';
        $type_message = 'danger';
    }
}

// Charger un événement pour modification
if ($action === 'modifier' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $query = "SELECT * FROM evenements e 
              JOIN emplois_du_temps edt ON e.id_emploi_du_temps = edt.id 
              WHERE e.id = :id AND edt.id_utilisateur = :id_utilisateur";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':id_utilisateur', $id_utilisateur);
    $stmt->execute();
    
    $evenement_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$evenement_a_modifier) {
        $message = 'Événement introuvable ou vous n\'êtes pas autorisé à le modifier.';
        $type_message = 'danger';
    }
}

// Récupérer la semaine à afficher
$semaine = isset($_GET['semaine']) ? intval($_GET['semaine']) : 0; // 0 = semaine actuelle
$date_debut_semaine = date('Y-m-d', strtotime("monday this week $semaine week"));
$date_fin_semaine = date('Y-m-d', strtotime("sunday this week $semaine week"));

// Récupérer les événements de la semaine et les tâches avec date d'échéance
// Debug : afficher les dates de la semaine
error_log("Date début: " . $date_debut_semaine);
error_log("Date fin: " . $date_fin_semaine);

// Récupérer les événements de la semaine
$query_evenements = "
    SELECT 
        e.id,
        e.titre,
        e.date_debut,
        e.date_fin,
        e.lieu,
        e.description,
        ec.id as id_categorie,
        ec.nom as categorie_nom,
        ec.couleur as categorie_couleur,
        'evenement' as type
    FROM evenements e
    JOIN emplois_du_temps edt ON e.id_emploi_du_temps = edt.id
    LEFT JOIN categories ec ON e.id_categorie = ec.id
    WHERE edt.id_utilisateur = :id_utilisateur
    AND DATE(e.date_debut) >= :date_debut
    AND DATE(e.date_debut) <= :date_fin
    ORDER BY e.date_debut ASC";

$stmt_evenements = $db->prepare($query_evenements);
$stmt_evenements->bindParam(':id_utilisateur', $id_utilisateur);
$stmt_evenements->bindParam(':date_debut', $date_debut_semaine);
$stmt_evenements->bindParam(':date_fin', $date_fin_semaine);
$stmt_evenements->execute();
$evenements = $stmt_evenements->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les tâches de la semaine
$query_taches = "
    SELECT 
        t.id,
        t.titre,
        t.date_echeance as date_debut,
        t.date_echeance as date_fin,
        '' as lieu,
        t.description,
        tc.id as id_categorie,
        tc.nom as categorie_nom,
        tc.couleur as categorie_couleur,
        'tache' as type,
        t.statut
    FROM taches t
    LEFT JOIN categories tc ON t.id_categorie = tc.id
    WHERE t.id_utilisateur = :id_utilisateur
    AND t.date_echeance IS NOT NULL
    AND DATE(t.date_echeance) >= :date_debut
    AND DATE(t.date_echeance) <= :date_fin
    ORDER BY t.date_echeance ASC";

$stmt_taches = $db->prepare($query_taches);
$stmt_taches->bindParam(':id_utilisateur', $id_utilisateur);
$stmt_taches->bindParam(':date_debut', $date_debut_semaine);
$stmt_taches->bindParam(':date_fin', $date_fin_semaine);
$stmt_taches->execute();
$taches = $stmt_taches->fetchAll(PDO::FETCH_ASSOC);

// Organiser les événements et tâches par jour
$evenements_par_jour = [];
foreach ($evenements as $evenement) {
    $jour = date('Y-m-d', strtotime($evenement['date_debut']));
    if (!isset($evenements_par_jour[$jour])) {
        $evenements_par_jour[$jour] = [];
    }
    $evenements_par_jour[$jour][] = $evenement;
}
foreach ($taches as $tache) {
    $jour = date('Y-m-d', strtotime($tache['date_debut']));
    if (!isset($evenements_par_jour[$jour])) {
        $evenements_par_jour[$jour] = [];
    }
    $evenements_par_jour[$jour][] = $tache;
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <h2 class="mb-4 display-6">
                <i class="fas fa-calendar-week"></i> Emploi du temps
            </h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $type_message; ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-5 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <?php if ($action === 'modifier' && $evenement_a_modifier): ?>
                            <i class="fas fa-edit"></i> Modifier un événement
                        <?php else: ?>
                            <i class="fas fa-plus"></i> Ajouter un événement
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?page=emploi_temps">
                        <?php if ($action === 'modifier' && $evenement_a_modifier): ?>
                            <input type="hidden" name="id" value="<?php echo $evenement_a_modifier['id']; ?>">
                        <?php endif; ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="titre" class="form-label">Titre*</label>
                                <input type="text" class="form-control" id="titre" name="titre" required
                                       value="<?php echo $evenement_a_modifier ? htmlspecialchars($evenement_a_modifier['titre']) : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="lieu" class="form-label">Lieu</label>
                                <input type="text" class="form-control" id="lieu" name="lieu"
                                       value="<?php echo $evenement_a_modifier ? htmlspecialchars($evenement_a_modifier['lieu']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"><?php echo $evenement_a_modifier ? htmlspecialchars($evenement_a_modifier['description']) : ''; ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="date_debut" class="form-label">Date de début*</label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" required
                                       value="<?php echo $evenement_a_modifier ? date('Y-m-d', strtotime($evenement_a_modifier['date_debut'])) : date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="heure_debut" class="form-label">Heure de début*</label>
                                <input type="time" class="form-control" id="heure_debut" name="heure_debut" required
                                       value="<?php echo $evenement_a_modifier ? date('H:i', strtotime($evenement_a_modifier['date_debut'])) : '08:00'; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_fin" class="form-label">Date de fin*</label>
                                <input type="date" class="form-control" id="date_fin" name="date_fin" required
                                       value="<?php echo $evenement_a_modifier ? date('Y-m-d', strtotime($evenement_a_modifier['date_fin'])) : date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="heure_fin" class="form-label">Heure de fin*</label>
                                <input type="time" class="form-control" id="heure_fin" name="heure_fin" required
                                       value="<?php echo $evenement_a_modifier ? date('H:i', strtotime($evenement_a_modifier['date_fin'])) : '09:00'; ?>">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="id_categorie" class="form-label">Catégorie</label>
                            <select class="form-select" id="id_categorie" name="id_categorie">
                                <option value="">Aucune catégorie</option>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?php echo $categorie['id']; ?>" 
                                            <?php echo $evenement_a_modifier && $evenement_a_modifier['id_categorie'] == $categorie['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categorie['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="taches_associees" class="form-label">Tâches associées</label>
                            <select class="form-select" id="taches_associees" name="taches_associees[]" multiple>
                                <?php foreach ($taches_select as $tache): ?>
                                    <option value="<?php echo $tache['id']; ?>">
                                        <?php echo htmlspecialchars($tache['titre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Maintenez Ctrl (Windows) ou Cmd (Mac) pour sélectionner plusieurs tâches.</div>
                        </div>

                        <div class="d-flex gap-3 justify-content-end mt-4">
                            <?php if ($action === 'modifier' && $evenement_a_modifier): ?>
                                <button type="submit" name="modifier_evenement" class="btn btn-primary px-4">
                                    <i class="fas fa-save"></i> Enregistrer les modifications
                                </button>
                                <a href="index.php?page=emploi_temps" class="btn btn-secondary px-4">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                            <?php else: ?>
                                <button type="submit" name="ajouter_evenement" class="btn btn-primary px-4">
                                    <i class="fas fa-plus"></i> Ajouter l'événement
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Semaine du <?php echo date('d/m/Y', strtotime($date_debut_semaine)); ?> au <?php echo date('d/m/Y', strtotime($date_fin_semaine)); ?></h5>
                <div>
                    <a href="index.php?page=emploi_temps&semaine=<?php echo $semaine - 1; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-chevron-left"></i> Semaine précédente
                    </a>
                    <a href="index.php?page=emploi_temps&semaine=0" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-calendar-day"></i> Semaine actuelle
                    </a>
                    <a href="index.php?page=emploi_temps&semaine=<?php echo $semaine + 1; ?>" class="btn btn-sm btn-outline-secondary">
                        Semaine suivante <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="emploi-temps-container">
                    <table class="table table-bordered emploi-temps-table">
                        <thead>
                            <tr>
                                <th>Horaire</th>
                                <?php
                                for ($i = 0; $i < 7; $i++) {
                                    $jour = date('Y-m-d', strtotime("$date_debut_semaine +$i days"));
                                    $nom_jour = date('l', strtotime($jour));
                                    $numero_jour = date('d/m', strtotime($jour));
                                    
                                    // Traduire le jour en français
                                    switch ($nom_jour) {
                                        case 'Monday': $nom_jour = 'Lundi'; break;
                                        case 'Tuesday': $nom_jour = 'Mardi'; break;
                                        case 'Wednesday': $nom_jour = 'Mercredi'; break;
                                        case 'Thursday': $nom_jour = 'Jeudi'; break;
                                        case 'Friday': $nom_jour = 'Vendredi'; break;
                                        case 'Saturday': $nom_jour = 'Samedi'; break;
                                        case 'Sunday': $nom_jour = 'Dimanche'; break;
                                    }
                                    
                                    $classe = date('Y-m-d') === $jour ? 'table-active' : '';
                                    echo "<th class=\"$classe\">$nom_jour<br>$numero_jour</th>";
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Heures de la journée (de 8h à 20h)
                            for ($heure = 8; $heure <= 20; $heure++) {
                                echo "<tr>";
                                echo "<td class=\"text-center\">" . sprintf("%02d:00", $heure) . "</td>";
                                
                                for ($i = 0; $i < 7; $i++) {
                                    $jour = date('Y-m-d', strtotime("$date_debut_semaine +$i days"));
                                    $classe = date('Y-m-d') === $jour ? 'table-active' : '';
                                    echo "<td class=\"$classe\">";
                                    
                                    // Afficher les événements et tâches de cette heure
                                    if (isset($evenements_par_jour[$jour])) {
                                        foreach ($evenements_par_jour[$jour] as $item) {
                                            $heure_debut = intval(date('H', strtotime($item['date_debut'])));
                                            $heure_fin = intval(date('H', strtotime($item['date_fin'])));
                                            
                                            if ($heure >= $heure_debut && $heure < $heure_fin) {
                                                $couleur_bg = $item['categorie_couleur'] ?? '#6c757d';
                                                $couleur_texte = '#ffffff';
                                                $est_tache = isset($item['type']) && $item['type'] === 'tache';
                                                
                                                $classe_item = $est_tache ? 'tache' : 'evenement';
                                                $icone = $est_tache ? '<i class="fas fa-tasks me-1"></i>' : '<i class="fas fa-calendar-day me-1"></i>';
                                                
                                                // Ajouter une classe supplémentaire pour les tâches terminées
                                                if ($est_tache && isset($item['statut']) && $item['statut'] === 'terminee') {
                                                    $classe_item .= ' tache-terminee';
                                                }
                                                
                                                echo "<div class=\"$classe_item p-1 mb-1\" data-task-id=\"{$item['id']}\" style=\"background-color: $couleur_bg; color: $couleur_texte;\">";
                                                echo "<div class=\"fw-bold\">$icone" . htmlspecialchars($item['titre']) . "</div>";
                                                echo "<div class=\"small\">" . date('H:i', strtotime($item['date_debut'])) . " - " . date('H:i', strtotime($item['date_fin'])) . "</div>";
                                                
                                                if (!empty($item['lieu'])) {
                                                    echo "<div class=\"small\"><i class=\"fas fa-map-marker-alt\"></i> " . htmlspecialchars($item['lieu']) . "</div>";
                                                }
                                                
                                                echo "<div class=\"mt-1\">";
                                                if ($est_tache) {
                                                    // Pour les tâches, ajouter un bouton pour changer le statut
                                                    $statut = $item['statut'] ?? 'a_faire';
                                                    $checked = $statut === 'terminee' ? 'checked' : '';
                                                    echo "<div class=\"form-check d-inline-block me-2\">";
                                                    echo "<input class=\"form-check-input task-status\" type=\"checkbox\" data-task-id=\"{$item['id']}\" $checked>";
                                                    echo "<label class=\"form-check-label small\">Terminée</label>";
                                                    echo "</div>";
                                                    echo "<a href=\"index.php?page=taches&action=modifier&id=" . $item['id'] . "\" class=\"btn btn-sm btn-light me-1\"><i class=\"fas fa-edit\"></i></a>";
                                                } else {
                                                    echo "<a href=\"index.php?page=emploi_temps&action=modifier&id=" . $item['id'] . "\" class=\"btn btn-sm btn-light me-1\"><i class=\"fas fa-edit\"></i></a>";
                                                }
                                                
                                                $page = $est_tache ? 'taches' : 'emploi_temps';
                                                $message = $est_tache ? 'cette tâche' : 'cet événement';
                                                echo "<a href=\"index.php?page=$page&action=supprimer&id=" . $item['id'] . "\" class=\"btn btn-sm btn-light\" onclick=\"return confirmDelete('Êtes-vous sûr de vouloir supprimer $message ?');\"><i class=\"fas fa-trash\"></i></a>";
                                                echo "</div>";
                                                echo "</div>";
                                            }
                                        }
                                    }
                                    
                                    echo "</td>";
                                }
                                
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($evenements)): ?>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> Aucun événement prévu pour cette semaine. Utilisez le formulaire ci-dessus pour ajouter des événements à votre emploi du temps.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>