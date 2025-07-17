<div class="container py-5">
    <!-- Hero Section -->
    <div class="row align-items-center mb-5">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <h1 class="display-4 fw-bold">Organisez votre vie étudiante</h1>
            <p class="lead mb-4">Un outil simple et efficace pour gérer vos tâches, cours et deadlines en un seul endroit.</p>
            
            <?php if (!isset($_SESSION['utilisateur_id'])): ?>
                <div class="d-flex gap-3">
                    <a href="index.php?page=connexion" class="btn btn-primary btn-lg px-4">
                        Connexion
                    </a>
                    <a href="index.php?page=inscription" class="btn btn-outline-primary btn-lg px-4">
                        Inscription
                    </a>
                </div>
            <?php else: ?>
                <a href="index.php?page=tableau_bord" class="btn btn-primary btn-lg px-4">
                    Accéder à mon tableau de bord
                </a>
            <?php endif; ?>
        </div>
        <div class="col-lg-6 text-center">
            <img src="assets/img/calendar.png" alt="Agenda" class="img-fluid" style="max-height: 300px;">
        </div>
    </div>
    
    <!-- Features Section -->
    <div class="row mb-5">
        <div class="col-12 text-center mb-4">
            <h2 class="fw-bold">Tout ce dont vous avez besoin</h2>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="feature-card p-4 text-center h-100 border rounded">
                <div class="feature-icon mb-3">
                    <i class="fas fa-tasks fa-2x text-primary"></i>
                </div>
                <h3 class="h5 mb-3">Gestion des tâches</h3>
                <p class="mb-0">Créez, organisez et suivez vos tâches par priorité et catégorie.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="feature-card p-4 text-center h-100 border rounded">
                <div class="feature-icon mb-3">
                    <i class="fas fa-calendar-week fa-2x text-info"></i>
                </div>
                <h3 class="h5 mb-3">Emploi du temps</h3>
                <p class="mb-0">Visualisez votre semaine et gérez vos cours en un coup d'œil.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="feature-card p-4 text-center h-100 border rounded">
                <div class="feature-icon mb-3">
                    <i class="fas fa-palette fa-2x text-success"></i>
                </div>
                <h3 class="h5 mb-3">Personnalisation</h3>
                <p class="mb-0">Adaptez l'interface à vos préférences avec le mode sombre/clair.</p>
            </div>
        </div>
    </div>
    
    <!-- Call to Action -->
    <div class="row">
        <div class="col-12">
            <div class="p-5 text-center bg-light rounded">
                <h2 class="mb-3">Prêt à mieux organiser vos études ?</h2>
                <p class="lead mb-4">Rejoignez des milliers d'étudiants qui gèrent efficacement leur temps.</p>
                <?php if (!isset($_SESSION['utilisateur_id'])): ?>
                    <a href="index.php?page=inscription" class="btn btn-primary btn-lg px-5">
                        Commencer maintenant
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> 