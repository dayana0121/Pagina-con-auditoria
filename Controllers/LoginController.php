<?php
// Controllers/LoginController.php

// Requerimos todos los modelos que este controlador va a necesitar coordinar
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Models/OtpManager.php';
require_once __DIR__ . '/../Models/EmailService.php';

class LoginController {
    private $usuarioModel;
    private $auditoriaModel;
    private $otpManager;
    private $emailService;

    public function __construct() {
        // Instanciamos los objetos
        $this->usuarioModel = new Usuario();
        $this->auditoriaModel = new Auditoria();
        $this->otpManager = new OtpManager();
        $this->emailService = new EmailService();
    }

    /**
     * Procesa el intento de inicio de sesión (Paso 1 del frontend)
     */
    public function autenticar($email, $password) {
        if ($this->usuarioModel->estaBloqueado($email)) {
            $this->auditoriaModel->registrarAcceso('CUENTA_BLOQUEADA', $email, null, 'Intento de acceso a cuenta bloqueada');
            return [
                'success' => false, 
                'field' => 'email', 
                'message' => 'Tu cuenta está temporalmente bloqueada. Intenta en 15 minutos.'
            ];
        }

        $usuario = $this->usuarioModel->buscarPorEmail($email);

        if (!$usuario) {
            $this->auditoriaModel->registrarAcceso('LOGIN_FALLIDO', $email, null, 'Correo no registrado');
            return ['success' => false, 'field' => 'email', 'message' => 'Credenciales incorrectas.'];
        }

        // --- BLOQUE MODIFICADO PARA DIAGNÓSTICO ---
        // Intentamos con password_verify O con comparación simple (solo para esta prueba)
        $passwordValida = password_verify($password, $usuario['password']) || ($password === $usuario['password']);

        if ($passwordValida) {
            // --- CONTRASEÑA CORRECTA ---
            $this->usuarioModel->resetearIntentos($email);
            $this->auditoriaModel->registrarAcceso('LOGIN_EXITOSO', $email, $usuario['id'], 'Paso 1 completado.');

            $codigoOTP = sprintf("%06d", mt_rand(1, 999999));
            
            if ($this->otpManager->guardarOTP($usuario['id'], $codigoOTP)) {
                // IMPORTANTE: Asegúrate que tus credenciales en EmailService.php sean correctas
                $correoEnviado = $this->emailService->enviarOTP($email, $codigoOTP);
                
                if ($correoEnviado) {
                    $_SESSION['temp_user_id'] = $usuario['id'];
                    $_SESSION['temp_email'] = $email;
                    return ['success' => true]; 
                } else {
                    // Si el login fue exitoso pero el correo falló, te lo avisará aquí
                    return ['success' => false, 'message' => 'Login OK, pero error al enviar correo. Revisa EmailService.php'];
                }
            } else {
                return ['success' => false, 'message' => 'Error al guardar OTP en la tabla otp_tokens.'];
            }

        } else {
            // --- CONTRASEÑA INCORRECTA ---
            $estadoFallo = $this->usuarioModel->registrarIntentoFallido($email);
            $this->auditoriaModel->registrarAcceso('LOGIN_FALLIDO', $email, $usuario['id'], 'Password mismatch');

            return [
                'success' => false, 
                'field' => 'password', 
                'message' => 'Credenciales incorrectas (Hash mismatch).'
            ];
        }
    }
}