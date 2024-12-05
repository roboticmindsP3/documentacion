<?php
require_once(__DIR__ . '/../config.php');

// Verificar si el usuario está autenticado
require_login();
$base_path = $CFG->documentacionroot . '/mod/';

// Obtener la ruta del subdirectorio solicitada
if (isset($_GET['path'])) {
    $ruta = urldecode($_GET['path']); // Sanitiza la ruta recibida
	$mostrar_directamente = urldecode($_GET['mostrar_directamente']);
    // Verifica que sea un directorio válido
    if (is_dir($ruta)) {
		
        listar_directorio(urldecode($ruta), 0, '', $mostrar_directamente); // Usa la función existente para listar el contenido del subdirectorio
    } else {
        echo "<p>No es un directorio válido.</p>";
    }
} else {
    echo "<p>No se especificó una ruta válida.</p>";
}


// Función para listar el contenido de un directorio como tarjetas
function listar_directorio($ruta, $nivel = 0, $numeracion = '', $mostrar_directamente = false ) {
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
						// Mostrar el título del archivo
						$nombre_archivo = pathinfo($ruta_relativa, PATHINFO_FILENAME);
						echo "<h3>$nombre_archivo</h3>";
						
						echo "<button id='button_iframe_container_$iframe_counter' class='toggle-button' onclick='loadIframe(\"iframe_container_$iframe_counter\", \"descargar.php?file=" . urlencode($ruta_relativa) . "&curso=" . urlencode($GLOBALS['curso']) . "&modalidad=" . urlencode($GLOBALS['modalidad']) . "\")'>Mostrar</button>";

						

						// Contenedor vacío que se llenará dinámicamente con el iframe
						echo "<div id='iframe_container_$iframe_counter' class='embedded-content' style='display: none;'></div>";
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
							// URL del iframe embebido
							$iframe_src = htmlspecialchars($matches[1]);
							$iframe_src = html_entity_decode($iframe_src);

							// Generar botón y contenedor vacío
							echo "<button id='button_iframe_container_$iframe_counter' class='toggle-button' onclick='loadIframe(\"iframe_container_$iframe_counter\", \"$iframe_src\")'>Mostrar</button>";
							echo "<div id='iframe_container_$iframe_counter' class='embedded-content' style='display: none;'></div>";
							$iframe_counter++; // Incrementa el contador

						} elseif (preg_match('/https:\/\/docs\.google\.com\/[^\s]+/', $contenido_txt, $matches)) {
							$url_google_docs = $matches[0];

							// Generar botón y contenedor vacío
							echo "<button id='button_iframe_container_$iframe_counter' class='toggle-button' onclick='loadIframe(\"iframe_container_$iframe_counter\", \"$url_google_docs\")'>Mostrar</button>";
							echo "<div id='iframe_container_$iframe_counter' class='embedded-content' style='display: none;'></div>";
							$iframe_counter++; // Incrementa el contador

						} elseif (preg_match('/https:\/\/www\.youtube\.com\/watch\?v=([^\s&]+)/', $contenido_txt, $matches) || preg_match('/https:\/\/youtu\.be\/([^\s&]+)/', $contenido_txt, $matches) || preg_match('/https:\/\/www\.youtubekids\.com\/watch\?v=([^\s&]+)/', $contenido_txt, $matches)) {
							// Enlace de YouTube
							$youtube_id = $matches[1];
							$youtube_embed_url = "https://www.youtube.com/embed/" . htmlspecialchars($youtube_id);

							// Generar botón y contenedor vacío
							echo "<button id='button_iframe_container_$iframe_counter' class='toggle-button' onclick='loadIframe(\"iframe_container_$iframe_counter\", \"$youtube_embed_url\")'>Mostrar</button>";
							echo "<div id='iframe_container_$iframe_counter' class='embedded-content' style='display: none;'></div>";
							$iframe_counter++; // Incrementa el contador

						} elseif (preg_match('/https:\/\/[^\s]+\.pdf/', $contenido_txt, $matches)) {
							// Enlace de PDF
							$url_pdf = $matches[0];

							// Generar botón y contenedor vacío
							echo "<button id='button_iframe_container_$iframe_counter' class='toggle-button' onclick='loadIframe(\"iframe_container_$iframe_counter\", \"$url_pdf\")'>Mostrar</button>";
							echo "<div id='iframe_container_$iframe_counter' class='embedded-content' style='display: none;'></div>";
							$iframe_counter++; // Incrementa el contador

						} elseif (preg_match('/https:\/\/[^\s]+/', $contenido_txt, $matches) || preg_match('/http:\/\/[^\s]+/', $contenido_txt, $matches)) {
							// Otros enlaces externos
							$url_enlace = $matches[0];
							echo "<a href='" . htmlspecialchars($url_enlace) . "' target='_blank'>Abrir enlace externo</a>";
						} else {
							// Mostrar el contenido del archivo si no hay embebidos
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


