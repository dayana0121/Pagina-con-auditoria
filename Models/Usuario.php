<?php
// Models/Usuario.php

require_once __DIR__ . '/../Config/Database.php';

class Usuario {
    private $db;
    private $maxIntentos = 3; // Bloquear al 3er intento fallido
    private $minutosBloqueo = 15; // Tiempo de castigo

    public function __construct() {
        $this->db = Database::getInstancia();
    }

    /**
     * Busca un usuario por su correo electrónico
     */
    public function buscarPorEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Verifica si la cuenta está actualmente bloqueada
     */
    public function estaBloqueado($email) {
        $usuario = $this->buscarPorEmail($email);
        
        if ($usuario && $usuario['bloqueado_hasta'] !== null) {
            $fechaActual = date('Y-m-d H:i:s');
            if ($usuario['bloqueado_hasta'] > $fechaActual) {
                return true; // Sigue castigado
            } else {
                // El tiempo de castigo ya pasó, reseteamos la cuenta
                $this->resetearIntentos($email);
                return false;
            }
        }
        return false;
    }

    /**
     * Registra un intento fallido de contraseña
     */
    public function registrarIntentoFallido($email) {
        $usuario = $this->buscarPorEmail($email);
        
        if ($usuario) {
            $nuevosIntentos = $usuario['intentos_fallidos'] + 1;
            
            if ($nuevosIntentos >= $this->maxIntentos) {
                // Bloquear cuenta
                $bloqueo = date('Y-m-d H:i:s', strtotime("+$this->minutosBloqueo minutes"));
                $stmt = $this->db->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE email = ?");
                $stmt->execute([$nuevosIntentos, $bloqueo, $email]);
                return 'BLOQUEADO';
            } else {
                // Solo sumar intento
                $stmt = $this->db->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE email = ?");
                $stmt->execute([$nuevosIntentos, $email]);
                return 'FALLO';
            }
        }
        return null;
    }

    /**
     * Reinicia a cero los fallos cuando el usuario hace un login exitoso
     */
    public function resetearIntentos($email) {
        $stmt = $this->db->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE email = ?");
        $stmt->execute([$email]);
    }
}