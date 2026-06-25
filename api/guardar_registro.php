<?php
require_once 'config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['estudiante_id'], $data['tipo'], $data['letras'], $data['fecha'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();
    
    $estudiante_id = $data['estudiante_id'];
    $tipo = $data['tipo'];
    $letras = str_split($data['letras']);
    $fecha = $data['fecha'];
    $observaciones = $data['observaciones'] ?? '';
    $usuario_id = $data['usuario_id'] ?? 1;
    
    // Mapeo de columnas según tipo
    $columnas = [];
    if ($tipo === 'demerito') {
        $columnas = ['A' => 'dem_a', 'B' => 'dem_b', 'C' => 'dem_c', 'D' => 'dem_d'];
    } elseif ($tipo === 'redencion') {
        $columnas = ['A' => 'red_a', 'B' => 'red_b', 'C' => 'red_c'];
    } elseif ($tipo === 'reconocimiento') {
        $columnas = ['A' => 'rec_a', 'B' => 'rec_b'];
    } else {
        throw new Exception('Tipo no válido');
    }
    
    // Actualizar las columnas correspondientes en la tabla estudiantes
    $updates = [];
    $params = [];
    foreach ($letras as $letra) {
        if (isset($columnas[$letra])) {
            $col = $columnas[$letra];
            $updates[] = "$col = 'X'";
        }
    }
    if (empty($updates)) {
        throw new Exception('No hay columnas válidas para actualizar');
    }
    
    $sql = "UPDATE estudiantes SET " . implode(', ', $updates) . ", fecha = :fecha, observaciones = :observaciones WHERE id = :estudiante_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':fecha' => $fecha,
        ':observaciones' => $observaciones,
        ':estudiante_id' => $estudiante_id
    ]);
    
    // Opcional: también podrías insertar un registro en una tabla de historial
    // ...
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Registro guardado']);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}