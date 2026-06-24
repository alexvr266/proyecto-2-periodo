// js/config.js - Configuración de la API
const API_CONFIG = {
    baseURL: 'http://localhost/sistema_demeritos/api/',
    endpoints: {
        estudiantes: 'get_estudiantes.php',
        registros: 'registros.php',
        login: 'auth.php'
    }
};

// Exportar para usar en otros archivos
window.API_CONFIG = API_CONFIG;