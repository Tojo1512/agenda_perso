# Agenda Personnel et Gestion de Tâches pour Étudiants

Application web de gestion d'agenda et de tâches conçue spécifiquement pour les besoins des étudiants.

## 📋 Fonctionnalités principales

- **Gestion des tâches et deadlines**
  - Création, modification et suppression de tâches
  - Catégorisation par matières (scientifique/littéraire)
  - Alertes et rappels pour les deadlines

- **Personnalisation et accessibilité**
  - Thèmes personnalisables (mode sombre/clair)
  - Templates pré-remplis pour différents types de projets

- **Gestion de temps et productivité**
  - Suggestion du temps à passer par tâche
  - Suggestion de planning basé sur les habitudes

## 🛠️ Installation

### Prérequis
- PHP 7.4+
- MySQL 5.7+
- Serveur web (Apache, Nginx)
- XAMPP (recommandé pour le développement)

### Étapes d'installation

1. Cloner ce dépôt dans votre répertoire htdocs de XAMPP :
   ```
   git clone [url-du-depot] agenda_perso
   ```

2. Importer la base de données :
   ```
   mysql -u [username] -p < bdd/script.sql
   ```

3. Configurer les paramètres de connexion à la base de données dans le fichier de configuration (à créer).

4. Accéder à l'application via :
   ```
   http://localhost/agenda_perso
   ```

## 🏗️ Structure du projet

```
agenda_perso/
├── assets/           # Ressources statiques (CSS, JS, images)
├── bdd/              # Scripts et schémas de base de données
├── classes/          # Classes PHP du modèle
├── config/           # Fichiers de configuration
├── controllers/      # Contrôleurs de l'application
├── templates/        # Templates pour l'affichage
├── utils/            # Utilitaires et fonctions d'aide
├── index.php         # Point d'entrée principal
├── README.md         # Documentation
└── projet_guide.md   # Guide détaillé de développement
```

## 📝 Guide de développement

Le fichier `projet_guide.md` contient les détails complets sur :
- La liste des tâches à accomplir
- L'architecture détaillée du projet
- Les classes et méthodes à implémenter
- Le schéma de la base de données
- Les flux de travail typiques

## 🔄 Flux de travail typiques

1. **Création et gestion d'une tâche**
   - L'étudiant se connecte à son compte
   - Il accède à la section "Tâches"
   - Il clique sur "Nouvelle tâche"
   - Il remplit le formulaire et confirme
   - Le système programme les notifications

2. **Utilisation des suggestions de planning**
   - L'étudiant reçoit une notification de suggestion
   - Il peut accepter, modifier ou refuser la suggestion
   - S'il accepte, le planning est automatiquement mis à jour

## 👥 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Soumettre une pull request 