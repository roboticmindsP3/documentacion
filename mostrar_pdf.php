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
        'shortname' => '%pr-%'
    ];

    $cursos_docente = $DB->get_records_sql($sql, $params);
    $guias_totales = array();
    $a = ["sallesierra_2024", "uesjb_2024", "uejae_2024", "uesjb_2024", "uemsc_2024", "uefsjsc_2024", "ueflsa_2024", "flst_2024", "ueslb_2024", "uehms_2024", "uejbpa_2024", "uejlm_2024", "psr_2024", "uepc_2024", "ueffc_2024", "uemsq_2024", "uesjq_2024", "cah_2024"];

    foreach ($cursos_docente as $curso) {
        // Dividir el shortname por el guión (-)
        $partes = explode('-', $curso->course_shortname);
        $cod_curso = isset($partes[1]) ? $partes[1] : ''; 
        if (in_array($cod_curso, $a)) {
            $cod_curso = 'sallesierra_2024';
        }
        $guias_permitidas = $segundabd->get_records_sql('SELECT guias_permitidas FROM rm_colegios_guias_construc WHERE codigo_colegio = ?', array($cod_curso));
        foreach ($guias_permitidas as $guia) {
            $json_decoded = json_decode($guia->guias_permitidas, true); 
            if (is_array($json_decoded)) {
                $guias_totales = array_merge($guias_totales, $json_decoded);
            } else {
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
    $base_dir = $CFG->documentacionroot . '/doc_doce/mod/guias_2024';

    if (is_dir($base_dir)) {
        if ($dir = opendir($base_dir)) {
            echo "<h2>Lista de Guías de Construcción disponibles</h2>";
            echo "<ul>";

            $pdfs_disponibles = false;

            while (($file = readdir($dir)) !== false) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                    $partes = explode('_', $file, 2);
                    $codigo_pdf = $partes[0]; 
                    $nombre_visible = isset($partes[1]) ? $partes[1] : $file;
                    $nombre_visible = preg_replace('/\.pdf$/', '', $nombre_visible);
                    $nombre_visible = str_replace(['_', '-'], ' ', $nombre_visible);

                    if (array_key_exists($codigo_pdf, $guias_totales)) {
                        $nombre_sin_ext = pathinfo($file, PATHINFO_FILENAME);
                        echo "<li><a href='#' onclick='mostrarImagenes(\"" . htmlspecialchars($nombre_sin_ext) . "\")'>" . htmlspecialchars($nombre_visible) . "</a></li>";
                        $pdfs_disponibles = true; 
                    }
                }
            }

            if (!$pdfs_disponibles) {
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
    <!-- Ajuste del viewport sin maximum-scale ni user-scalable -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Imágenes</title>
    <style>
        /* Tu CSS original + ajustes para imágenes y navegación overlay */
        
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

        /* Contenedor de las imágenes */
        #images-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .imagen-slide {
            position: absolute;
            top:0;
            left:0;
            width:100%;
            height:100%;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .imagen-slide img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain; /* Asegura que la imagen se ajuste sin deformarse */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
            from {transform: rotate(0deg);}
            to {transform: rotate(360deg);}
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
            box-shadow: 0px 4px 8px rgba(0,0,0,0.2);
        }

        /* Navegación overlay estilo flechas a los lados */
        #navigation {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
            padding: 0 20px;
            box-sizing: border-box;
            z-index: 20; /* por encima de la imagen */
        }

        #prev-image, #next-image {
            background-color: rgba(0,123,255,0.7);
            border: none;
            color: white;
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            transition: background-color 0.3s, transform 0.3s, box-shadow 0.3s;
            width: 45px;
            height: 45px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #prev-image:hover, #next-image:hover {
            background-color: rgba(0, 123, 255, 1);
            transform: scale(1.1);
            box-shadow: 0px 4px 8px rgba(0,0,0,0.3);
        }

        #prev-image::before {
            content: '←';
        }

        #next-image::before {
            content: '→';
        }

        #page-indicator {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 123, 255, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 16px;
            z-index: 20;
        }

        #page-info {
            margin-top: 10px;
            font-size: 16px;
            color: #333;
            text-align: center;
        }

        /* Responsividad para pantallas pequeñas */
        @media screen and (max-width: 768px) {
            #prev-image, #next-image {
                width: 35px;
                height: 35px;
                font-size: 20px;
            }
            #username {
                font-size: 16px;
            }
        }

        /* Ajuste adicional para pantallas muy pequeñas */
        @media screen and (max-width: 480px) {
            #prev-image, #next-image {
                width: 30px;
                height: 30px;
                font-size: 18px;
            }
            #username {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Contenedor de Imágenes -->
    <div id="pdf-container">
        <div id="username"><?php echo htmlspecialchars($username); ?></div>
        <div id="loading">
            <div class="spinner"></div>
            <span>Escoja una guía...</span>
        </div>
        <div id="navigation" style="display: none;">
            <button id="prev-image"></button>
            <button id="next-image"></button>
        </div>
        <div id="images-container"></div>
        <div id="page-indicator">Pag 01</div>
    </div>

    <div id="page-info">
        <p>Página: <span id="page-num"></span> / <span id="page-count"></span></p>
    </div>

    <script>
        var imagenActual = 0;
        var imagenes = []; 
        var totalSlides = 0;

        function mostrarImagenes(folderName) {
            showLoading("Cargando imágenes...");

            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'list_images.php?folder=' + encodeURIComponent(folderName), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    imagenes = JSON.parse(xhr.responseText);
                    totalSlides = imagenes.length;
                    imagenActual = 0;

                    var imagesContainer = document.getElementById('images-container');
                    imagesContainer.innerHTML = '';

                    if (totalSlides > 0) {
                        imagenes.forEach(function(imgUrl, index) {
                            var imgDiv = document.createElement('div');
                            imgDiv.className = 'imagen-slide';
                            imgDiv.style.display = (index === 0) ? 'flex' : 'none'; // Muestra la primera imagen, escondemos el resto

                            var img = document.createElement('img');
                            img.src = imgUrl;
                            img.alt = 'Imagen ' + (index + 1);
                            imgDiv.appendChild(img);
                            imagesContainer.appendChild(imgDiv);
                        });

                        actualizarIndicador();
                        document.getElementById('navigation').style.display = 'flex';
                    } else {
                        imagesContainer.innerHTML = '<p>No hay imágenes disponibles.</p>';
                        document.getElementById('navigation').style.display = 'none';
                    }

                    hideLoading();
                }
            };
            xhr.send();
        }

        function showPrevImage() {
            if (totalSlides === 0) return;
            imagenActual--;
            if (imagenActual < 0) {
                imagenActual = totalSlides - 1;
            }
            actualizarImagenes();
        }

        function showNextImage() {
            if (totalSlides === 0) return;
            imagenActual++;
            if (imagenActual >= totalSlides) {
                imagenActual = 0;
            }
            actualizarImagenes();
        }

        function actualizarImagenes() {
            var slides = document.getElementsByClassName('imagen-slide');
            for (var i = 0; i < slides.length; i++) {
                slides[i].style.display = 'none';
            }

            if (slides[imagenActual]) {
                slides[imagenActual].style.display = 'flex';
            }

            actualizarIndicador();
        }

        function actualizarIndicador() {
            var pageIndicator = document.getElementById('page-indicator');
            if (pageIndicator && totalSlides > 0) {
                var pageNumber = imagenActual + 1;
                var pageText = "Pag " + (pageNumber < 10 ? "0" + pageNumber : pageNumber);
                pageIndicator.textContent = pageText;
            }
        }

        document.getElementById('prev-image').addEventListener('click', showPrevImage);
        document.getElementById('next-image').addEventListener('click', showNextImage);

        function showLoading(text = "Cargando...", showSpinner = true) {
            var loadingElement = document.getElementById('loading');
            var spinnerElement = loadingElement.querySelector('.spinner');
            var loadingText = loadingElement.querySelector('span');

            if (loadingText) {
                loadingText.textContent = text;
            }

            if (spinnerElement) {
                if (showSpinner) {
                    spinnerElement.classList.remove('hidden');
                } else {
                    spinnerElement.classList.add('hidden');
                }
            }

            loadingElement.style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        window.onload = function() {
            var pageIndicator = document.getElementById('page-indicator');
            if (pageIndicator) {
                pageIndicator.textContent = 'Pag 00';
            }
        };

        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        function adjustPDFContainer() {
            var container = document.getElementById('pdf-container');
            container.style.width = window.innerWidth + 'px';
            container.style.height = (window.innerHeight) + 'px';
        }

        window.addEventListener('resize', adjustPDFContainer);
        window.addEventListener('orientationchange', adjustPDFContainer);
        adjustPDFContainer();
    </script>
</body>
</html>
