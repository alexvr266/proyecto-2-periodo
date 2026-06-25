<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM estudiantes ORDER BY id ASC");
    $estudiantes = $stmt->fetchAll();
    
    // También obtenemos los deméritos/redenciones/reconocimientos de la tabla demeritos
    // y los agregamos a cada estudiante (esto es un ejemplo, deberías hacer un JOIN o consulta separada)
    // Para simplificar, aquí asumimos que las columnas dem_a, dem_b, etc. ya están en la tabla estudiantes.
    // Si no, deberías hacer un JOIN con la tabla demeritos.
    
    echo json_encode(['success' => true, 'data' => $estudiantes]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}