<div class="row mt-5">
    <div class="col-md-6 offset-md-3 text-center">
        <div class="card mb-4">
            <div class="card-body">
                <h2>Bienvenue sur votre Agenda Personnel</h2>
                <p class="lead">Organisez votre vie académique efficacement</p>
                <img src="assets/img/calendar.png" alt="Agenda" class="img-fluid mb-4" style="max-width: 200px;">
                
                <p>Notre application vous aide à :</p>
                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item"><i class="fas fa-tasks text-primary"></i> Gérer vos tâches et deadlines</li>
                    <li class="list-group-item"><i class="fas fa-tags text-success"></i> Catégoriser vos matières</li>
                    <li class="list-group-item"><i class="fas fa-bell text-warning"></i> Recevoir des alertes et rappels</li>
                    <li class="list-group-item"><i class="fas fa-calendar-week text-info"></i> Planifier votre emploi du temps</li>
                </ul>
                
                <?php if (!isset($_SESSION['utilisateur_id'])): ?>
                    <div class="d-grid gap-2">
                        <a href="index.php?page=connexion" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </a>
                        <a href="index.php?page=inscription" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-user-plus"></i> Créer un compte
                        </a>
                    </div>
                <?php else: ?>
                    <div class="d-grid gap-2">
                        <a href="index.php?page=tableau_bord" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt"></i> Accéder à mon tableau de bord
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Fonctionnalités principales</h3>
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-tasks fa-3x text-primary mb-3"></i>
                                <h5>Gestion des tâches</h5>
                                <p>Créez, modifiez et suivez vos tâches facilement</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-week fa-3x text-info mb-3"></i>
                                <h5>Emploi du temps</h5>
                                <p>Visualisez votre semaine en un coup d'œil</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-bell fa-3x text-warning mb-3"></i>
                                <h5>Alertes et rappels</h5>
                                <p>Ne manquez plus jamais une deadline importante</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-palette fa-3x text-success mb-3"></i>
                                <h5>Personnalisation</h5>
                                <p>Adaptez l'interface à vos préférences</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 