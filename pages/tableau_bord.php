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

// Récupérer les tâches à faire (non terminées) pour l'utilisateur
$query_taches = "SELECT t.*, c.nom as categorie_nom, c.couleur as categorie_couleur
                FROM taches t
                LEFT JOIN categories c ON t.id_categorie = c.id
                WHERE t.id_utilisateur = :id_utilisateur
                AND t.statut != 'terminee'
                ORDER BY 
                    CASE WHEN t.date_echeance IS NULL THEN 1 ELSE 0 END,
                    t.date_echeance ASC,
                    FIELD(t.priorite, 'haute', 'moyenne', 'basse')
                LIMIT 5";

$stmt_taches = $db->prepare($query_taches);
$stmt_taches->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
$stmt_taches->execute();
$taches = $stmt_taches->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les tâches par statut pour les statistiques
$query_stats = "SELECT statut, COUNT(*) as nombre
                FROM taches
                WHERE id_utilisateur = :id_utilisateur
                GROUP BY statut";

$stmt_stats = $db->prepare($query_stats);
$stmt_stats->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
$stmt_stats->execute();
$stats = [];
while ($row = $stmt_stats->fetch(PDO::FETCH_ASSOC)) {
    $stats[$row['statut']] = $row['nombre'];
}

// Calculer le total des tâches
$total_taches = array_sum($stats);

// Récupérer les événements à venir (prochains 7 jours)
$query_evenements = "SELECT e.*, c.nom as categorie_nom, c.couleur as categorie_couleur
                    FROM evenements e
                    LEFT JOIN categories c ON e.id_categorie = c.id
                    LEFT JOIN emplois_du_temps edt ON e.id_emploi_du_temps = edt.id
                    WHERE edt.id_utilisateur = :id_utilisateur
                    AND e.date_debut >= CURRENT_DATE()
                    AND e.date_debut <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
                    ORDER BY e.date_debut ASC
                    LIMIT 5";

$stmt_evenements = $db->prepare($query_evenements);
$stmt_evenements->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
$stmt_evenements->execute();
$evenements = $stmt_evenements->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le nombre de tâches terminées par jour (7 derniers jours)
$query_progression = "SELECT DATE(date_creation) as jour, COUNT(*) as nombre
                     FROM taches
                     WHERE id_utilisateur = :id_utilisateur
                     AND statut = 'terminee'
                     AND date_creation >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
                     GROUP BY DATE(date_creation)
                     ORDER BY jour ASC";

$stmt_progression = $db->prepare($query_progression);
$stmt_progression->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
$stmt_progression->execute();
$progression = $stmt_progression->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">
            <i class="fas fa-tachometer-alt"></i> Tableau de bord
        </h2>
    </div>
</div>

<div class="row">
    <!-- Tâches à faire -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-tasks"></i> Tâches à faire</h5>
                <a href="index.php?page=taches" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-list"></i> Voir tout
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($taches)): ?>
                    <p class="text-center text-muted my-5">Aucune tâche en cours. Bravo !</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($taches as $tache): ?>
                            <div class="list-group-item list-group-item-action tache-<?php echo $tache['priorite']; ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($tache['titre']); ?></h5>
                                    <small>
                                        <?php if ($tache['date_echeance']): ?>
                                            <i class="fas fa-calendar-alt"></i> 
                                            <?php 
                                                $date = new DateTime($tache['date_echeance']);
                                                echo $date->format('d/m/Y'); 
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sans date</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <?php if ($tache['description']): ?>
                                    <p class="mb-1"><?php echo htmlspecialchars($tache['description']); ?></p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <?php if ($tache['categorie_nom']): ?>
                                            <span class="categorie-badge" style="background-color: <?php echo $tache['categorie_couleur']; ?>">
                                                <?php echo htmlspecialchars($tache['categorie_nom']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </small>
                                    <span class="badge bg-<?php 
                                        if ($tache['priorite'] === 'haute') echo 'danger';
                                        elseif ($tache['priorite'] === 'moyenne') echo 'warning';
                                        else echo 'info';
                                    ?>">
                                        <?php echo ucfirst($tache['priorite']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="index.php?page=taches&action=ajouter" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvelle tâche
                </a>
            </div>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Statistiques</h5>
            </div>
            <div class="card-body">
                <?php if ($total_taches === 0): ?>
                    <p class="text-center text-muted my-5">Aucune statistique disponible.</p>
                <?php else: ?>
                    <h6>Répartition des tâches</h6>
                    <div class="progress mb-4" style="height: 25px;">
                        <?php
                        $a_faire = isset($stats['a_faire']) ? $stats['a_faire'] : 0;
                        $en_cours = isset($stats['en_cours']) ? $stats['en_cours'] : 0;
                        $terminee = isset($stats['terminee']) ? $stats['terminee'] : 0;
                        
                        $pct_a_faire = $total_taches > 0 ? round(($a_faire / $total_taches) * 100) : 0;
                        $pct_en_cours = $total_taches > 0 ? round(($en_cours / $total_taches) * 100) : 0;
                        $pct_terminee = $total_taches > 0 ? round(($terminee / $total_taches) * 100) : 0;
                        ?>
                        <div class="progress-bar bg-danger" style="width: <?php echo $pct_a_faire; ?>%" 
                             data-bs-toggle="tooltip" title="À faire: <?php echo $a_faire; ?>">
                            <?php if ($pct_a_faire > 10): echo $a_faire; endif; ?>
                        </div>
                        <div class="progress-bar bg-warning" style="width: <?php echo $pct_en_cours; ?>%" 
                             data-bs-toggle="tooltip" title="En cours: <?php echo $en_cours; ?>">
                            <?php if ($pct_en_cours > 10): echo $en_cours; endif; ?>
                        </div>
                        <div class="progress-bar bg-success" style="width: <?php echo $pct_terminee; ?>%" 
                             data-bs-toggle="tooltip" title="Terminées: <?php echo $terminee; ?>">
                            <?php if ($pct_terminee > 10): echo $terminee; endif; ?>
                        </div>
                    </div>
                    
                    <div class="row text-center mb-4">
                        <div class="col">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <h3 class="mb-0"><?php echo $a_faire; ?></h3>
                                    <small class="text-muted">À faire</small>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <h3 class="mb-0"><?php echo $en_cours; ?></h3>
                                    <small class="text-muted">En cours</small>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <h3 class="mb-0"><?php echo $terminee; ?></h3>
                                    <small class="text-muted">Terminées</small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Événements à venir -->
                <h6 class="mt-4"><i class="fas fa-calendar-week"></i> Événements à venir (7 jours)</h6>
                <?php if (empty($evenements)): ?>
                    <p class="text-center text-muted my-3">Aucun événement à venir.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($evenements as $evenement): ?>
                            <div class="list-group-item p-2">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($evenement['titre']); ?></h6>
                                    <small>
                                        <?php 
                                            $date = new DateTime($evenement['date_debut']);
                                            echo $date->format('d/m H:i'); 
                                        ?>
                                    </small>
                                </div>
                                <?php if ($evenement['categorie_nom']): ?>
                                    <small class="categorie-badge" style="background-color: <?php echo $evenement['categorie_couleur']; ?>">
                                        <?php echo htmlspecialchars($evenement['categorie_nom']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="index.php?page=emploi_temps" class="btn btn-primary">
                    <i class="fas fa-calendar-plus"></i> Gérer l'emploi du temps
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Suggestions de planning -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Suggestions pour votre planning</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Conseil du jour</h6>
                    <?php
                    $conseils = [
                        "Priorisez vos tâches par ordre d'importance et d'urgence.",
                        "Faites des pauses régulières pour maintenir votre productivité.",
                        "Décomposez les grandes tâches en petites étapes plus faciles à gérer.",
                        "Essayez d'étudier à la même heure chaque jour pour créer une routine.",
                        "Utilisez la technique Pomodoro : 25 minutes de travail, puis 5 minutes de pause.",
                        "Préparez votre planning la veille pour commencer la journée efficacement.",
                        "Identifiez votre période de productivité maximale et planifiez les tâches importantes à ce moment.",
                    ];
                    echo $conseils[array_rand($conseils)];
                    ?>
                </div>
                
                <?php if (!empty($taches)): ?>
                    <div class="mt-3">
                        <h6>Organisation suggérée pour aujourd'hui :</h6>
                        <ol class="list-group list-group-numbered">
                            <?php 
                            $taches_prioritaires = array_slice($taches, 0, 3);
                            foreach ($taches_prioritaires as $tache): 
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold"><?php echo htmlspecialchars($tache['titre']); ?></div>
                                        <?php if ($tache['temps_estime']): ?>
                                            Durée estimée: <?php echo $tache['temps_estime']; ?> min
                                        <?php else: ?>
                                            Durée estimée: 30 min
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">
                                        <?php 
                                        if ($tache['priorite'] === 'haute') echo 'Prioritaire';
                                        elseif ($tache['priorite'] === 'moyenne') echo 'Important';
                                        else echo 'Normal';
                                        ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> 