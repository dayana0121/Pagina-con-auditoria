<?php
// Controllers/OtpController.php

require_once __DIR__ . '/../Models/OtpManager.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Models/EmailService.php';

class OtpController {
    private $otpManager;
    private $auditoriaModel;
    private $emailService;

    public function __construct() {
        $this->otpManager = new OtpManager();
        $this->auditoriaModel = new Auditoria();
        $this->emailService = new EmailService();
    }

    /**
     * Verifica si el código ingresado por el usuario es correcto
     */
    public function verificar($codigoIngresado) {
        // 1. Validar que exista una sesión temporal (que hayan pasado por el LoginController primero)
        if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_email'])) {
            return [
                'success' => false, 
                'message' => 'Sesión expirada o inválida. Por favor, vuelve a iniciar sesión.'
            ];
        }

        $usuarioId = $_SESSION['temp_user_id'];
        $email = $_SESSION['temp_email'];

        // 2. Le preguntamos al Modelo si el código es válido
        if ($this->otpManager->validarOTP($usuarioId, $codigoIngresado)) {
            
            // --- CÓDIGO CORRECTO ---
            // Destruimos la sesión temporal y creamos la sesión real y definitiva
            $_SESSION['user_id'] = $usuarioId;
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_email']);

            // Registramos el éxito en la auditoría
            $this->auditoriaModel->registrarAcceso('OTP_EXITOSO', $email, $usuarioId, 'Autenticación de 2 factores completada');
            
            return ['success' => true];

        } else {
            // --- CÓDIGO INCORRECTO O EXPIRADO ---
            // Registramos el fallo para tener trazabilidad de posibles ataques
            $this->auditoriaModel->registrarAcceso('OTP_FALLIDO', $email, $usuarioId, "Intento fallido con código: $codigoIngresado");
            
            return [
                'success' => false, 
                'message' => 'El código es incorrecto o ha expirado. Verifica tu correo.'
            ];
        }
    }

    /**
     * Genera y envía un nuevo código si el usuario le da a "Reenviar"
     */
    public function reenviar() {
        if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_email'])) {
            return ['success' => false, 'message' => 'Sesión no válida.'];
        }

        $usuarioId = $_SESSION['temp_user_id'];
        $email = $_SESSION['temp_email'];

        // Generamos un nuevo código de 6 dígitos
        $nuevoCodigo = sprintf("%06d", mt_rand(1, 999999));

        // El modelo OtpManager automáticamente borrará el viejo y guardará este nuevo
        if ($this->otpManager->guardarOTP($usuarioId, $nuevoCodigo)) {
            
            // Enviamos el correo
            if ($this->emailService->enviarOTP($email, $nuevoCodigo)) {
                $this->auditoriaModel->registrarAcceso('OTP_EXITOSO', $email, $usuarioId, 'Nuevo código OTP generado y enviado');
                return ['success' => true, 'message' => 'Nuevo código enviado exitosamente.'];
            }
        }

        return ['success' => false, 'message' => 'Error al procesar el reenvío del código.'];
    }
}