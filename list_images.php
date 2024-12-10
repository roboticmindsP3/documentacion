<?php
// list_images.php

// Asegúrate de que el usuario esté autenticado
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../conbd2.php');
require_once($CFG->libdir . '/dml/mysqli_native_moodle_database.php'); 

if (!isloggedin()) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

if (isset($_GET['folder'])) {
    $folder = basename($_GET['folder']); // Sanitizar la entrada

    // Antes las imágenes estaban en documentacionroot/httpdocs/moodle/documentacion/
    // Ahora queremos que estén en doc_doce/mod/guias_2024/folder
    $images_dir = $CFG->documentacionroot . '/doc_doce/mod/guias_2024/' . $folder;

    // En vez de devolver URL directa, devolvemos URL a renderfile.php
    // Manteniendo renderfile.php en documentacion/
    $renderfile_url = $CFG->wwwroot . '/documentacion/renderfile.php';

    if (is_dir($images_dir)) {
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $imagenes = [];

        if ($dir = opendir($images_dir)) {
            while (($file = readdir($dir)) !== false) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, $extensiones_permitidas)) {
                    // Ahora la URL apunta a renderfile.php con file=folder/imagen
                    $ruta_imagen_url = $renderfile_url . '?file=' . urlencode($folder . '/' . $file);
                    $imagenes[] = htmlspecialchars($ruta_imagen_url);
                }
            }
            closedir($dir);
        }

        // Ordenar las imágenes alfabéticamente
        sort($imagenes);

        // Devolver JSON
        header('Content-Type: application/json');
        echo json_encode($imagenes);
        exit;
    } else {
        // Carpeta no existe
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }
} else {
    // Parámetro 'folder' no proporcionado
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}
