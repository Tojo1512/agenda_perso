-- Insertion de données d'exemple pour les tests
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe) 
VALUES ('Dupont', 'Jean', 'jean.dupont@email.com', SHA2('motdepasse123', 256));

INSERT INTO categories (nom, couleur, type, id_utilisateur) 
VALUES 
('Mathématiques', '#FF5733', 'scientifique', 1),
('Français', '#33FF57', 'littéraire', 1),
('Informatique', '#3357FF', 'scientifique', 1);

INSERT INTO taches (titre, description, date_echeance, priorite, id_utilisateur, id_categorie) 
VALUES 
('Devoir de maths', 'Exercices 1 à 10 page 25', DATE_ADD(NOW(), INTERVAL 7 DAY), 'haute', 1, 1),
('Dissertation', 'Sujet: La liberté dans les romans du XIXe siècle', DATE_ADD(NOW(), INTERVAL 14 DAY), 'moyenne', 1, 2),
('Projet de programmation', 'Créer une application de gestion de tâches', DATE_ADD(NOW(), INTERVAL 30 DAY), 'haute', 1, 3);