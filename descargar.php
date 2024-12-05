<?php

// Asegúrate de que config.php está en el directorio raíz de Moodle y la ruta es correcta
require_once(__DIR__ . '/../config.php'); // Ajusta la ruta según la ubicación del script

// Verificar si el script se está ejecutando dentro del entorno de Moodle
if (!defined('MOODLE_INTERNAL')) {
    die('Este script solo puede ser ejecutado dentro de Moodle.');
}

// Verificar si el usuario está autenticado
require_login(); // Esta función verifica si el usuario está autenticado

// Verificar el dominio desde el cual se está haciendo la solicitud
$allowed_domain = 'plataforma.roboticminds.ec';
$request_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

// Verifica si el dominio de la solicitud coincide con el dominio permitido
if (strpos($request_host, $allowed_domain) === false) {
    die('Este script solo puede ser ejecutado desde el dominio permitido.');
}

// Obtener el contexto global
$context = context_system::instance();

// Verificar si el usuario es admin
$is_admin = has_capability('moodle/site:config', $context);

// Función para verificar si el usuario está matriculado en un curso específico
function is_user_enrolled_in_course($userid, $courseid) {
    global $DB;
    
    // Obtener el enrolment method IDs
    $enrolments = $DB->get_records('enrol', ['courseid' => $courseid]);
    
    foreach ($enrolments as $enrolment) {
        if ($DB->record_exists('user_enrolments', ['userid' => $userid, 'enrolid' => $enrolment->id])) {
            return true;
        }
    }
    
    return false;
}

// Define la ruta base al directorio deseado
$base_path = '/var/www/vhosts/plataforma.roboticminds.ec/doc_doce/mod/';

// Obtener el archivo solicitado y otras variables de la URL
$file = isset($_GET['file']) ? $_GET['file'] : '';
$curso = isset($_GET['curso']) ? $_GET['curso'] : '';
$modalidad = isset($_GET['modalidad']) ? $_GET['modalidad'] : '';

// Decodificar el archivo para evitar problemas con caracteres especiales
$file = rawurldecode($file);
// Construir la ruta completa del archivo
$ruta_archivo = rtrim($base_path, '/') . '/' . $file;


// Asegurarse de que la ruta del archivo no contenga caracteres inseguros
if (strpos(realpath($ruta_archivo), realpath($base_path)) !== 0) {
    echo "Acceso denegado.";
    exit;
}

// Verificar si el archivo existe
if (file_exists($ruta_archivo)) {
    $extension = strtolower(pathinfo($ruta_archivo, PATHINFO_EXTENSION));
    
    // Cambiar comportamiento según la extensión del archivo
    switch ($extension) {
        case 'pdf':
            header('Content-Type: application/pdf');
            readfile($ruta_archivo);
            break;
        case 'doc':
        case 'docx':
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: inline; filename="' . basename($ruta_archivo) . '"');
            readfile($ruta_archivo);
            break;
        case 'html':
        case 'htm':
            header('Content-Type: text/html');
            readfile($ruta_archivo);
            break;
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            header('Content-Type: image/' . $extension);
            readfile($ruta_archivo);
            break;
        default:
            // Si no es uno de los tipos soportados, forzar la descarga
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($ruta_archivo) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($ruta_archivo));
            readfile($ruta_archivo);
            break;
    }
    exit;
} else {
    echo "El archivo no existe: $ruta_archivo";
}
?>
