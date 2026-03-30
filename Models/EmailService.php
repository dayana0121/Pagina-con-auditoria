<?php
// Models/EmailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class EmailService {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);

        // --- CONFIGURACIÓN DEL SERVIDOR SMTP ---
        $this->mailer->isSMTP();
        $this->mailer->Host       = 'smtp.gmail.com'; 
        $this->mailer->SMTPAuth   = true;
        
        // 1. TU CORREO REAL (El que enviará los códigos)
        $this->mailer->Username   = 'diegogaliano2005@gmail.com'; 
        
        // 2. TU CONTRASEÑA DE APLICACIÓN (16 letras, sin espacios)
        $this->mailer->Password   = 'xehrhwkrczvaxlhi'; 
        
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $this->mailer->Port       = 587; 
        $this->mailer->CharSet    = 'UTF-8';

        // 3. IDENTIDAD DEL REMITENTE
        // Aquí pones tu correo de nuevo y el nombre que quieres que el usuario vea
        $this->mailer->setFrom('tu_correo@gmail.com', 'SecureAuth - Sistema de Seguridad');
    }

    public function enviarOTP($emailDestino, $codigoOTP) {
        try {
            $this->mailer->clearAddresses();
            
            // $emailDestino es la variable que viene del formulario de login
            // Enviará el código a CUALQUIER correo que el usuario ingrese
            $this->mailer->addAddress($emailDestino);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Tu codigo de verificacion seguro';
            
            $this->mailer->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                    <h2 style='color: #333; text-align: center;'>Verificación de Acceso</h2>
                    <p style='color: #555; font-size: 16px;'>Has solicitado iniciar sesión en el sistema.</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <span style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #0066ff; background: #f4f8ff; padding: 15px 25px; border-radius: 8px;'>{$codigoOTP}</span>
                    </div>
                    <p style='color: #777; font-size: 14px; text-align: center;'>Este código expirará en 5 minutos.</p>
                </div>
            ";

            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Error PHPMailer: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
}