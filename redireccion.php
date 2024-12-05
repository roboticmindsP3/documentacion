<?php

// Asegúrate de que config.php está en el directorio raíz de Moodle y la ruta es correcta
require_once(__DIR__ . '/../config.php'); // Ajusta la ruta según la ubicación del script

// Verificar si el script se está ejecutando dentro del entorno de Moodle
/*if (!defined('MOODLE_INTERNAL')) {
    die('Este script solo puede ser ejecutado dentro del entorno de Moodle.');
}*/

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
$base_path = $CFG->documentacionroot . '/mod/'; // Ruta base corregida


// Encabezado HTML
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Contenido del Curso</title>
</head>
<body>
    
	

    <!-- JavaScript -->
    <script>
        function toggleIframeVisibility(iframeId) {
            const iframe = document.getElementById(iframeId);
            const button = document.getElementById("button_" + iframeId);
            if (iframe && button) { // Verificar si iframe y botón existen
                if (iframe.style.display === "none") {
                    iframe.style.display = "block";
                    button.textContent = "Ocultar";
                } else {
                    iframe.style.display = "none";
                    button.textContent = "Mostrar";
                }
            }
        }

        function loadIframe(containerId, iframeSrc) {
            const container = document.getElementById(containerId);

            // Si el iframe ya fue creado, solo alternar visibilidad
            if (container.querySelector("iframe")) {
                container.style.display = container.style.display === "none" ? "block" : "none";
                return;
            }

            // Crear el iframe dinámicamente
            const iframe = document.createElement("iframe");
            iframe.src = iframeSrc;
            iframe.style.width = "100%";
            iframe.style.height = "600px";
            iframe.frameBorder = "0";
            
            // Agregar el iframe al contenedor
            container.appendChild(iframe);
            container.style.display = "block"; // Mostrar el contenedor
        }
    </script>';

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
				$mostrar_directamente = true;
			} else {
				// Construir la ruta al directorio usando la ruta base definida
				$ruta_directorio = rtrim($base_path, '/') . '/' . $modalidad . '/' . $curso_codigo . '/' . $estudiante_docente;
				$mostrar_directamente = true;
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
				echo '<div class="menu_principal">
						<!-- Menú principal -->
						<div id="directorios-principales">';
								listar_directorios_principales($ruta_directorio, $mostrar_directamente);
								echo '
						</div>
					</div>';
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

echo '<div class="conteiner_contenido">
			<!-- Contenido dinámico -->
			<div id="contenido">
				<!-- Aquí se cargará dinámicamente el contenido del subdirectorio seleccionado -->
			</div>
		</div>
</div>

		
<script src="script.js"></script> <!-- Incluye el archivo JavaScript -->
</body>
</html>';


function listar_directorios_principales($ruta, $mostrar_directamente) {
    if ($handle = opendir($ruta)) {
        $contenido = [];
        while (false !== ($entrada = readdir($handle))) {
            if ($entrada != "." && $entrada != ".." && is_dir($ruta . "/" . $entrada)) {
                $contenido[] = $entrada; // Solo agregar directorios
            }
        }
        closedir($handle);
        natcasesort($contenido);

        $is_first = true; // Variable para marcar el primer botón
        foreach ($contenido as $entrada) {
            $default_attr = $is_first ? 'data-default="true"' : '';
            $is_first = false; // Solo el primer botón tendrá este atributo

            // Botón para cargar subdirectorio dinámicamente
            echo "<button class='subdirectory-btn' $default_attr data-path='" . htmlspecialchars($ruta . '/' . $entrada . '&mostrar_directamente=' . $mostrar_directamente, ENT_QUOTES, 'UTF-8') . "'> $entrada </button>";
        }
    } else {
        echo "<p>Error al abrir el directorio. Comuníquese con soporte.</p>";
    }
}


?>


<script>
	document.addEventListener('DOMContentLoaded', () => {
    // Seleccionar el primer botón marcado como predeterminado
    const defaultButton = document.querySelector('.subdirectory-btn[data-default="true"]');
    if (defaultButton) {
        // Marcar como seleccionado
        defaultButton.classList.add('active');

        // Obtener la ruta desde el atributo del botón
        const ruta = defaultButton.getAttribute('data-path');

        // Llamar a la función que carga el contenido del subdirectorio
        fetch("cargar_subdirectorio.php?path=" + ruta)
            .then(response => response.text())
            .then(data => {
                document.getElementById("contenido").innerHTML = data; // Cargar el contenido
            })
            .catch(error => {
                console.error("Error al cargar el contenido:", error);
            });
    }

    // Configurar eventos para los botones del subdirectorio
    const directorios = document.querySelectorAll('#directorios-principales button');
    directorios.forEach((button) => {
        button.addEventListener('click', (event) => {
            // Remover la clase 'active' de todos los botones
            directorios.forEach((btn) => btn.classList.remove('active'));

            // Agregar la clase 'active' al botón seleccionado
            event.currentTarget.classList.add('active');

            // Obtener la ruta del botón seleccionado
            const ruta = event.currentTarget.getAttribute('data-path');

            // Cargar el contenido del subdirectorio
            fetch("cargar_subdirectorio.php?path=" + ruta)
                .then(response => response.text())
                .then(data => {
                    document.getElementById("contenido").innerHTML = data; // Cargar el contenido
                })
                .catch(error => {
                    console.error("Error al cargar el contenido:", error);
                });
        });
    });
});


	
    

</script>


