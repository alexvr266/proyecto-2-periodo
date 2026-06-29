<?php
require_once 'config/database.php';
$data = json_decode(file_get_contents('php://input'), true);

try {
    $db = Database::getInstance()->getConnection();
    $sql = "DELETE FROM estudiantes WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $data['id']]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}