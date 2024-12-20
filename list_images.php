<?php
// list_imagenes.php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../conbd2.php');
require_once($CFG->libdir . '/dml/mysqli_native_moodle_database.php'); 

if (!isloggedin()) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

if (isset($_GET['folder'])) {
    $folder = basename($_GET['folder']);
    $images_dir = $CFG->documentacionroot . '/doc_doce/mod/guias_2024/' . $folder;
    $renderfile_url = $CFG->wwwroot . '/documentacion/renderfile.php';

    if (is_dir($images_dir)) {
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $imagenes = [];

        if ($dir = opendir($images_dir)) {
            while (($file = readdir($dir)) !== false) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, $extensiones_permitidas)) {
                    // Buscamos archivos que terminen en _part1.ext
                    if (preg_match('/_part1\.' . $extension . '$/', $file)) {
                        $base_name = preg_replace('/_part1\.' . $extension . '$/', '', $file);

                        // Extraer número de página del $base_name
                        $pattern = '/page-(\d+)/';
                        preg_match($pattern, $base_name, $m);
                        $pagenum = isset($m[1]) ? (int)$m[1] : 0;

                        // Intentar obtener las 4 partes
                        $parts = [];
                        for ($i = 1; $i <= 4; $i++) {
                            $part_file = $base_name . '_part' . $i . '.' . $extension;
                            $part_path = $images_dir . '/' . $part_file;
                            if (!file_exists($part_path)) {
                                $parts = [];
                                break;
                            }
                            $parts[] = $renderfile_url . '?file=' . urlencode($folder . '/' . $part_file);
                        }

                        if (!empty($parts)) {
                            // Guardamos array con pagenum para ordenar después
                            $imagenes[] = [
                                'pagenum' => $pagenum,
                                'parts' => $parts
                            ];
                        }
                    }
                }
            }
            closedir($dir);
        }

        // Ordenar por pagenum
        usort($imagenes, function($a, $b) {
            return $a['pagenum'] - $b['pagenum'];
        });

        // Ahora extraemos sólo la parte 'parts' para el JSON final
        $solo_partes = array_map(function($item) {
            return $item['parts'];
        }, $imagenes);

        header('Content-Type: application/json');
        echo json_encode($solo_partes);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}
