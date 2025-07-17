<?php
class Template {
    public static function getAllByUser($db, $userId) {
        $stmt = $db->prepare('SELECT * FROM templates WHERE id_utilisateur = ? ORDER BY id DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function getById($db, $id, $userId) {
        $stmt = $db->prepare('SELECT * FROM templates WHERE id = ? AND id_utilisateur = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public static function delete($db, $id, $userId) {
        $stmt = $db->prepare('DELETE FROM templates WHERE id = ? AND id_utilisateur = ?');
        $stmt->execute([$id, $userId]);
    }
}
