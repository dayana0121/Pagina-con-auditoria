<?php
// Views/login/acceso.php

// 1. Configuración estricta de sesiones (Seguridad)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// 2. Cabecera JSON para que el JavaScript del frontend lo procese correctamente
header('Content-Type: application/json; charset=utf-8');

// 3. Bloquear cualquier acceso directo por la URL (Solo POST permitido)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// 4. Importar los Controladores (Subimos dos niveles con __DIR__ . '/../../')
require_once __DIR__ . '/../../Controllers/LoginController.php';
require_once __DIR__ . '/../../Controllers/OtpController.php';
require_once __DIR__ . '/../../Models/Auditoria.php';
require_once __DIR__ . '/../../Config/Database.php';

// 5. Capturar la acción solicitada por el frontend
$accion = $_POST['accion'] ?? '';

// 6. El Enrutador (Switch)
switch ($accion) {
    
    case 'login':
        // Sanitizamos el correo antes de pasarlo al controlador
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        // Instanciamos el controlador y ejecutamos el método
        $loginController = new LoginController();
        $resultado = $loginController->autenticar($email, $password);
        
        // Devolvemos la respuesta al JavaScript
        echo json_encode($resultado);
        break;

    case 'verify_otp':
        // Limpiamos el token para asegurarnos de que solo sean números
        $token = preg_replace('/[^0-9]/', '', $_POST['token'] ?? '');
        
        $otpController = new OtpController();
        $resultado = $otpController->verificar($token);
        
        echo json_encode($resultado);
        break;

    case 'resend_otp':
        $otpController = new OtpController();
        $resultado = $otpController->reenviar();
        
        echo json_encode($resultado);
        break;

    case 'get_session':
        // Este endpoint lo usa tu panel de dashboard para pintar el nombre y correo del usuario
        if (isset($_SESSION['user_id'])) {
            $db = Database::getInstancia();
            $stmt = $db->prepare("SELECT nombre, email FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo json_encode(['success' => true, 'name' => $user['nombre'], 'email' => $user['email']]);
            } else {
                echo json_encode(['success' => false]);
            }
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'logout':
        // Destruimos la sesión y registramos la salida en la auditoría
        if (isset($_SESSION['user_id'])) {
            $auditoria = new Auditoria();
            $auditoria->registrarAcceso('LOGOUT', 'N/A', $_SESSION['user_id'], 'Cierre de sesión manual exitoso');
        }
        session_unset();
        session_destroy();
        
        echo json_encode(['success' => true]);
        break;

    default:
        // Si mandan una acción que no existe en el switch
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no reconocida por el servidor.']);
        break;
}