<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-calendar-alt"></i> Agenda Étudiant
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $page === 'accueil' ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home"></i> Accueil
                    </a>
                </li>
                
                <?php if (isset($_SESSION['utilisateur_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'tableau_bord' ? 'active' : ''; ?>" href="index.php?page=tableau_bord">
                            <i class="fas fa-tachometer-alt"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'taches' ? 'active' : ''; ?>" href="index.php?page=taches">
                            <i class="fas fa-tasks"></i> Tâches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'categories' ? 'active' : ''; ?>" href="index.php?page=categories">
                            <i class="fas fa-tags"></i> Catégories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'emploi_temps' ? 'active' : ''; ?>" href="index.php?page=emploi_temps">
                            <i class="fas fa-calendar-week"></i> Emploi du temps
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['utilisateur_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['utilisateur_prenom'] . ' ' . $_SESSION['utilisateur_nom']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="index.php?page=profil"><i class="fas fa-user-cog"></i> Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=deconnexion"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'connexion' ? 'active' : ''; ?>" href="index.php?page=connexion">
                            <i class="fas fa-sign-in-alt"></i> Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'inscription' ? 'active' : ''; ?>" href="index.php?page=inscription">
                            <i class="fas fa-user-plus"></i> Inscription
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Bouton de changement de thème -->
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=<?php echo $page; ?>&action=changer_theme" title="Changer de thème">
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