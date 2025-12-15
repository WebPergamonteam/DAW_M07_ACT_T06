<?php
require_once('lib/nusoap.php');

if (getenv('DDEV_PRIMARY_URL')) {
    $base_url = getenv('DDEV_PRIMARY_URL');
    if (strpos($base_url, 'https://') === false && strpos($base_url, 'http://') === false) {
        $base_url = 'https://' . $base_url;
    }
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $base_url = $protocol . '://' . $host;
}

$base_url = rtrim($base_url, '/');
$soap_url = $base_url . '/servidor_soap.php?wsdl';
$soap_endpoint = $base_url . '/servidor_soap.php';

if (!preg_match('/^https?:\/\//', $soap_url)) {
    die('Error: ' . htmlspecialchars($soap_url));
}
if (!preg_match('/^https?:\/\//', $soap_endpoint)) {
    die('Error: ' . htmlspecialchars($soap_endpoint));
}

// Crear cliente SOAP con WSDL
// Usar 'wsdl' como string para indicar que es un WSDL
$client = new nusoap_client($soap_url, 'wsdl');

// Establecer el endpoint explícitamente ANTES de cualquier llamada
// Esto fuerza a NuSOAP a usar nuestro endpoint en lugar del que viene del WSDL
$client->forceEndpoint = $soap_endpoint;
$client->endpoint = $soap_endpoint;

if (defined('CURLOPT_HTTP_VERSION') && defined('CURL_HTTP_VERSION_1_1')) {
    $client->setCurlOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
}
if (defined('CURLOPT_FORBID_REUSE')) {
    $client->setCurlOption(CURLOPT_FORBID_REUSE, 1);
}
if (defined('CURLOPT_FRESH_CONNECT')) {
    $client->setCurlOption(CURLOPT_FRESH_CONNECT, 1);
}

if ($client->endpointType == 'wsdl' && is_null($client->wsdl)) {
    $client->loadWSDL();
    if (empty($client->endpoint) || !preg_match('/^https?:\/\//', $client->endpoint ?? '')) {
        $client->endpoint = $soap_endpoint;
        $client->forceEndpoint = $soap_endpoint;
    }
    if (isset($client->operations) && is_array($client->operations)) {
        foreach ($client->operations as $opName => &$opData) {
            if (isset($opData['endpoint']) && !preg_match('/^https?:\/\//', $opData['endpoint'])) {
                $opData['endpoint'] = $soap_endpoint;
            }
        }
    }
}

$error = '';
$mensaje = '';
$generos = array();

if (empty($client->endpoint) || !preg_match('/^https?:\/\//', $client->endpoint ?? '')) {
    $client->endpoint = $soap_endpoint;
    $client->forceEndpoint = $soap_endpoint;
}

if ($client->fault) {
    $error = 'Error de conexión';
} else {
    $err = $client->getError();
    if ($err) {
        $error = 'Error';
    } else {
        if (empty($client->endpoint) || !preg_match('/^https?:\/\//', $client->endpoint ?? '')) {
            $client->endpoint = $soap_endpoint;
            $client->forceEndpoint = $soap_endpoint;
        }
        $generos = $client->call('obtenerGeneros', array());
        if ($client->fault || $client->getError()) {
            $generos = array();
        } else {
            if (!is_array($generos)) {
                $generos = array();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['genero'])) {
    $nombre = trim($_POST['nombre']);
    $genero = intval($_POST['genero']);
    
    if (empty($nombre)) {
        $error = 'El nombre del grupo es obligatorio';
    } else {
        if (empty($client->endpoint) || !preg_match('/^https?:\/\//', $client->endpoint ?? '')) {
            $client->endpoint = $soap_endpoint;
            $client->forceEndpoint = $soap_endpoint;
        }
        $result = $client->call('insertarGrupo', array(
            'nombre' => $nombre,
            'genero' => $genero
        ));
        
        if ($client->fault || $client->getError()) {
            $error = 'Error al insertar';
        } else {
            if (is_string($result) && $result === "Todo OK!") {
                $mensaje = "Grupo insertado correctamente";
                $_POST = array();
            } else {
                $error = is_string($result) ? $result : 'Error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insertar Grupo Musical</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        input[type="text"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .mensaje {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .mensaje.exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .nav-links {
            text-align: center;
            margin-top: 20px;
        }
        .nav-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Insertar Nuevo Grupo Musical</h1>
        
        <?php if ($mensaje): ?>
            <div class="mensaje exito"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre del Grupo:</label>
                <input type="text" id="nombre" name="nombre" required 
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="genero">Género:</label>
                <select id="genero" name="genero" required>
                    <option value="">Seleccione un género</option>
                    <?php foreach ($generos as $genero): ?>
                        <?php 
                            $array = json_decode(json_encode($genero), true);
                        ?>
                        <option value="<?php echo $array['identificador']; ?>"
                                <?php echo (isset($_POST['genero']) && $_POST['genero'] == $array['identificador']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($array['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn">Insertar Grupo</button>
        </form>
        
        <div class="nav-links">
            <a href="index.php">Inicio</a> | 
            <a href="ver_grupos.php">Ver Grupos por Género</a>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; color: #666; font-size: 14px;">
            &copy; <?php echo date('Y'); ?> - Vadym Volokhov
        </div>
    </div>
</body>
</html>

