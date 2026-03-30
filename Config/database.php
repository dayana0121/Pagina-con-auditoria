<?php
// Config/Database.php

class Database {
    private static $instancia = null;
    private $conexion;

    // Credenciales por defecto de XAMPP
    private $host     = '127.0.0.1'; 
    private $db       = 'tecno'; 
    private $user     = 'root';
    private $pass     = '';
    private $charset  = 'utf8mb4';

    // El constructor es privado para evitar que se creen múltiples conexiones
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Manejo estricto de errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve arrays limpios
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Seguridad máxima contra Inyección SQL
        ];

        try {
            $this->conexion = new PDO($dsn, $this->user, $this->pass, $opciones);
        } catch (PDOException $e) {
            // Si falla la conexión, detenemos la ejecución y enviamos un JSON de error al frontend
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Error crítico de base de datos. Verifica que MySQL esté encendido en XAMPP.'
            ]);
            exit;
        }
    }

    // Prevenir clonación
    private function __clone() {}

    // Método estático para obtener la conexión desde cualquier otra clase
    public static function getInstancia() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        } 
        return self::$instancia->conexion;
    }
}