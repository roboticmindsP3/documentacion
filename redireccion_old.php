<?php

// Asegúrate de que config.php está en el directorio raíz de Moodle y la ruta es correcta
require_once(__DIR__ . '/../config.php'); // Ajusta la ruta según la ubicación del script

// Verificar si el script se está ejecutando dentro del entorno de Moodle
if (!defined('MOODLE_INTERNAL')) {
    die('Este script solo puede ser ejecutado dentro del entorno de Moodle.');
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

// Obtener el ID del rol managerpedagogico
$roleid = $DB->get_field('role', 'id', array('shortname' => 'managerpedagogico'));

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

// Obtener la instancia de usuario actual
global $USER;
$userid = $USER->id;
// Define la ruta base al directorio deseado
$base_path = '/var/www/vhosts/plataforma.roboticminds.ec/doc_doce/mod/'; // Ruta base corregida

// Encabezado HTML
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contenido del Curso</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #fff;
            color: #333;
        }
        h1 {
            color: #0066cc;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .directory {
            margin-bottom: 20px;
            cursor: pointer;
        }
        .directory a {
            color: #0066cc;
            text-decoration: none;
        }
        .directory a:hover {
            text-decoration: underline;
        }
        .directory-content {
            padding-left: 20px;
        }
        .file {
            margin: 5px 0;
        }
        .file a {
            color: #3f3dec;
            text-decoration: none;
        }
        .file a:hover {
            text-decoration: underline;
        }
        iframe {
            width: 100%;
            height: 500px;
            border: none;
        }
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
        details {
            margin-bottom: 10px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 5px;
        }
        .embedded-content {
            margin-top: 20px;
        }
        .toggle-button {
            display: block;
            margin: 10px 0;
            cursor: pointer;
            position: relative;
            z-index: 1000;
            background-color: #ffffff;
            color: #333;
            border: 1px solid #ddd;
            padding: 5px;
        }
    </style>
    <script>
        function toggleIframeVisibility(iframeId) {
            const iframe = document.getElementById(iframeId);
            const button = document.getElementById("button_" + iframeId);
            if (iframe && button) {  // Verificar si iframe y botón existen
                if (iframe.style.display === "none") {
                    iframe.style.display = "block";
                    button.textContent = "Ocultar";
                } else {
                    iframe.style.display = "none";
                    button.textContent = "Mostrar";
                }
            }
        }
    </script>
</head>
<body>';

echo '<div class="container">';

// Verificar si hay variables en la URL
if (!empty($_GET)) {
    // Variables para almacenar los datos obtenidos
    $curso = '';
    $modalidad = '';
    $courseid = '';
    
    foreach ($_GET as $clave => $valor) {
        // Limpiar los valores
        $valor_limpio = htmlspecialchars($valor);

        // Determinar el tipo de dato
        if ($clave == 'curso') {
            $curso = $valor_limpio; // Almacenar el valor del curso
        } elseif ($clave == 'modalidad') {
            $modalidad = $valor_limpio; // Almacenar el valor de la modalidad
        } elseif ($clave == 'id'){
            $courseid = $valor_limpio;
        }
    }

    // Verificar que tanto 'curso' como 'modalidad' están presentes
    if ($curso && $modalidad && $courseid) {
        // Obtener el curso por su nombre corto
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $courseshortname = $course->shortname;
        // Asegurarse de que 'curso' contiene al menos un guion
        if (strpos($courseshortname, '-') !== false) {
            // Extraer las partes del código después del '-'
            $partes_curso = explode('-', $courseshortname);
            $curso_codigo = isset($partes_curso[1]) ? $partes_curso[1] : '';
            $estudiante_docente = isset($partes_curso[0]) ? $partes_curso[0] : '';

            if (!$is_admin && !is_user_enrolled_in_course($userid, $courseid)) {
				// Si el usuario no tiene el rol 13 (por ejemplo, rol de administrador o algo similar)
				if ($roleid !== "13") {
					// Mensaje de error más detallado
					$error_message = sprintf(
						'No tienes permiso para acceder a este contenido. Curso ID: %d, User ID: %d, Role ID: %d',
						$courseid,
						$userid,
						$roleid
					);
					exit($error_message); // Usar exit para mayor claridad en la terminación del script
				}
			}
			// arreglo colegios la salle
			$a = ["sallesierra_2024", "uesjb_2024", "uejae_2024", "uesjb_2024", "uemsc_2024", "uefsjsc_2024", "ueflsa_2024", "flst_2024", "ueslb_2024", "uehms_2024", "uejbpa_2024", "uejlm_2024", "psr_2024", "uepc_2024", "ueffc_2024", "uemsq_2024", "uesjq_2024", "cah_2024"];

			// Verificar si $curso_codigo está en el array $a
			if (in_array($curso_codigo, $a)) {
				// Construir la ruta al directorio usando la ruta base definida
				$ruta_directorio = rtrim($base_path, '/') . '/' . $modalidad . '/' . $a[0] . '/' . $estudiante_docente;
				$mostrar_directamente = false;
			} else {
				// Construir la ruta al directorio usando la ruta base definida
				$ruta_directorio = rtrim($base_path, '/') . '/' . $modalidad . '/' . $curso_codigo . '/' . $estudiante_docente;
				$mostrar_directamente = false;
			}


            if ($estudiante_docente != 'pr' && $estudiante_docente != '') {
                $estudiante_docente = 'est';
				if (in_array($curso_codigo, $a)) {
                $ruta_directorio = rtrim($base_path, '/') . '/' . $modalidad . '/' . $a[0]  . '/' . $estudiante_docente . '/' . $partes_curso[0];
                $mostrar_directamente = true; // Mostrar documentos directamente para los estudiantes
				}else
				{
					$ruta_directorio = rtrim($base_path, '/') . '/' . $modalidad . '/' . $curso_codigo . '/' . $estudiante_docente . '/' . $partes_curso[0];
                $mostrar_directamente = true; // Mostrar documentos directamente para los estudiantes
				}
            }

            // Verificar si hay un subdirectorio especificado en la URL
            $subdir = isset($_GET['subdir']) ? $_GET['subdir'] : '';
            if ($subdir) {
                $ruta_directorio .= '/' . $subdir;
            }

            // Listar el directorio inicial
            if (is_dir($ruta_directorio)) {
                listar_directorio($ruta_directorio, 0, '', $mostrar_directamente);
            } else {
                echo "<p>No existe contenido en el curso que intenta ingresar, por favor intentelo más tarde o comuníquese con soporte@roboticminds.com.ec</p>";
            }
        } else {
            echo "<p>El valor de 'curso' no contiene un guion '-' para separar las partes.</p>";
        }
    } else {
        echo "<p>Faltan las variables 'curso' o 'modalidad'.</p>";
    }
} else {
    echo "<p>No se han recibido las variables necesarias para indicar el contenido del curso.</p>";
}

echo '</div>
<script src="script.js"></script> <!-- Incluye el archivo JavaScript -->
</body>
</html>';



// Función para listar el contenido de un directorio
function listar_directorio($ruta, $nivel = 0, $numeracion = '', $mostrar_directamente = false) {
    global $base_path;
    static $iframe_counter = 0; // Contador de iframes

    if ($handle = opendir($ruta)) {
        $contenido = [];

        while (false !== ($entrada = readdir($handle))) {
            if ($entrada != "." && $entrada != "..") {
                $contenido[] = $entrada;
            }
        }
        closedir($handle);

        natcasesort($contenido);
        $indice = 1;

        foreach ($contenido as $entrada) {
            $ruta_completa = $ruta . "/" . $entrada;
            $identificador_directorio = $numeracion . $indice;

            if (is_dir($ruta_completa)) {
                echo "<details class='directory'>
						<summary> $entrada</summary>
						<div class='directory-content'>";
                listar_directorio($ruta_completa, $nivel + 1, $identificador_directorio . '.', $mostrar_directamente);
                echo "</div></details>";
            } else {
                $archivo_enc = urlencode($entrada);
                $ruta_relativa = substr($ruta_completa, strlen($base_path));
                $extension = strtolower(pathinfo($entrada, PATHINFO_EXTENSION));

                echo "<div class='file'>"; // Contenedor para cada archivo

                if ($mostrar_directamente) {
                    if ($extension == 'pdf') {
                        echo "<button id='button_iframe_pdf_$iframe_counter' class='toggle-button' onclick='toggleIframeVisibility(\"iframe_pdf_$iframe_counter\")'>Ocultar</button>";
						// Obtener solo el nombre del archivo sin la ruta completa y sin la extensión
						$nombre_archivo = pathinfo($ruta_relativa, PATHINFO_FILENAME);

						// Mostrar el título con el nombre del archivo sin la extensión
						echo "<h3>$nombre_archivo</h3>";
                        echo "<iframe id='iframe_pdf_$iframe_counter' src='descargar.php?file=" . urlencode($ruta_relativa) . "&curso=" . urlencode($GLOBALS['curso']) . "&modalidad=" . urlencode($GLOBALS['modalidad']) . "' class='embedded-content'></iframe>";
                        $iframe_counter++; // Incrementa el contador
                    } elseif ($extension == 'doc' || $extension == 'docx') {
						// Construir el enlace de descarga
						$download_link = "descargar.php?file=" . urlencode($ruta_relativa) . "&curso=" . urlencode($GLOBALS['curso']) . "&modalidad=" . urlencode($GLOBALS['modalidad']);

						// Mostrar un enlace para descargar el archivo
						echo "Descargar <a href='" . htmlspecialchars($download_link) . "' class='embedded-content'>$entrada</a>";
					} elseif ($extension == 'html' || $extension == 'htm') {
                        echo "<a href='" . htmlspecialchars($ruta_completa) . "' target='_blank' class='embedded-content'>$entrada</a>";
                    } elseif ($extension == 'txt') {
                        // Obtener el contenido del archivo
                        $contenido_txt = file_get_contents($ruta_completa);

                        // Decodificar todas las entidades HTML del contenido
                        $contenido_txt_decoded = decode_entities($contenido_txt);
                        // Obtener el título del contenido
                        $titulo = obtener_titulo($contenido_txt_decoded);
                        if ($titulo) {
                            echo "<h2>$titulo</h2>"; // Mostrar el título si existe
                        }

                        // Verificar cualquier contenido embebido
                        if (preg_match('/<iframe.*src="([^"]+)".*<\/iframe>/', $contenido_txt, $matches) || preg_match('/<iframe[^>]*src="([^"]+)"[^>]*>/is', $contenido_txt_decoded, $matches)) {
                            // Decodificar la URL de Canva
                            $iframe_tag = $matches[0];
                            $decoded_url = html_entity_decode($iframe_tag);

                            // Extraer el atributo src
                            $iframe_src = htmlspecialchars($matches[1]);
                            $iframe_src = html_entity_decode($iframe_src);
                            // Construir el nuevo iframe con el formato indicado
                            $nuevo_iframe = "<iframe src=\"$iframe_src\" type=\"text/html\" allowscriptaccess=\"always\" allowfullscreen=\"true\" scrolling=\"yes\" allownetworking=\"all\"></iframe>";

                            // Reemplazar el iframe con el nuevo iframe construido
                            $iframe_tag_decoded = str_replace('<iframe', '<iframe id="iframe_embebed_' . $iframe_counter  . '"', $nuevo_iframe);

                            echo "<button id='button_iframe_embebed_$iframe_counter' class='toggle-button' onclick='toggleIframeVisibility(\"iframe_embebed_$iframe_counter\")'>Ocultar</button>";
                            echo "<div class='embedded-content'>" . $iframe_tag_decoded . "</div>";
                            $iframe_counter++; // Incrementa el contador
                        } elseif (preg_match('/https:\/\/docs\.google\.com\/[^\s]+/', $contenido_txt, $matches)) {
							$url_google_docs = $matches[0];
							echo "<button id='button_google_docs_$iframe_counter' class='toggle-button' onclick='toggleIframeVisibility(\"google_docs_$iframe_counter\")'>Ocultar</button>";
							echo "<iframe id='google_docs_$iframe_counter' src='" . htmlspecialchars($url_google_docs) . "' width='100%' height='600px' frameborder='0'></iframe>";
							$iframe_counter++; // Incrementa el contador
						} elseif (preg_match('/https:\/\/www\.youtube\.com\/watch\?v=([^\s&]+)/', $contenido_txt, $matches) || preg_match('/https:\/\/youtu\.be\/([^\s&]+)/', $contenido_txt, $matches) || preg_match('/https:\/\/www\.youtubekids\.com\/watch\?v=([^\s&]+)/', $contenido_txt, $matches)) {
							// Enlace de YouTube
							$youtube_id = $matches[1];
							echo "<button id='button_youtube_$iframe_counter' class='toggle-button' onclick='toggleIframeVisibility(\"youtube_$iframe_counter\")'>Ocultar</button>";
							echo "<iframe id='youtube_$iframe_counter' width='560' height='315' src='https://www.youtube.com/embed/" . htmlspecialchars($youtube_id) . "' frameborder='0' allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe>";
							$iframe_counter++; // Incrementa el contador
						} elseif (preg_match('/https:\/\/[^\s]+\.pdf/', $contenido_txt, $matches)) {
							// Enlace de PDF
							$url_pdf = $matches[0];
							echo "<button id='button_pdf_$iframe_counter' class='toggle-button' onclick='toggleIframeVisibility(\"pdf_$iframe_counter\")'>Ocultar</button>";
							echo "<iframe id='pdf_$iframe_counter' src='" . htmlspecialchars($url_pdf) . "' width='100%' height='600px' frameborder='0'></iframe>";
							$iframe_counter++; // Incrementa el contador
						} elseif (preg_match('/https:\/\/[^\s]+/', $contenido_txt, $matches)) {
						// Otros enlaces externos
						$url_enlace = $matches[0];
						echo "<a href='" . htmlspecialchars($url_enlace) . "' target='_blank'>Abrir enlace externo</a>";
					} else {
                            echo "<pre>" . htmlspecialchars($contenido_txt) . "</pre>";
                        }
                    } elseif ($extension == 'png' || $extension == 'jpg' || $extension == 'jpeg' || $extension == 'gif') {
                        echo "<img src='descargar.php?file=" . urlencode($ruta_relativa) . "&curso=" . urlencode($GLOBALS['curso']) . "&modalidad=" . urlencode($GLOBALS['modalidad']) . "' alt='$entrada' style='max-width:100%;'>";
                    } else {
                        echo "Ver <a href='descargar.php?file=" . urlencode($ruta_relativa) . "&curso=" . urlencode($GLOBALS['curso']) . "&modalidad=" . urlencode($GLOBALS['modalidad']) . "' target='_blank'>$entrada</a>";
                    }
                } else {
                    echo "Descargar <a href='descargar.php?file=" . urlencode($ruta_relativa) . "&curso=" . urlencode($GLOBALS['curso']) . "&modalidad=" . urlencode($GLOBALS['modalidad']) . "'>$entrada</a>";
                }

                echo "</div>"; // Cierre del contenedor de archivo
            }
            $indice++;
        }
    } else {
        echo "<p>No se puede abrir el contenido, por favor intentelo más tarde o comuníquese con soporte@roboticminds.com.ec</p>";
    }
}


function decode_entities($text) {
    // Decodificar entidades HTML estándar
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

    // Decodificar entidades numéricas hexadecimales (por ejemplo, &#x2F; -> /)
    $text = preg_replace_callback('/&#x([0-9a-fA-F]+);/', function ($matches) {
        return mb_chr(hexdec($matches[1]), 'UTF-8');
    }, $text);

    // Decodificar entidades numéricas decimales (por ejemplo, &#47; -> /)
    $text = preg_replace_callback('/&#([0-9]+);/', function ($matches) {
        return mb_chr($matches[1], 'UTF-8');
    }, $text);

    return $text;
}

// Función para extraer el título del contenido de un archivo .txt
function obtener_titulo($contenido_txt) {
    // Buscar todas las variaciones posibles de "titulo"
    if (preg_match('/^(titulo|Titulo)\s*:\s*(.*)/im', $contenido_txt, $matches)) {
        return trim($matches[2]); // Extraer el título después de "Titulo:"
    }
    return null; // Si no se encuentra, retornar null
}
?>
