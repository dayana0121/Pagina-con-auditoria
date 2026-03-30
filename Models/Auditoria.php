<?php
// Models/Auditoria.php

// Requerimos el Singleton de la base de datos
require_once __DIR__ . '/../Config/Database.php';

class Auditoria {
    private $db;

    public function __construct() {
        // Al instanciar la clase, obtenemos la conexión segura
        $this->db = Database::getInstancia();
    }

    /**
     * Registra un evento de acceso en la bitácora
     */
    public function registrarAcceso($tipoEvento, $emailProvisto, $usuarioId = null, $detalles = null) {
        // Capturamos la IP real y el navegador del usuario automáticamente
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

        // Preparamos la consulta SQL de inserción
        $sql = "INSERT INTO auditoria_accesos 
                (usuario_id, email_provisto, tipo_evento, detalles, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        // Ejecutamos pasando los parámetros de forma segura
        return $stmt->execute([
            $usuarioId, 
            $emailProvisto, 
            $tipoEvento, 
            $detalles, 
            $ipAddress, 
            $userAgent
        ]);
    }

    /**
     * Actualiza el contador global de visitas en tiempo real
     * Si es la primera visita del día, crea la fila. Si ya existe, suma +1.
     */
    public function registrarVisitaGlobal() {
        $sql = "INSERT INTO visitas_tiempo_real (fecha, total_visitas) 
                VALUES (CURDATE(), 1) 
                ON DUPLICATE KEY UPDATE total_visitas = total_visitas + 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }
}