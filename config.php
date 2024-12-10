<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'admin_';
$CFG->dbuser    = 'jeff';
$CFG->dbpass    = '1?I&d2Ckh3fh';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_general_ci',
);

$CFG->wwwroot   = 'https://affectionate-brahmagupta.74-208-19-154.plesk.page/moodle';
$CFG->dataroot  = '/var/www/vhosts/affectionate-brahmagupta.74-208-19-154.plesk.page/moodledata';
$CFG->documentacionroot  = '/var/www/vhosts/affectionate-brahmagupta.74-208-19-154.plesk.page';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
