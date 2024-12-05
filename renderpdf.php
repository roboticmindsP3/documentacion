<?php
require_once(__DIR__ . '/../config.php');

if (isloggedin()) {
    $username = $USER->username;
} else {
    die('Acceso denegado');
}

$base_path = '/var/www/vhosts/plataforma.roboticminds.ec/doc_doce/mod/guias_2024';

if (isset($_GET['file'])) {
    $file = basename(rawurldecode($_GET['file']));
} else {
    die('No se ha especificado un archivo.');
}

$file_path = $base_path . '/' . $file;

if (file_exists($file_path)) {
    session_write_close();

    $file_size = filesize($file_path);
    $block_size = ($file_size > 10 * 1024 * 1024) ? 16384 : 8192;

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    if (isset($_SERVER['HTTP_RANGE'])) {
        list($param, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        list($range_start, $range_end) = explode('-', $range);
        $range_start = intval($range_start);
        $range_end = ($range_end === '') ? $file_size - 1 : intval($range_end);
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

    $fp = fopen($file_path, 'rb');
    while (!feof($fp)) {
        echo fread($fp, $block_size);
        flush();
    }
    fclose($fp);
    exit;
} else {
    echo "El archivo no existe.";
}
?>
