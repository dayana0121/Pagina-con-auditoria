<?php
/**
 * ============================================
 * VISTA: Procesador de acceso (Backend endpoint)
 * ============================================
 * Este archivo recibe las peticiones POST del frontend
 * (login y verificación OTP) y las dirige al controlador
 * correspondiente. Siempre responde en JSON para que
 * JavaScript del frontend pueda procesar la respuesta.
 *
 * Flujo:
 * - Recibo la acción por POST ('login', 'verify_otp', 'resend_otp')
 * - Incluyo la conexión a BD
 * - Incluyo el controlador necesario
 * - Ejecuto la función correspondiente
 * - Devuelvo la respuesta como JSON
 */

// Constante para evitar acceso directo a controladores/modelos
define('ACCESO_PERMITIDO', true);

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    // Configuración de sesión segura
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Cabecera de respuesta JSON
header('Content-Type: application/json; charset=utf-8');

// Solo aceptar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido.']);
    exit;
}

// Incluir conexión a base de datos
require_once dirname(dirname(__DIR__)) . '/config/database.php';

// Determinar qué acción ejecutar
 $accion = $_POST['accion'] ?? '';
/*
switch ($accion) {

    case 'login':
        // Procesar intento de login
        require_once dirname(dirname(__DIR__)) . '/controller/login.php';
        $respuesta = procesarLogin($pdo);
        break;

    case 'verify_otp':
        // Verificar código OTP
        require_once dirname(dirname(__DIR__)) . '/controller/otp.php';
        $respuesta = verificarOtp($pdo);
        break;

    case 'resend_otp':
        // Reenviar código OTP
        require_once dirname(dirname(__DIR__)) . '/controller/otp.php';
        $respuesta = reenviarOtp($pdo);
        break;

    default:
        http_response_code(400);
        $respuesta = ['success' => false, 'message' => 'Accion no reconocida.'];
        break;
}

// Devolver respuesta como JSON
echo json_encode($respuesta);
exit;