<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Personnel Étudiant</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Intégration de Bootstrap pour un style rapide -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?php echo isset($_SESSION['theme']) ? $_SESSION['theme'] : 'clair'; ?>">
    <div class="container mt-4">
        <header class="mb-4">
            <h1 class="text-center">Agenda Personnel et Gestion de Tâches</h1>
            <p class="text-center text-muted">Organisez votre vie étudiante efficacement</p>
        </header> 