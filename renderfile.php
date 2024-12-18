<?php
require_once(__DIR__ . '/../config.php');

if (!isloggedin()) {
    die('Acceso denegado');
}

// La misma ruta base que usabas en renderpdf.php
$base_path = $CFG->documentacionroot . '/doc_doce/mod/guias_2024';

// Antes en renderpdf.php usabas basename porque era un PDF en la raíz. 
// Ahora necesitas permitir subcarpetas, así que haremos lo mismo que con imágenes.
if (!isset($_GET['file'])) {
    die('No se ha especificado un archivo.');
}

$requested_path = rawurldecode($_GET['file']);
$requested_path = str_replace('..','',$requested_path);
$requested_path = str_replace('\\','',$requested_path);
$requested_path = str_replace('//','/',$requested_path);

session_start();
require_once(__DIR__ . '/../config.php');

// Verificar si el usuario está autenticado
if (!isloggedin()) {
    header("HTTP/1.1 403 Forbidden");
    exit("Acceso denegado.");
}

// Sanitizar el parámetro 'file' para mayor seguridad
$requested_path = rawurldecode($_GET['file'] ?? '');
$requested_path = str_replace(['..', '\\', '//'], '', $requested_path);
$file_path = realpath($base_path . '/' . $requested_path);

// Validar si el archivo existe y está dentro del directorio permitido
if (!$file_path || strpos($file_path, $base_path) !== 0 || !file_exists($file_path)) {
    header("HTTP/1.1 404 Not Found");
    exit("Archivo no encontrado.");
}

// Configurar cabeceras para proteger la entrega
$extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
switch ($extension) {
    case 'jpg': $content_type = 'image/jpeg'; break;
    case 'png': $content_type = 'image/png'; break;
    case 'gif': $content_type = 'image/gif'; break;
    default: $content_type = 'application/octet-stream'; break;
}

header('Content-Type: ' . $content_type);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Disposition: inline; filename="' . basename($file_path) . '"');

// Entregar el archivo en bloques
$block_size = 8192;
$fp = fopen($file_path, 'rb');
while (!feof($fp)) {
    echo fread($fp, $block_size);
    flush();
}
fclose($fp);
exit;

// Detectar extensión para elegir Content-Type
$extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
switch ($extension) {
    case 'pdf':
        $content_type = 'application/pdf';
        break;
    case 'jpg':
	 	$content_type = 'image/jpg';
        break;
    case 'jpeg':
        $content_type = 'image/jpeg';
        break;
    case 'png':
        $content_type = 'image/png';
        break;
    case 'gif':
        $content_type = 'image/gif';
        break;
    case 'webp':
        $content_type = 'image/webp';
        break;
    default:
        // Si es otro tipo de archivo, se sirve genérico
        $content_type = 'application/octet-stream';
        break;
}

session_write_close();
$file_size = filesize($file_path);
$block_size = ($file_size > 10 * 1024 * 1024) ? 16384 : 8192;

header('Content-Type: ' . $content_type);
header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Manejo de rangos (igual que en renderpdf.php)
if (isset($_SERVER['HTTP_RANGE'])) {
    list($param, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    list($range_start, $range_end) = explode('-', $range);
    $range_start = intval($range_start);
    $range_end = ($range_end === '') ? $file_size - 1 : intval($range_end);

    if ($range_start > $range_end || $range_end >= $file_size) {
        header("HTTP/1.1 416 Requested Range Not Satisfiable");
        exit;
    }

    $length = $range_end - $range_start + 1;

    header('HTTP/1.1 206 Partial Content');
    header('Content-Range: bytes ' . $range_start . '-' . $range_end . '/' . $file_size);
    header('Content-Length: ' . $length);

    $fp = fopen($file_path, 'rb');
    fseek($fp, $range_start);
    while (!feof($fp) && $length > 0) {
        $read = min($block_size, $length);
        echo fread($fp, $read);
        flush();
        $length -= $read;
    }
    fclose($fp);
    exit;
}

// Si no hay rango, servir todo el archivo
header('Content-Length: ' . $file_size);
$fp = fopen($file_path, 'rb');
while (!feof($fp)) {
    echo fread($fp, $block_size);
    flush();
}
fclose($fp);
exit;
