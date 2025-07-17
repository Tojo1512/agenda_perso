DROP DATABASE IF EXISTS `bdd_agenda_perso`;

CREATE DATABASE IF NOT EXISTS `bdd_agenda_perso`;
USE `bdd_agenda_perso`;

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
    -- id_tache supprimé pour gestion via table de liaison
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- Table de liaison emplois_du_temps <-> taches
CREATE TABLE emploi_temps_taches (
    id_emploi_du_temps INT NOT NULL,
    id_tache INT NOT NULL,
    PRIMARY KEY (id_emploi_du_temps, id_tache),
    FOREIGN KEY (id_emploi_du_temps) REFERENCES emplois_du_temps(id) ON DELETE CASCADE,
    FOREIGN KEY (id_tache) REFERENCES taches(id) ON DELETE CASCADE
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
    id_utilisateur INT NOT NULL,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    contenu JSON,
    type VARCHAR(100),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
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

