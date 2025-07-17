<?php
function connectDB() {
    $host = 'localhost';
    $dbname = 'bdd_agenda_perso';
    $user = 'root';
    $pass = '';

    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die('Erreur connexion BDD: ' . $e->getMessage());
    }
}
?>
