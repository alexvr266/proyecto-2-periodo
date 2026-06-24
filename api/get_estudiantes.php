<?php
// api/get_estudiantes.php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM estudiantes ORDER BY ano, seccion, nombre");
    $estudiantes = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $estudiantes,
        'total' => count($estudiantes)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>