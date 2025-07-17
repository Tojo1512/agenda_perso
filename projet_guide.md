# Guide du Projet : Agenda Personnel et Gestion de Tâches pour Étudiants

## 📋 Liste des Tâches

### Phase 1 : Mise en place de la structure de base
- [ ] Créer la structure de la base de données
- [ ] Configurer l'environnement de développement
- [ ] Créer les classes de base
- [ ] Mettre en place l'authentification des utilisateurs

### Phase 2 : Développement des fonctionnalités principales
- [ ] Implémenter la gestion des tâches (CRUD)
- [ ] Développer le système de catégorisation par matières
- [ ] Créer le système d'emploi du temps
- [ ] Implémenter le système d'alertes et rappels

### Phase 3 : Personnalisation et accessibilité
- [ ] Développer les thèmes personnalisables (mode sombre/clair)
- [ ] Créer des templates pré-remplis
- [ ] Optimiser l'interface pour différents appareils

### Phase 4 : Fonctionnalités avancées
- [ ] Implémenter le système de suggestion de temps par tâche
- [ ] Développer l'algorithme de suggestion de planning
- [ ] Intégrer la gestion de statistiques et productivité

### Phase 5 : Tests et déploiement
- [ ] Effectuer des tests unitaires et fonctionnels
- [ ] Correction des bugs et optimisation
- [ ] Déploiement de la version finale

## 🏗️ Architecture du Projet

### Classes et Méthodes à Créer

#### 1. Classe `Utilisateur`
- **Attributs** :
  - id
  - nom
  - prenom
  - email
  - mot_de_passe
  - preferences (thème, notifications, etc.)
  
- **Méthodes** :
  - `creerCompte()`
  - `connecter()`
  - `deconnecter()`
  - `modifierProfil()`
  - `changerTheme(theme)`
  - `definirPreferences(preferences)`

#### 2. Classe `Tache`
- **Attributs** :
  - id
  - titre
  - description
  - date_creation
  - date_echeance
  - priorite
  - statut (à faire, en cours, terminée)
  - id_utilisateur
  - id_categorie
  - temps_estime
  
- **Méthodes** :
  - `creer()`
  - `modifier()`
  - `supprimer()`
  - `changerStatut(statut)`
  - `definirPriorite(priorite)`
  - `definirTempsEstime(temps)`

#### 3. Classe `Categorie`
- **Attributs** :
  - id
  - nom
  - couleur
  - type (scientifique, littéraire, etc.)
  - id_utilisateur
  
- **Méthodes** :
  - `creer()`
  - `modifier()`
  - `supprimer()`
  - `listerTaches()`

#### 4. Classe `EmploiDuTemps`
- **Attributs** :
  - id
  - titre
  - date_debut
  - date_fin
  - id_utilisateur
  
- **Méthodes** :
  - `creer()`
  - `modifier()`
  - `supprimer()`
  - `ajouterEvenement(evenement)`
  - `supprimerEvenement(id_evenement)`
  - `genererVueJournaliere()`
  - `genererVueHebdomadaire()`
  - `genererVueMensuelle()`

#### 5. Classe `Evenement`
- **Attributs** :
  - id
  - titre
  - description
  - date_debut
  - date_fin
  - lieu
  - id_emploi_du_temps
  - id_categorie
  
- **Méthodes** :
  - `creer()`
  - `modifier()`
  - `supprimer()`
  - `definirRecurrence(type_recurrence)`

#### 6. Classe `Notification`
- **Attributs** :
  - id
  - titre
  - message
  - date_envoi
  - id_utilisateur
  - id_tache (optionnel)
  - id_evenement (optionnel)
  - statut (lue, non lue)
  
- **Méthodes** :
  - `creer()`
  - `marquerCommeLue()`
  - `supprimer()`
  - `envoyer()`

#### 7. Classe `Template`
- **Attributs** :
  - id
  - nom
  - description
  - contenu
  - type (mémoire, projet, etc.)
  
- **Méthodes** :
  - `creer()`
  - `modifier()`
  - `supprimer()`
  - `appliquer(id_utilisateur)`

#### 8. Classe `SuggestionPlanning`
- **Attributs** :
  - id
  - id_utilisateur
  - date_generation
  
- **Méthodes** :
  - `analyserHabitudes()`
  - `genererSuggestions()`
  - `appliquerSuggestion(id_suggestion)`
  - `refuserSuggestion(id_suggestion)`

#### 9. Classe `Statistique`
- **Attributs** :
  - id
  - id_utilisateur
  - periode
  - donnees
  
- **Méthodes** :
  - `calculerProductivite()`
  - `calculerTempsMoyen()`
  - `genererRapport()`
  - `visualiserDonnees(type_visualisation)`

## 🗄️ Structure de la Base de Données

```sql
-- Tables principales
CREATE TABLE utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    theme VARCHAR(50) DEFAULT 'clair',
    preferences JSON,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    couleur VARCHAR(50),
    type VARCHAR(100),
    id_utilisateur INT,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE taches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_echeance DATETIME,
    priorite ENUM('basse', 'moyenne', 'haute') DEFAULT 'moyenne',
    statut ENUM('a_faire', 'en_cours', 'terminee') DEFAULT 'a_faire',
    temps_estime INT, -- en minutes
    id_utilisateur INT NOT NULL,
    id_categorie INT,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (id_categorie) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE emplois_du_temps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    id_utilisateur INT NOT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE evenements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    lieu VARCHAR(255),
    recurrence VARCHAR(100),
    id_emploi_du_temps INT NOT NULL,
    id_categorie INT,
    FOREIGN KEY (id_emploi_du_temps) REFERENCES emplois_du_temps(id) ON DELETE CASCADE,
    FOREIGN KEY (id_categorie) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    message TEXT,
    date_envoi DATETIME NOT NULL,
    statut ENUM('lue', 'non_lue') DEFAULT 'non_lue',
    id_utilisateur INT NOT NULL,
    id_tache INT,
    id_evenement INT,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (id_tache) REFERENCES taches(id) ON DELETE CASCADE,
    FOREIGN KEY (id_evenement) REFERENCES evenements(id) ON DELETE CASCADE
);

CREATE TABLE templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    contenu JSON,
    type VARCHAR(100),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE suggestions_planning (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date_generation DATETIME DEFAULT CURRENT_TIMESTAMP,
    contenu JSON,
    statut ENUM('en_attente', 'acceptee', 'refusee') DEFAULT 'en_attente',
    id_utilisateur INT NOT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE statistiques (
    id INT PRIMARY KEY AUTO_INCREMENT,
    periode VARCHAR(50),
    donnees JSON,
    date_generation DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur INT NOT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);
```

## 📱 Structure des Pages/Vues

1. **Page d'accueil/connexion**
   - Formulaire de connexion
   - Option de création de compte
   - Présentation des fonctionnalités principales

2. **Tableau de bord**
   - Vue d'ensemble des tâches à venir
   - Emploi du temps du jour/semaine
   - Notifications
   - Accès rapide aux fonctionnalités

3. **Gestion des tâches**
   - Liste des tâches (filtrable par catégorie, priorité, statut)
   - Formulaire de création/modification
   - Vue chronologique des échéances

4. **Emploi du temps**
   - Vue journalière/hebdomadaire/mensuelle
   - Ajout/modification d'événements
   - Visualisation par catégorie

5. **Suggestions et planning**
   - Suggestions générées automatiquement
   - Options d'acceptation/refus
   - Visualisation des habitudes

6. **Paramètres**
   - Modification du profil
   - Préférences d'affichage (thème)
   - Gestion des notifications
   - Gestion des templates

## 🔄 Flux de Travail Typiques

1. **Création et gestion d'une tâche**
   - L'étudiant se connecte à son compte
   - Il accède à la section "Tâches"
   - Il clique sur "Nouvelle tâche"
   - Il remplit le formulaire (titre, description, échéance, catégorie, etc.)
   - Il confirme la création
   - Le système envoie une confirmation et programme les notifications

2. **Utilisation des suggestions de planning**
   - L'étudiant reçoit une notification de nouvelle suggestion
   - Il consulte la suggestion dans son tableau de bord
   - Il peut accepter, modifier ou refuser la suggestion
   - S'il accepte, le planning est automatiquement mis à jour

3. **Personnalisation de l'interface**
   - L'étudiant accède aux paramètres
   - Il choisit le thème (sombre/clair)
   - Il configure ses préférences de notification
   - Les changements sont appliqués immédiatement

## 🚀 Prochaines étapes de développement

1. Créer la structure de base de données et les tables nécessaires
2. Développer les classes principales (Utilisateur, Tache, Categorie)
3. Implémenter l'interface utilisateur de base
4. Ajouter progressivement les fonctionnalités avancées 