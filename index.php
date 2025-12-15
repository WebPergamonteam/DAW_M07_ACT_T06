<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupos Musicales - Servicio Web SOAP</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 800px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 40px;
            text-align: center;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 2.5em;
        }
        .subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 1.2em;
        }
        .menu {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .menu-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1.2em;
            transition: transform 0.2s, box-shadow 0.2s;
            display: block;
        }
        .menu-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .info {
            margin-top: 40px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            color: #555;
        }
        .info h2 {
            color: #333;
            margin-bottom: 15px;
        }
        .info ul {
            text-align: left;
            display: inline-block;
            margin-top: 10px;
        }
        .info li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎵 Grupos Musicales</h1>
        <p class="subtitle">Servicio Web SOAP con NuSOAP</p>
        
        <div class="menu">
            <a href="insertar_grupo.php" class="menu-item">
                ➕ Insertar Nuevo Grupo
            </a>
            <a href="ver_grupos.php" class="menu-item">
                👁️ Ver Grupos por Género
            </a>
        </div>
        
        <div class="info">
            <h2>Información del Sistema</h2>
            <p>Este sistema permite gestionar grupos musicales clasificados por género mediante un servicio web SOAP.</p>
            <ul>
                <li><strong>Insertar Grupos:</strong> Añade nuevos grupos musicales seleccionando su género</li>
                <li><strong>Ver Grupos:</strong> Consulta los grupos filtrados por género</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; color: #666; font-size: 14px;">
            &copy; <?php echo date('Y'); ?> - Vadym Volokhov
        </div>
    </div>
</body>
</html>

