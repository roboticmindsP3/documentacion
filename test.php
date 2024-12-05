<?php
require_once(__DIR__ . '/../config.php'); // Ajusta la ruta según la ubicación del script

// Activar la visualización de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtener la instancia de usuario actual
if (isloggedin()) {
    $username = $USER->id;
} else {
    die("Acceso Denegado");
}

// Ruta base donde están almacenados los PDFs
$base_dir = $CFG->documentacionroot . '/mod/guias_2024';

// Obtener el archivo desde la URL
$file = isset($_GET['file']) ? basename($_GET['file']) : 'null';

$file = rawurldecode($file);
// Ruta completa al archivo PDF
$pdf_path = $base_dir . '/' . $file;

// Verificar si el archivo PDF existe
if (!file_exists($pdf_path)) {
    die("El archivo PDF no existe: " . htmlspecialchars($pdf_path));
}

// Encabezado HTML
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de PDF con PDF.js</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        #pdf-container {
            width: 80%;
            height: 80vh;
            border: 1px solid #ccc;
            margin-top: 20px;
            position: relative; /* Añadido para posicionar los elementos correctamente */
        }
        canvas {
            width: 100%;
            height: auto;
            user-select: none; /* Desactivar la selección de texto */
            -webkit-user-select: none;
        }
        #username {
            font-weight: bold;
            color: #007BFF;
            margin: 0; /* Eliminar margen para que se integre mejor */
            position: absolute; /* Cambiado para colocarlo sobre el canvas */
            top: 0; /* Colocado en el margen superior del canvas */
            left: 50%; /* Centrar horizontalmente */
            transform: translateX(-50%); /* Centrar horizontalmente */
            background: rgba(255, 255, 255, 0.8); /* Fondo blanco semitransparente */
            padding: 5px; /* Espaciado interno */
            border-bottom: 1px solid #ccc; /* Línea inferior para separar del canvas */
            z-index: 10; /* Asegurarse de que esté por encima del canvas */
        }
        #navigation {
            margin-top: 1px; /* Separar de la parte superior del contenedor */
            z-index: 10; /* Asegurarse de que estén por encima del canvas */
        }
        button {
            padding: 10px;
            font-size: 14px;
            cursor: pointer;
            margin: 0 5px;
        }
        #loading {
            display: none; /* Ocultar por defecto */
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            color: #007BFF;
        }
    </style>
</head>
<body>
    <!-- Botones de navegación (arriba del PDF) -->
    <div id="navigation">
        <button id="prev-page">Página Anterior</button>
        <button id="next-page">Página Siguiente</button>
        <p>Página: <span id="page-num"></span> / <span id="page-count"></span></p>
    </div>
    <div id="pdf-container">
        <div id="username">' . htmlspecialchars($username) . '</div>
        <div id="loading">Cargando...</div> <!-- Elemento de carga -->
        <canvas id="pdf-render"></canvas>
    </div>
   
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
    <script>
        var url = \'renderpdf.php\'; // Cambiar a la URL correcta
        pdfjsLib.GlobalWorkerOptions.workerSrc = \'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.worker.min.js\';

        let pdfDoc = null,
            pageNum = 1,
            pageIsRendering = false,
            pageNumIsPending = null;

        const scale = 1.5,
              canvas = document.getElementById(\'pdf-render\'),
              ctx = canvas.getContext(\'2d\', { willReadFrequently: true }); // Optimización de canvas

        const renderPage = num => {
            pageIsRendering = true;
            pdfDoc.getPage(num).then(page => {
                const viewport = page.getViewport({ scale });
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const renderContext = {
                    canvasContext: ctx,
                    viewport
                };

                const renderTask = page.render(renderContext);

                renderTask.promise.then(() => {
                    pageIsRendering = false;
                    document.getElementById(\'loading\').style.display = \'none\'; // Ocultar el símbolo de carga
                    if (pageNumIsPending !== null) {
                        renderPage(pageNumIsPending);
                        pageNumIsPending = null;
                    }
                });

                document.getElementById(\'page-num\').textContent = num;
            });
        };

        const queueRenderPage = num => {
            if (pageIsRendering) {
                pageNumIsPending = num;
            } else {
                renderPage(num);
            }
        };

        const showPrevPage = () => {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;
            queueRenderPage(pageNum);
        };

        const showNextPage = () => {
            if (pageNum >= pdfDoc.numPages) {
                return;
            }
            pageNum++;
            queueRenderPage(pageNum);
        };

        // Mostrar el símbolo de carga
        document.getElementById(\'loading\').style.display = \'block\'; 

        pdfjsLib.getDocument(url).promise.then(pdfDoc_ => {
            pdfDoc = pdfDoc_;
            document.getElementById(\'page-count\').textContent = pdfDoc.numPages;
            renderPage(pageNum);
        }).catch(error => {
            console.error("Error al cargar el PDF: ", error);
            document.getElementById(\'loading\').textContent = "Error al cargar el PDF"; // Mensaje de error
        });

        document.getElementById(\'prev-page\').addEventListener(\'click\', showPrevPage);
        document.getElementById(\'next-page\').addEventListener(\'click\', showNextPage);

        canvas.addEventListener(\'contextmenu\', function(e) {
            e.preventDefault();
        });
    </script>
</body>
</html>';
?>
