<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario de Evaluación Docente</title>
</head>
<body>
    <h2>Formulario de Evaluación Docente</h2>
    <form action="procesar.php" method="post">
        <h3>Datos de Identificación</h3>
        <label>Nombre de la Institución:</label>
        <input type="text" name="institucion" required><br>

        <label>Año de clase observada:</label>
        <input type="text" name="anio_clase" required><br>

        <label>Nombre del Docente:</label>
        <input type="text" name="nombre_docente" required><br>

        <label>Asignatura:</label>
        <input type="text" name="asignatura" required><br>

        <label>Formación Docente:</label>
        <input type="text" name="formacion_docente" required><br>

        <label>Fecha de visita técnica:</label>
        <input type="date" name="fecha_visita" required><br>

        <h3>Evaluación del Proceso de Enseñanza – Aprendizaje</h3>
        <!-- Aquí se pueden agregar más campos según la rúbrica -->

        <input type="submit" value="Generar PDF">
    </form>
</body>
</html>