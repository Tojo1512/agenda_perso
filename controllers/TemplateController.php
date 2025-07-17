<?php
require_once __DIR__ . '/../models/Template.php';
require_once __DIR__ . '/../config/db.php';

class TemplateController {
    public function index() {
        session_start();
        $db = connectDB();
        $userId = $_SESSION['utilisateur_id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=connexion');
            exit;
        }
        $templates = Template::getAllByUser($db, $userId);
        include __DIR__ . '/../views/templates/index.php';
    }
    
    public function utiliser() {
        session_start();
        $db = connectDB();
        $userId = $_SESSION['utilisateur_id'] ?? null;
        $id = $_GET['id'] ?? null;
        if (!$userId || !$id) {
            header('Location: index.php?page=templates');
            exit;
        }
        $template = Template::getById($db, $id, $userId);
        if (!$template) {
            header('Location: index.php?page=templates');
            exit;
        }
        $_SESSION['template_prefill'] = $template['contenu'];
        header('Location: index.php?page=taches&action=ajouter&from_template=1');
        exit;
    }
    
    public function supprimer() {
        session_start();
        $db = connectDB();
        $userId = $_SESSION['utilisateur_id'] ?? null;
        $id = $_GET['id'] ?? null;
        if ($userId && $id) {
            Template::delete($db, $id, $userId);
        }
        header('Location: index.php?page=templates');
        exit;
    }
}
