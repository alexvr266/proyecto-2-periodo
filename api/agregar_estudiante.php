<?php
require_once 'config/database.php';
$data = json_decode(file_get_contents('php://input'), true);

try {
    $db = Database::getInstance()->getConnection();
    $sql = "INSERT INTO estudiantes (nombre, nie, sexo, especialidad, codigo, ano, turno) 
            VALUES (:nombre, :nie, :sexo, :especialidad, :codigo, :ano, :turno)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':nie' => $data['nie'],
        ':sexo' => $data['sexo'],
        ':especialidad' => $data['especialidad'],
        ':codigo' => $data['codigo'],
        ':ano' => $data['ano'],
        ':turno' => $data['turno']
    ]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}