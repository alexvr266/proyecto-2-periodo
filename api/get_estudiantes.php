<?php
// api/get_estudiantes.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT e.*, 
                   GROUP_CONCAT(r.letras_seleccionadas) as letras,
                   GROUP_CONCAT(r.tipo) as tipos,
                   GROUP_CONCAT(r.fecha) as fechas_registro
            FROM estudiantes e
            LEFT JOIN registros r ON e.id = r.estudiante_id
            GROUP BY e.id
            ORDER BY e.ano, e.seccion, e.nombre";
    
    $stmt = $db->query($sql);
    $estudiantes = $stmt->fetchAll();

    // Procesar los registros para asignar los campos dem_a, dem_b, etc.
    foreach ($estudiantes as &$est) {
        // Inicializar campos
        $est['dem_a'] = '';
        $est['dem_b'] = '';
        $est['dem_c'] = '';
        $est['dem_d'] = '';
        $est['red_a'] = '';
        $est['red_b'] = '';
        $est['red_c'] = '';
        $est['rec_a'] = '';
        $est['rec_b'] = '';

        if ($est['letras']) {
            $tipos = explode(',', $est['tipos']);
            $letras = explode(',', $est['letras']);
            foreach ($tipos as $idx => $tipo) {
                $letra = $letras[$idx] ?? '';
                if ($tipo === 'demerito') {
                    foreach (str_split($letra) as $l) {
                        $campo = "dem_" . strtolower($l);
                        if (array_key_exists($campo, $est)) $est[$campo] = 'X';
                    }
                } elseif ($tipo === 'redencion') {
                    foreach (str_split($letra) as $l) {
                        $campo = "red_" . strtolower($l);
                        if (array_key_exists($campo, $est)) $est[$campo] = 'X';
                    }
                } elseif ($tipo === 'reconocimiento') {
                    foreach (str_split($letra) as $l) {
                        $campo = "rec_" . strtolower($l);
                        if (array_key_exists($campo, $est)) $est[$campo] = 'X';
                    }
                }
            }
        }
        // Limpiar campos temporales
        unset($est['letras'], $est['tipos'], $est['fechas_registro']);
    }

    echo json_encode([
        'success' => true,
        'data' => $estudiantes,
        'total' => count($estudiantes)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>