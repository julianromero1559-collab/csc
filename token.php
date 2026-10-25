<?php
// Función para obtener la IP real del cliente
function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$archivo = 'solicitudes.json';
$autorizadosFile = 'autorizados.json';

// Cargar las solicitudes
$solicitudes = file_exists($archivo) ? json_decode(file_get_contents($archivo), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip'];
    $accion = $_POST['accion']; // aceptar o rechazar

    if (isset($solicitudes[$ip])) {
        if ($accion === 'aceptar') {
            $solicitudes[$ip]['estado'] = 'aceptado';

            // Agregar IP a autorizados.json
            $autorizados = file_exists($autorizadosFile) ? json_decode(file_get_contents($autorizadosFile), true) : [];
            if (!is_array($autorizados)) $autorizados = [];

            if (!in_array($ip, $autorizados)) {
                $autorizados[] = $ip;
                file_put_contents($autorizadosFile, json_encode($autorizados, JSON_PRETTY_PRINT));
            }

        } elseif ($accion === 'rechazar') {
            $solicitudes[$ip]['estado'] = 'rechazado';
        }

        // Guardar el nuevo estado
        file_put_contents($archivo, json_encode($solicitudes, JSON_PRETTY_PRINT));
    }

    header("Location: autorizador.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Autorizador</title>
    <style>
        body {
            background: #111;
            color: white;
            font-family: monospace;
            padding: 20px;
        }

        .solicitud {
            background: #222;
            padding: 15px;
            margin: 15px 0;
            border: 1px solid #555;
        }

        button {
            padding: 5px 15px;
            margin-right: 10px;
            cursor: pointer;
            font-weight: bold;
        }

        .aceptar {
            background: #28a745;
            color: white;
            border: none;
        }

        .rechazar {
            background: #dc3545;
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    <h1>Solicitudes de ingreso</h1>

    <?php if (empty($solicitudes)): ?>
        <p>No hay solicitudes pendientes.</p>
    <?php else: ?>
        <?php foreach ($solicitudes as $ip => $info): ?>
            <div class="solicitud">
                <strong>Nombre:</strong> <?= htmlspecialchars($info['nombre']) ?><br>
                <strong>IP:</strong> <?= htmlspecialchars($ip) ?><br>
                <strong>Estado:</strong> <?= htmlspecialchars($info['estado']) ?><br><br>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="ip" value="<?= $ip ?>">
                    <input type="hidden" name="accion" value="aceptar">
                    <button class="aceptar">✅ Aceptar</button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="ip" value="<?= $ip ?>">
                    <input type="hidden" name="accion" value="rechazar">
                    <button class="rechazar">❌ Rechazar</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>