<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Vérification de la session
if (!isset($_SESSION['utilisateur_id'])) {
    echo json_encode(['success' => false, 'error' => 'Session expirée ou utilisateur non connecté']);
    exit;
}

require_once(__DIR__ . '/../config/database.php');

// Connexion à la base de données
$db = Database::getInstance();
$id_utilisateur = $_SESSION['utilisateur_id'];

$task_id = isset($_POST['task_id']) ? $_POST['task_id'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';

if (!empty($task_id) && !empty($status)) {
    // Vérifier que la tâche appartient bien à l'utilisateur
    $query_verify = "SELECT COUNT(*) FROM taches WHERE id = :id AND id_utilisateur = :id_utilisateur";
    $stmt_verify = $db->prepare($query_verify);
    $stmt_verify->bindParam(':id', $task_id);
    $stmt_verify->bindParam(':id_utilisateur', $id_utilisateur);
    $stmt_verify->execute();
    
    if ($stmt_verify->fetchColumn() > 0) {
        $query = "UPDATE taches SET statut = :statut WHERE id = :id AND id_utilisateur = :id_utilisateur";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':statut', $status);
        $stmt->bindParam(':id', $task_id);
        $stmt->bindParam(':id_utilisateur', $id_utilisateur);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
}
