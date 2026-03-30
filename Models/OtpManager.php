<?php
// Models/OtpManager.php

require_once __DIR__ . '/../Config/Database.php';

class OtpManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstancia();
    }

    /**
     * Guarda un nuevo código OTP en la base de datos para un usuario específico.
     * Automáticamente elimina cualquier código anterior no usado para mantener la tabla limpia.
     */
    public function guardarOTP($usuarioId, $codigo, $minutosValidez = 5) {
        try {
            // 1. Limpiar códigos viejos de este usuario (Previene acumulación de basura)
            $stmtDelete = $this->db->prepare("DELETE FROM otp_tokens WHERE usuario_id = ?");
            $stmtDelete->execute([$usuarioId]);

            // 2. Calcular la fecha y hora exacta en la que el código dejará de servir
            $expiraEn = date('Y-m-d H:i:s', strtotime("+$minutosValidez minutes"));

            // 3. Insertar el nuevo código
            $stmtInsert = $this->db->prepare("INSERT INTO otp_tokens (usuario_id, codigo, expira_en) VALUES (?, ?, ?)");
            return $stmtInsert->execute([$usuarioId, $codigo, $expiraEn]);
            
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Valida si el código ingresado por el usuario es correcto y no ha expirado.
     */
    public function validarOTP($usuarioId, $codigoIngresado) {
        // Buscamos el último código generado para este usuario
        $stmt = $this->db->prepare("SELECT codigo, expira_en FROM otp_tokens WHERE usuario_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$usuarioId]);
        $registro = $stmt->fetch();

        if ($registro) {
            $fechaActual = date('Y-m-d H:i:s');
            
            // Verificamos dos cosas: que el código sea idéntico y que la fecha de expiración sea mayor a la actual
            if ($registro['codigo'] === $codigoIngresado && $registro['expira_en'] >= $fechaActual) {
                
                // ¡Éxito! Como es un código de un solo uso, lo destruimos inmediatamente
                $stmtDelete = $this->db->prepare("DELETE FROM otp_tokens WHERE usuario_id = ?");
                $stmtDelete->execute([$usuarioId]);
                
                return true;
            }
        }
        
        // Si no existe, no coincide o ya expiró
        return false;
    }
}