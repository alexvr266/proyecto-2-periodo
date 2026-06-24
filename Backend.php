<?php
// config/database.php
class Database {
    private static $instance = null;
    private $conn;
    
    private $host = 'localhost';
    private $dbname = 'sistema_demeritos';
    private $username = 'root';
    private $password = '';
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// api/auth.php - Login
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $usuario = $data['usuario'] ?? '';
    $password = $data['password'] ?? '';
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE usuario = ? AND estado = 'activo'");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();
    
    if ($user && hash('sha256', $password) === $user['password']) {
        // Iniciar sesión
        session_start();
        $_SESSION['usuario'] = $user;
        
        echo json_encode([
            'success' => true,
            'usuario' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'rol' => $user['rol']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Credenciales incorrectas']);
    }
}

// api/estudiantes.php - CRUD Estudiantes
class EstudiantesAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getByNIE($nie) {
        $stmt = $this->db->prepare("SELECT * FROM estudiantes WHERE nie = ?");
        $stmt->execute([$nie]);
        return $stmt->fetch();
    }
    
    public function getAll($filtros = []) {
        $sql = "SELECT e.*, 
                       COUNT(r.id) as total_registros,
                       SUM(CASE WHEN r.tipo = 'demerito' THEN 1 ELSE 0 END) as total_demeritos,
                       SUM(CASE WHEN r.tipo = 'redencion' THEN 1 ELSE 0 END) as total_redenciones,
                       SUM(CASE WHEN r.tipo = 'reconocimiento' THEN 1 ELSE 0 END) as total_reconocimientos
                FROM estudiantes e
                LEFT JOIN registros r ON e.id = r.estudiante_id";
        
        $where = [];
        $params = [];
        
        if (!empty($filtros['ano'])) {
            $where[] = "e.ano = ?";
            $params[] = $filtros['ano'];
        }
        
        if (!empty($filtros['seccion'])) {
            $where[] = "e.seccion = ?";
            $params[] = $filtros['seccion'];
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " GROUP BY e.id ORDER BY e.nombre ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO estudiantes (nombre, nie, sexo, ano, seccion, turno)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            strtoupper($data['nombre']),
            $data['nie'],
            $data['sexo'],
            $data['ano'],
            $data['seccion'],
            $data['turno']
        ]);
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE estudiantes 
            SET nombre = ?, nie = ?, sexo = ?, ano = ?, seccion = ?, turno = ?
            WHERE id = ?
        ");
        $stmt->execute([
            strtoupper($data['nombre']),
            $data['nie'],
            $data['sexo'],
            $data['ano'],
            $data['seccion'],
            $data['turno'],
            $id
        ]);
        return $stmt->rowCount() > 0;
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM estudiantes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}

// api/registros.php - CRUD Registros
class RegistrosAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($filtros = []) {
        $sql = "SELECT * FROM vw_registros_completos";
        $where = [];
        $params = [];
        
        if (!empty($filtros['ano'])) {
            $where[] = "ano = ?";
            $params[] = $filtros['ano'];
        }
        
        if (!empty($filtros['tipo'])) {
            $where[] = "tipo = ?";
            $params[] = $filtros['tipo'];
        }
        
        if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
            $where[] = "fecha BETWEEN ? AND ?";
            $params[] = $filtros['fecha_desde'];
            $params[] = $filtros['fecha_hasta'];
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO registros (estudiante_id, fecha, tipo, letras_seleccionadas, observaciones, usuario_registro)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['estudiante_id'],
            $data['fecha'],
            $data['tipo'],
            $data['letras'],
            $data['observaciones'],
            $data['usuario_id']
        ]);
        return $this->db->lastInsertId();
    }
    
    public function getEstadisticas() {
        $stmt = $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM estudiantes) as total_estudiantes,
                (SELECT COUNT(*) FROM registros) as total_registros,
                (SELECT COUNT(*) FROM registros WHERE tipo = 'demerito') as total_demeritos,
                (SELECT COUNT(*) FROM registros WHERE tipo = 'redencion') as total_redenciones,
                (SELECT COUNT(*) FROM registros WHERE tipo = 'reconocimiento') as total_reconocimientos,
                (SELECT COUNT(*) FROM estudiantes WHERE ano = '1°') as total_1ano,
                (SELECT COUNT(*) FROM estudiantes WHERE ano = '2°') as total_2ano,
                (SELECT COUNT(*) FROM estudiantes WHERE ano = '3°') as total_3ano
        ");
        return $stmt->fetch();
    }
}

// api/catalogos.php - Catálogos
class CatalogosAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getByTipo($tipo) {
        $stmt = $this->db->prepare("SELECT * FROM catalogos WHERE tipo = ? AND estado = 'activo' ORDER BY orden");
        $stmt->execute([$tipo]);
        return $stmt->fetchAll();
    }
}