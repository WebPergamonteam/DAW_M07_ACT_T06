<?php
// Uso DDEV en mi ordenador local porque me resulta más conveniente configurar entornos para cada proyecto
$is_ddev = getenv('DDEV_PRIMARY_URL') !== false || (isset($_ENV['DDEV_PRIMARY_URL']) && $_ENV['DDEV_PRIMARY_URL'] !== '');

if ($is_ddev) {
    $ddev_db_host = getenv('DDEV_DB_HOST');
    if (($ddev_db_host === false || $ddev_db_host === '') && isset($_ENV['DDEV_DB_HOST']) && $_ENV['DDEV_DB_HOST'] !== '') {
        $ddev_db_host = $_ENV['DDEV_DB_HOST'];
    }
    if ($ddev_db_host && $ddev_db_host !== false && $ddev_db_host !== '') {
        define('DB_HOST', $ddev_db_host);
    } else {
        define('DB_HOST', 'db');
    }
    
    $ddev_user = getenv('DDEV_DB_USER');
    if (($ddev_user === false || $ddev_user === '') && isset($_ENV['DDEV_DB_USER']) && $_ENV['DDEV_DB_USER'] !== '') {
        $ddev_user = $_ENV['DDEV_DB_USER'];
    }
    define('DB_USER', $ddev_user ?: 'db');
    
    $ddev_pass = getenv('DDEV_DB_PASSWORD');
    if (($ddev_pass === false || $ddev_pass === '') && isset($_ENV['DDEV_DB_PASSWORD']) && $_ENV['DDEV_DB_PASSWORD'] !== '') {
        $ddev_pass = $_ENV['DDEV_DB_PASSWORD'];
    }
    define('DB_PASS', $ddev_pass ?: 'db');
    define('DB_NAME', 'db');
} else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'admin');
    define('DB_PASS', '');
    define('DB_NAME', 'db');
}

// Función para obtener conexión a la base de datos
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            return null;
        }
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        return null;
    }
}
?>

