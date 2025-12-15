<?php
require_once('lib/nusoap.php');

// Uso DDEV en mi ordenador local porque me resulta más conveniente configurar entornos para cada proyecto
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

$client = new nusoap_client($soap_url, 'wsdl');
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
$generos = array();
$grupos = array();
$genero_seleccionado = '';

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['genero'])) {
    $genero_seleccionado = intval($_POST['genero']);
    
    if ($genero_seleccionado > 0) {
        if (empty($client->endpoint) || !preg_match('/^https?:\/\//', $client->endpoint ?? '')) {
            $client->endpoint = $soap_endpoint;
            $client->forceEndpoint = $soap_endpoint;
        }
        $grupos = $client->call('obtenerGruposPorGenero', array(
            'genero' => $genero_seleccionado
        ));
        
        if ($client->fault || $client->getError()) {
            $error = 'Error';
            $grupos = array();
        } else {
            if (!is_array($grupos)) {
                $grupos = array();
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
    <title>Ver Grupos por Género</title>
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
            max-width: 900px;
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
            margin-bottom: 30px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
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
            margin-top: 10px;
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
        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .mensaje.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background-color: #f5f5f5;
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
        <h1>Ver Grupos por Género</h1>
        
        <?php if ($error): ?>
            <div class="mensaje error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="genero">Seleccione un Género:</label>
                <select id="genero" name="genero" required>
                    <option value="">Seleccione un género</option>
                    <?php foreach ($generos as $genero): ?>
                        <?php 
                            $array = json_decode(json_encode($genero), true);
                        ?>
                        <option value="<?php echo $array['identificador']; ?>"
                                <?php echo ($genero_seleccionado == $array['identificador']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($array['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Buscar Grupos</button>
            </div>
        </form>
        
        <?php if (!empty($grupos)): ?>
            <h2 style="margin-top: 30px; color: #333;">Grupos Encontrados:</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Grupo</th>
                        <th>Género ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grupos as $grupo): ?>
                        <?php 
                            $array = json_decode(json_encode($grupo), true);
                        ?>
                        <tr>
                            <td><?php echo $array['identificador']; ?></td>
                            <td><?php echo htmlspecialchars($array['nombre']); ?></td>
                            <td><?php echo $array['genero']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($genero_seleccionado > 0): ?>
            <div class="mensaje info">No se encontraron grupos para el género seleccionado.</div>
        <?php endif; ?>
        
        <div class="nav-links">
            <a href="index.php">Inicio</a> | 
            <a href="insertar_grupo.php">Insertar Nuevo Grupo</a>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; color: #666; font-size: 14px;">
            &copy; <?php echo date('Y'); ?> - Vadym Volokhov
        </div>
    </div>
</body>
</html>

