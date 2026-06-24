// config.js - Configuración de la API
const API_CONFIG = {
    baseURL: 'http://localhost/sistema_demeritos/api/',
    endpoints: {
        auth: 'auth.php',
        estudiantes: 'estudiantes.php',
        registros: 'registros.php',
        catalogos: 'catalogos.php',
        estadisticas: 'estadisticas.php'
    }
};

// Cliente API
class APIClient {
    constructor() {
        this.baseURL = API_CONFIG.baseURL;
    }
    
    async request(endpoint, method = 'GET', data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        };
        
        if (data) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(this.baseURL + endpoint, options);
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error en la petición');
        }
        
        return await response.json();
    }
    
    // Métodos específicos
    async login(usuario, password) {
        return this.request('auth.php', 'POST', { usuario, password });
    }
    
    async getEstudiantes(filtros = {}) {
        const query = new URLSearchParams(filtros).toString();
        return this.request(`estudiantes.php?${query}`);
    }
    
    async getEstudianteByNIE(nie) {
        return this.request(`estudiantes.php?nie=${nie}`);
    }
    
    async createEstudiante(data) {
        return this.request('estudiantes.php', 'POST', data);
    }
    
    async updateEstudiante(id, data) {
        return this.request(`estudiantes.php?id=${id}`, 'PUT', data);
    }
    
    async deleteEstudiante(id) {
        return this.request(`estudiantes.php?id=${id}`, 'DELETE');
    }
    
    async getRegistros(filtros = {}) {
        const query = new URLSearchParams(filtros).toString();
        return this.request(`registros.php?${query}`);
    }
    
    async createRegistro(data) {
        return this.request('registros.php', 'POST', data);
    }
    
    async getEstadisticas() {
        return this.request('estadisticas.php');
    }
    
    async getCatalogos(tipo) {
        return this.request(`catalogos.php?tipo=${tipo}`);
    }
}

// Instancia global
const api = new APIClient();