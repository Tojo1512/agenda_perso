# Agenda Personnel et Gestion de Tâches pour Étudiants

Application web de gestion d'agenda et de tâches conçue spécifiquement pour les besoins des étudiants.

## 📋 Fonctionnalités principales

- **Gestion des tâches et deadlines**
  - Création, modification et suppression de tâches
  - Catégorisation par matières (scientifique/littéraire)
  - Alertes et rappels pour les deadlines

- **Personnalisation et accessibilité**
  - Thèmes personnalisables (mode sombre/clair)
  - Interface responsive adaptée à tous les appareils

- **Gestion de temps et productivité**
  - Emploi du temps hebdomadaire
  - Visualisation des tâches par priorité
  - Tableau de bord avec statistiques

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

2. Créer une base de données MySQL nommée `bdd_agenda_perso`

3. Importer le script SQL :
   ```
   mysql -u root -p bdd_agenda_perso < bdd/script.sql
   ```

4. Accéder à l'application via :
   ```
   http://localhost/agenda_perso
   ```

## 🏗️ Structure du projet

```
agenda_perso/
├── assets/           # Ressources statiques (CSS, JS, images)
├── bdd/              # Scripts de base de données
├── includes/         # Fichiers d'inclusion (header, footer, etc.)
├── pages/            # Pages de l'application
├── index.php         # Point d'entrée principal
└── README.md         # Documentation
```

## 👤 Comptes de test

Pour tester l'application, vous pouvez utiliser le compte suivant :
- Email : jean.dupont@email.com
- Mot de passe : motdepasse123

Ou créer votre propre compte via la page d'inscription.

## 🔄 Flux de travail typiques

1. **Gestion des tâches**
   - Créer des tâches avec date d'échéance et priorité
   - Organiser les tâches par catégories
   - Suivre l'avancement et marquer comme terminées

2. **Emploi du temps**
   - Visualiser les événements par semaine
   - Ajouter des cours et rendez-vous
   - Naviguer entre les semaines

3. **Personnalisation**
   - Changer le thème (clair/sombre)
   - Modifier les informations du profil
   - Consulter les statistiques personnelles

## 📱 Compatibilité

L'application est conçue pour fonctionner sur :
- Ordinateurs de bureau
- Tablettes
- Smartphones

Le design responsive s'adapte automatiquement à la taille de l'écran.

## 🔒 Sécurité

- Mots de passe hachés avec la fonction password_hash()
- Protection contre les injections SQL avec PDO
- Validation des données utilisateur 