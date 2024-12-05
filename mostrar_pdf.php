<?php
    // Asegúrate de que el usuario esté autenticado
    require_once(__DIR__ . '/../config.php');
	require_once(__DIR__ . '/../conbd2.php');
	require_once($CFG->libdir . '/dml/mysqli_native_moodle_database.php'); 
	

	try {
		// Crear nueva instancia de la base de datos
		$segundabd = new mysqli_native_moodle_database();

		// Conectar a la segunda base de datos
		$segundabd->connect($CFGS->dbhost, $CFGS->dbuser, $CFGS->dbpass, $CFGS->dbname, $CFGS->dboptions['dbcollation']);

	} catch (Exception $e) {
		echo "Error conectando a la segunda base de datos: " . $e->getMessage();
	}


	$sql = "SELECT c.id AS course_id, c.fullname AS course_name, c.shortname AS course_shortname
        FROM {user} u
        JOIN {user_enrolments} ue ON ue.userid = u.id
        JOIN {enrol} e ON e.id = ue.enrolid
        JOIN {course} c ON c.id = e.courseid
        WHERE u.id = :userid AND c.shortname LIKE :shortname";

	$params = [
		'userid' => $USER->id,
		'shortname' => '%pr-%'  // El valor para el LIKE
	];

	// Utiliza get_records_sql() para obtener los resultados
	$cursos_docente = $DB->get_records_sql($sql, $params);
	$guias_totales = array();
	$a = ["sallesierra_2024", "uesjb_2024", "uejae_2024", "uesjb_2024", "uemsc_2024", "uefsjsc_2024", "ueflsa_2024", "flst_2024", "ueslb_2024", "uehms_2024", "uejbpa_2024", "uejlm_2024", "psr_2024", "uepc_2024", "ueffc_2024", "uemsq_2024", "uesjq_2024", "cah_2024"];

	foreach ($cursos_docente as $curso) {
		// Dividir el shortname por el guión (-)
		$partes = explode('-', $curso->course_shortname);
		$cod_curso = $partes[1]; 
		// Definir el array $a
		
		// Verificar si $cod_curso está en el array $a
		if (in_array($cod_curso, $a)) {
			// Si está en el array, usar 'sallesierra_2024' como prefijo para la búsqueda
			$cod_curso = 'sallesierra_2024';
		}
		$guias_permitidas = $segundabd->get_records_sql('SELECT guias_permitidas FROM rm_colegios_guias_construc WHERE codigo_colegio = ?', array($cod_curso));
		// Recorrer los resultados
		foreach ($guias_permitidas as $guia) {
			// Decodificar el JSON de la columna guias_permitidas
			$json_decoded = json_decode($guia->guias_permitidas, true); // 'true' para obtener un array asociativo

			// Verificar si se pudo decodificar el JSON correctamente
			if (is_array($json_decoded)) {
				// Combinar el JSON decodificado en el array principal
				$guias_totales = array_merge($guias_totales, $json_decoded);
			} else {
				// Si no es un JSON válido, puedes manejar el error aquí
				echo "Error al decodificar el JSON en la columna 'guias_permitidas'.";
			}
		}
				
	}

    if (isloggedin()) {
        $username = $USER->username;
    } else {
        $username = 'Invitado';
    }

    // Mostrar la lista de PDFs disponibles
    $base_dir = '/var/www/vhosts/plataforma.roboticminds.ec/doc_doce/mod/guias_2024';

    if (is_dir($base_dir)) {
    if ($dir = opendir($base_dir)) {
        echo "<h2>Lista de Guías de Construcción disponibles</h2>";
        echo "<ul>";

        $pdfs_disponibles = false; // Variable para comprobar si se ha encontrado algún PDF

        // Listar los archivos PDF
        while (($file = readdir($dir)) !== false) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                // Obtener la parte antes del primer guion bajo ('_') y el resto del nombre del archivo
                $partes = explode('_', $file, 2); // Limitar a 2 partes
                $codigo_pdf = $partes[0]; // El código antes del guion bajo
                $nombre_visible = isset($partes[1]) ? $partes[1] : $file; // El nombre después del guion bajo
				$nombre_visible = preg_replace('/\.pdf$/', '', $nombre_visible); // Quitar la extensión .pdf
				$nombre_visible = str_replace(['_', '-'], ' ', $nombre_visible); // Reemplazar guiones bajos por espacios

                // Comprobar si el código del archivo está en el array de guias_totales
                if (array_key_exists($codigo_pdf, $guias_totales)) {
                    // Si coincide, mostrar solo la parte después del guion bajo en la lista
                    echo "<li><a href='#' onclick='mostrarPDF(\"" . urlencode($file) . "\")'>" . htmlspecialchars($nombre_visible) . "</a></li>";
                    $pdfs_disponibles = true; // Marcar que se ha encontrado un PDF disponible
                }
            }
        }

        if (!$pdfs_disponibles) {
            // Si no se ha encontrado ningún PDF coincidente, mostrar un mensaje
            echo "<li>No tiene guías asignadas.</li>";
        }

        echo "</ul>";
        closedir($dir);
    } else {
        echo "No se pudo abrir el directorio.";
    }
} else {
    echo "La ruta base no es un directorio válido.";
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Visor de PDF con PDF.js</title>
    <style>
		#overlay {
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background-color: black;
			z-index: 9999;
			opacity: 0.9;
			color: white;
			text-align: center;
			font-size: 24px;
			justify-content: center;
			align-items: center;
		}

		body {
			font-family: Arial, sans-serif;
			margin: 0;
			padding: 0;
			display: flex;
			flex-direction: column;
			align-items: center;
			background-color: #f4f4f9;
		}

		h2 {
			color: #333;
			font-size: 24px;
			margin-top: 20px;
			text-align: center;
		}

		ul {
			padding: 0;
			list-style: none;
		}

		ul li {
			margin: 10px 0;
		}

		a {
			text-decoration: none;
			color: #007BFF;
			font-size: 18px;
			padding: 5px;
		}

		a:hover {
			color: #0056b3;
		}

		#pdf-container {
			width: 100vw;
			height: 100vh;
			display: flex;
			justify-content: center;
			align-items: center;
			overflow: hidden;
			position: relative;
			background-color: #fff;
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
			margin-top: 10px;
		}

		canvas {
			max-width: 100vw;
			max-height: 100vh;
			user-select: none;
			-webkit-user-select: none;
			width: auto;
			height: auto;
		}

		#username {
			font-weight: bold;
			color: #007BFF;
			position: absolute;
			top: 0;
			left: 50%;
			transform: translateX(-50%);
			background: rgba(255, 255, 255, 0.8);
			padding: 5px 10px;
			border-bottom: 1px solid #ccc;
			z-index: 10;
			font-size: 18px;
			width: 100%;
			text-align: center;
		}

		.spinner {
			width: 40px;
			height: 40px;
			border: 4px solid rgba(0, 123, 255, 0.3);
			border-top: 4px solid #007BFF;
			border-radius: 50%;
			animation: spin 1s linear infinite;
			margin-bottom: 10px;
			display: none;
		}

		.spinner.hidden {
			display: none;
		}

		@keyframes spin {
			from {
				transform: rotate(0deg);
			}
			to {
				transform: rotate(360deg);
			}
		}

		#loading {
			display: none;
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			background-color: rgba(255, 255, 255, 0.7);
			display: flex;
			justify-content: center;
			align-items: center;
			font-size: 24px;
			color: #007BFF;
			z-index: 10;
			width: auto;
			height: auto;
			padding: 20px;
			border-radius: 8px;
			box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
		}

		#navigation {
			display: flex;
			justify-content: center;
			margin-top: 20px;
		}

		button {
			padding: 10px 20px;
			font-size: 16px;
			background-color: #007BFF;
			color: white;
			border: none;
			border-radius: 5px;
			margin: 0 10px;
			cursor: pointer;
			transition: background-color 0.3s;
		}

		button:hover {
			background-color: #0056b3;
		}

		#prev-page, #next-page {
			position: absolute;
			display: flex;
			justify-content: center;
			align-items: center;
			top: 50%;
			transform: translateY(-50%);
			padding: 10px 15px;
			font-size: 18px;
			background-color: rgba(0, 123, 255, 0.7);
			color: white;
			border: none;
			border-radius: 8px;
			cursor: pointer;
			transition: background-color 0.3s, transform 0.3s, box-shadow 0.3s;
			box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
			width: 45px;
			height: 45px;
			z-index: 10;
		}

		#prev-page {
			left: 15px; /* Ajusta la posición desde el borde izquierdo del contenedor */
		}

		#next-page {
			right: 15px; /* Ajusta la posición desde el borde derecho del contenedor */
		}


		#prev-page:hover, #next-page:hover {
			background-color: rgba(0, 123, 255, 1);
			transform: translateY(-50%) scale(1.05);
			box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
		}

		button:disabled {
			background-color: rgba(204, 204, 204, 0.7);
			cursor: not-allowed;
			box-shadow: none;
		}

		#prev-page::before {
			content: '←';
		}

		#next-page::before {
			content: '→';
		}

		#page-info {
			margin-top: 10px;
			font-size: 16px;
			color: #333;
			text-align: center;
		}


		/* Responsividad para pantallas pequeñas */
		@media screen and (max-width: 768px) {

			#prev-page, #next-page {
				width: 35px;
				height: 35px;
				font-size: 14px;
			}

			#username {
				font-size: 16px;
			}
		}

		/* Ajuste adicional para pantallas muy pequeñas */
		@media screen and (max-width: 480px) {


			#prev-page, #next-page {
				width: 30px;
				height: 30px;
				font-size: 12px;
			}

			#username {
				font-size: 14px;
			}
		}
	</style>


</head>
<body>

    <!-- Contenedor del PDF -->
    <div id="pdf-container">
        <div id="username"><?php echo htmlspecialchars($username); ?></div>
		<div id="loading">
    		<div class="spinner"></div>
			<span>Escoja una guía...</span>
		</div>
		<!-- Botones de navegación -->
		<div id="navigation">
			<button id="prev-page"></button>
			<button id="next-page"></button>
		</div>
        
        <canvas id="pdf-render"></canvas>
    </div>

    

    <!-- Información de la página -->
    <div id="page-info">
        <p>Página: <span id="page-num"></span> / <span id="page-count"></span></p>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
    <script>
        var pdfDoc = null,
            pageNum = 1,
            pageIsRendering = false,
            pageNumIsPending = null,
            scale = 2,
            canvas = document.getElementById('pdf-render'),
            ctx = canvas.getContext('2d', { willReadFrequently: true });

        // Mostrar el mensaje de "Cargando..."
        function showLoading() {
            document.getElementById('loading').style.display = 'flex';
        }

        // Ocultar el mensaje de "Cargando..."
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        function renderPage(num) {
            pageIsRendering = true;
            pdfDoc.getPage(num).then(function(page) {
                const viewport = page.getViewport({ scale: scale });
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                var renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };

                var renderTask = page.render(renderContext);
                renderTask.promise.then(function() {
                    pageIsRendering = false;
                    hideLoading();

                    if (pageNumIsPending !== null) {
                        renderPage(pageNumIsPending);
                        pageNumIsPending = null;
                    }
                });

                document.getElementById('page-num').textContent = num;
            });
        }

        function queueRenderPage(num) {
            if (pageIsRendering) {
                pageNumIsPending = num;
            } else {
                renderPage(num);
            }
        }

        function showPrevPage() {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;
            queueRenderPage(pageNum);
        }

        function showNextPage() {
            if (pageNum >= pdfDoc.numPages) {
                return;
            }
            pageNum++;
            queueRenderPage(pageNum);
        }
		
		// Mostrar el mensaje de "Cargando..." o "Escoja una guía"
		function showLoading(text, showSpinner = true) {
			const loadingElement = document.getElementById('loading');
			const spinnerElement = loadingElement.querySelector('.spinner');
			const loadingText = loadingElement.querySelector('span');

			// Actualizar el texto del mensaje
			if (loadingText) {
				loadingText.textContent = text;
			}

			// Mostrar u ocultar el spinner según el parámetro
			if (spinnerElement) {
				if (showSpinner) {
					spinnerElement.classList.remove('hidden'); // Mostrar el spinner
				} else {
					spinnerElement.classList.add('hidden'); // Ocultar el spinner
				}
			}

			// Mostrar el contenedor de carga
			loadingElement.style.display = 'flex';
		}



        // Lógica para mostrar el PDF seleccionado
        function mostrarPDF(file) {
            var decodedFile = decodeURIComponent(file);
            var url = 'renderpdf.php?file=' + decodedFile;
            
            pageNum = 1; // Reiniciar a la página 1
			// Cambiar el texto a "Cargando..." cuando se selecciona una guía
        	showLoading("Cargando...");

            // Mostrar el símbolo de carga
            var loading= document.getElementById('loading');
			loading.style.display = 'block';
			
            // Cargar el PDF
            pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) {
                pdfDoc = pdfDoc_;
                document.getElementById('page-count').textContent = pdfDoc.numPages;
                renderPage(pageNum);
            }).catch(function(error) {
                console.error("Error al cargar el PDF: ", error);
                document.getElementById('loading').textContent = "Error al cargar el PDF, por favor intente recargando la página";
            });
        }

        document.getElementById('prev-page').addEventListener('click', showPrevPage);
        document.getElementById('next-page').addEventListener('click', showNextPage);

        // Deshabilitar clic derecho en toda la página
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
    </script>


</body>
</html>


<script>
	document.addEventListener('contextmenu', function(e) {
		e.preventDefault();
	});
</script>

<script>
    function adjustPDFContainer() {
        const container = document.getElementById('pdf-container');
        container.style.width = `${window.innerWidth}px`;
        container.style.height = `${window.innerHeight}px`;
    }

    window.addEventListener('resize', adjustPDFContainer);
    window.addEventListener('orientationchange', adjustPDFContainer);

    // Llamar una vez al cargar
    adjustPDFContainer();
</script>



