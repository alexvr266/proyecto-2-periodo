<?php
// api/guardar_registro.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$estudiante_id = $data['estudiante_id'] ?? null;
$tipo = $data['tipo'] ?? null;
$letras = $data['letras'] ?? '';
$fecha = $data['fecha'] ?? date('Y-m-d');
$observaciones = $data['observaciones'] ?? '';
$usuario_id = $data['usuario_id'] ?? 1; // por defecto admin

if (!$estudiante_id || !$tipo || !$letras) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO registros (estudiante_id, fecha, tipo, letras_seleccionadas, observaciones, usuario_registro)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$estudiante_id, $fecha, $tipo, $letras, $observaciones, $usuario_id]);
    
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>