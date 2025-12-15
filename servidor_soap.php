<?php
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);


ob_start();

require_once('lib/nusoap.php');
require_once('config.php');

// Determinar la URL base del servidor
if (getenv('DDEV_PRIMARY_URL')) {
    $base_url = getenv('DDEV_PRIMARY_URL');
    // Asegurar que siempre tenga un esquema válido
    if (strpos($base_url, 'https://') === false && strpos($base_url, 'http://') === false) {
        $base_url = 'https://' . $base_url;
    }
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $base_url = $protocol . '://' . $host;
}

// Asegurar que la URL esté bien formada
$base_url = rtrim($base_url, '/');
$server_url = $base_url . $_SERVER['SCRIPT_NAME'];

// Validar que la URL tenga esquema válido
if (!preg_match('/^https?:\/\//', $server_url)) {
    die('Error: URL del servidor SOAP no válida: ' . htmlspecialchars($server_url));
}

// Crear instancia del servidor SOAP
$server = new soap_server();
$namespace = 'urn:GruposMusicales';
$server->configureWSDL('GruposMusicales', $namespace, $server_url);
$server->wsdl->schemaTargetNamespace = $namespace;

// Asegurar que el endpoint en el WSDL tenga esquema válido
if (isset($server->wsdl->endpoint) && !preg_match('/^https?:\/\//', $server->wsdl->endpoint)) {
    $server->wsdl->endpoint = $server_url;
}

// Asegurar que el port tenga un location válido con esquema
$portName = 'GruposMusicalesPort';
if (isset($server->wsdl->ports[$portName])) {
    if (!isset($server->wsdl->ports[$portName]['location']) || 
        !preg_match('/^https?:\/\//', $server->wsdl->ports[$portName]['location'])) {
        $server->wsdl->ports[$portName]['location'] = $server_url;
    }
} else {
    // Crear el port si no existe
    $server->wsdl->ports[$portName] = array(
        'binding' => 'GruposMusicalesBinding',
        'location' => $server_url,
        'bindingType' => 'http://schemas.xmlsoap.org/wsdl/soap/'
    );
}

// Asegurar que el binding tenga operations inicializado ANTES de registrar funciones
$bindingName = 'GruposMusicalesBinding';
if (isset($server->wsdl->bindings[$bindingName])) {
    if (!isset($server->wsdl->bindings[$bindingName]['operations'])) {
        $server->wsdl->bindings[$bindingName]['operations'] = array();
    }
} else {
    // Crear el binding si no existe
    $server->wsdl->bindings[$bindingName] = array(
        'name' => $bindingName,
        'portType' => 'GruposMusicalesPortType',
        'style' => 'rpc',
        'transport' => 'http://schemas.xmlsoap.org/soap/http',
        'operations' => array()
    );
}

// Definir tipos complejos
$server->wsdl->addComplexType(
    'Genero',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'identificador' => array('name' => 'identificador', 'type' => 'xsd:int'),
        'nombre' => array('name' => 'nombre', 'type' => 'xsd:string')
    )
);

$server->wsdl->addComplexType(
    'ArrayGeneros',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Genero[]')
    ),
    'tns:Genero'
);

$server->wsdl->addComplexType(
    'Grupo',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'identificador' => array('name' => 'identificador', 'type' => 'xsd:int'),
        'nombre' => array('name' => 'nombre', 'type' => 'xsd:string'),
        'genero' => array('name' => 'genero', 'type' => 'xsd:int')
    )
);

$server->wsdl->addComplexType(
    'ArrayGrupos',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Grupo[]')
    ),
    'tns:Grupo'
);

// Ahora registrar las funciones que usan los tipos complejos
// Registrar función: insertarGrupo
$server->register(
    'insertarGrupo',
    array('nombre' => 'xsd:string', 'genero' => 'xsd:int'),
    array('return' => 'xsd:string'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Inserta un nuevo grupo musical en la base de datos'
);

// Registrar función: obtenerGeneros
$server->register(
    'obtenerGeneros',
    array(),
    array('return' => 'tns:ArrayGeneros'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Devuelve un array con todos los géneros disponibles'
);

// Registrar función: obtenerGruposPorGenero
$server->register(
    'obtenerGruposPorGenero',
    array('genero' => 'xsd:int'),
    array('return' => 'tns:ArrayGrupos'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Devuelve un array con todos los grupos de un género específico'
);

// Asegurar que todos los bindings tengan endpoint válido después de registrar las funciones
// Esto se hará después de registrar todas las funciones, pero lo preparamos aquí
$fixEndpoints = function() use ($server, $server_url) {
    if (isset($server->wsdl->bindings)) {
        foreach ($server->wsdl->bindings as $bindingName => &$binding) {
            if (isset($binding['operations'])) {
                foreach ($binding['operations'] as $opName => &$opData) {
                    if (isset($opData['endpoint']) && !preg_match('/^https?:\/\//', $opData['endpoint'])) {
                        $opData['endpoint'] = $server_url;
                    } elseif (!isset($opData['endpoint'])) {
                        $opData['endpoint'] = $server_url;
                    }
                }
            }
            if (!isset($binding['endpoint']) || !preg_match('/^https?:\/\//', $binding['endpoint'] ?? '')) {
                $binding['endpoint'] = $server_url;
            }
        }
    }
    
    // Asegurar que el port tenga location válido
    $portName = 'GruposMusicalesPort';
    if (isset($server->wsdl->ports[$portName])) {
        if (!isset($server->wsdl->ports[$portName]['location']) || 
            !preg_match('/^https?:\/\//', $server->wsdl->ports[$portName]['location'])) {
            $server->wsdl->ports[$portName]['location'] = $server_url;
        }
    }
};

/**
 * Función para insertar un nuevo grupo musical
 * @param string $nombre Nombre del grupo
 * @param int $genero ID del género
 * @return array Array con resultado (boolean) y mensaje (string)
 */
function insertarGrupo($nombre, $genero) {
    $conn = getDBConnection();
    
    $query = "INSERT INTO grupo (nombre, genero) VALUES ('$nombre', $genero)";
    $result = $conn->query($query);
    
    $conn->close();
    
    if ($result) {
        return "Todo OK!";
    } else {
        return "Error al insertar";
    }
}

/**
 * Función para obtener todos los géneros
 * @return array Array de géneros con identificador y nombre
 */
function obtenerGeneros() {
    try {
        $conn = getDBConnection();
        
        if (!$conn) {
            return array();
        }
        
        $result = $conn->query("SELECT identificador, nombre FROM genero ORDER BY nombre");
        $generos = array();
        
        if ($result === false) {
            $conn->close();
            return array();
        }
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (isset($row['identificador']) && isset($row['nombre'])) {
                    $generos[] = array(
                        'identificador' => (int)$row['identificador'],
                        'nombre' => (string)$row['nombre']
                    );
                }
            }
        }
        
        $conn->close();
        return $generos;
    } catch (Exception $e) {
        return array();
    }
}

/**
 * Función para obtener grupos por género
 * @param int $genero ID del género
 * @return array Array de grupos del género especificado
 */
function obtenerGruposPorGenero($genero) {
    $misGrupos = array();
    
    $conn = getDBConnection();
    
    $query = "SELECT identificador, nombre, genero FROM grupo WHERE genero = $genero ORDER BY nombre";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($grupo = $result->fetch_assoc()) {
            $misGrupos[] = $grupo;
        }
    }
    
    $conn->close();
    return $misGrupos;
}

// Asegurar que el binding tenga todos los campos necesarios
$bindingName = 'GruposMusicalesBinding';
if (isset($server->wsdl->bindings[$bindingName])) {
    if (!isset($server->wsdl->bindings[$bindingName]['portType'])) {
        $server->wsdl->bindings[$bindingName]['portType'] = 'GruposMusicalesPortType';
    }
    if (!isset($server->wsdl->bindings[$bindingName]['transport'])) {
        $server->wsdl->bindings[$bindingName]['transport'] = 'http://schemas.xmlsoap.org/soap/http';
    }
    if (!isset($server->wsdl->bindings[$bindingName]['style'])) {
        $server->wsdl->bindings[$bindingName]['style'] = 'rpc';
    }
    // Asegurar que operations esté inicializado
    if (!isset($server->wsdl->bindings[$bindingName]['operations'])) {
        $server->wsdl->bindings[$bindingName]['operations'] = array();
    }
} else {
    // Crear el binding si no existe
    $server->wsdl->bindings[$bindingName] = array(
        'name' => $bindingName,
        'portType' => 'GruposMusicalesPortType',
        'style' => 'rpc',
        'transport' => 'http://schemas.xmlsoap.org/soap/http',
        'operations' => array()
    );
}

// Asegurar que todos los endpoints tengan esquema válido antes de procesar la petición
if (isset($fixEndpoints)) {
    $fixEndpoints();
}

// Asegurar que todas las operaciones en el binding tengan endpoint válido
$bindingName = 'GruposMusicalesBinding';
if (isset($server->wsdl->bindings[$bindingName]['operations'])) {
    foreach ($server->wsdl->bindings[$bindingName]['operations'] as $opName => &$opData) {
        if (isset($opData['endpoint']) && !preg_match('/^https?:\/\//', $opData['endpoint'])) {
            $opData['endpoint'] = $server_url;
        } elseif (!isset($opData['endpoint'])) {
            $opData['endpoint'] = $server_url;
        }
    }
}

// Procesar la petición SOAP
// Usar file_get_contents para compatibilidad con PHP 7.0+
ob_end_clean(); // End and discard output buffer - SOAP server will output directly
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');
$server->service($HTTP_RAW_POST_DATA);
// The service() method handles all output and sends headers, so we just exit cleanly
exit();
