<nav class="navbar navbar-expand-lg navbar-main shadow rounded-4 my-3 px-2">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <i class="fas fa-calendar-alt text-primary me-2"></i> <span>Agenda Étudiant</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-3 py-2 <?php echo $page === 'accueil' ? 'active fw-bold bg-primary text-white' : ''; ?>" href="index.php">
                        <i class="fas fa-home me-1"></i> Accueil
                    </a>
                </li>
                <?php if (isset($_SESSION['utilisateur_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-2 <?php echo $page === 'tableau_bord' ? 'active fw-bold bg-primary text-white' : ''; ?>" href="index.php?page=tableau_bord">
                            <i class="fas fa-tachometer-alt me-1"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle rounded-pill px-3 py-2 <?php echo $page === 'taches' ? 'active fw-bold bg-primary text-white' : ''; ?>" href="#" id="taskDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-tasks me-1"></i> Tâches
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=taches">Liste des tâches</a></li>
                            <li><a class="dropdown-item" href="index.php?page=taches&action=ajouter">Ajouter une tâche</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-2 <?php echo $page === 'categories' ? 'active fw-bold bg-primary text-white' : ''; ?>" href="index.php?page=categories">
                            <i class="fas fa-tags me-1"></i> Catégories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-3 py-2 <?php echo $page === 'emploi_temps' ? 'active fw-bold bg-primary text-white' : ''; ?>" href="index.php?page=emploi_temps">
                            <i class="fas fa-calendar-week me-1"></i> Emploi du temps
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav align-items-center gap-2">
                <?php if (isset($_SESSION['utilisateur_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:36px; height:36px; font-size:1.1em;">
                                <?php echo strtoupper(substr($_SESSION['utilisateur_prenom'], 0, 1)); ?>
                            </span>
                            <span class="fw-semibold text-dark small d-none d-md-inline"> <?php echo htmlspecialchars($_SESSION['utilisateur_prenom']); ?> </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="index.php?page=profil"><i class="fas fa-user-cog me-2"></i> Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=deconnexion"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'connexion' ? 'active fw-bold' : ''; ?>" href="index.php?page=connexion">
                            <i class="fas fa-sign-in-alt"></i> Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'inscription' ? 'active fw-bold' : ''; ?>" href="index.php?page=inscription">
                            <i class="fas fa-user-plus"></i> Inscription
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Bouton de changement de thème -->
                <li class="nav-item ms-2">
                    <a class="nav-link theme-toggle p-2 rounded-circle" href="index.php?page=<?php echo $page; ?>&action=changer_theme" title="Changer de thème">
                        <?php if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'sombre'): ?>
                            <i class="fas fa-sun"></i>
                        <?php else: ?>
                            <i class="fas fa-moon"></i>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav> 