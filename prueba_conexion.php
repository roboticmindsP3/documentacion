<?php
	require_once(__DIR__ . '/../conbd2.php');
	require_once($CFG->libdir . '/dml/mysqli_native_moodle_database.php'); 
// Asegúrate de que el usuario esté autenticado
    require_once(__DIR__ . '/../config.php');
	

    if (isloggedin()) {
        $username = $USER->username;
    } else {
        $username = 'Invitado';
    }

	try {
		// Crear nueva instancia de la base de datos
		$segundabd = new mysqli_native_moodle_database();

		// Conectar a la segunda base de datos
		$segundabd->connect($CFGS->dbhost, $CFGS->dbuser, $CFGS->dbpass, $CFGS->dbname, $CFGS->dboptions['dbcollation']);

		echo "Conexión exitosa a la segunda base de datos";
	} catch (Exception $e) {
		echo "Error conectando a la segunda base de datos: " . $e->getMessage();
	}