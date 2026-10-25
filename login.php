<?php
session_start();

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

$ip = getUserIP();
$archivo = 'solicitudes.json';

// Cargar solicitudes existentes
$solicitudes = file_exists($archivo) ? json_decode(file_get_contents($archivo), true) : [];

// Si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = strtolower(trim($_POST['nombre'] ?? ''));
    $solicitudes[$ip] = [
        'nombre' => $nombre,
        'ip' => $ip,
        'estado' => 'esperando'
    ];
    file_put_contents($archivo, json_encode($solicitudes, JSON_PRETTY_PRINT));
    $_SESSION['ip'] = $ip;
    exit;
}

// Validar estado si la IP ya está en sesión y solicitudes
if (isset($_SESSION['ip']) && isset($solicitudes[$_SESSION['ip']])) {
    $estado = $solicitudes[$_SESSION['ip']]['estado'] ?? 'esperando';

    if ($estado === 'aceptado') {
        // Agregar a autorizados
        $autorizados = file_exists('autorizados.json') ? json_decode(file_get_contents('autorizados.json'), true) : [];
        if (!in_array($ip, $autorizados)) {
            $autorizados[] = $ip;
            file_put_contents('autorizados.json', json_encode($autorizados, JSON_PRETTY_PRINT));
        }

        // Eliminar solicitud y redirigir
        unset($solicitudes[$_SESSION['ip']]);
        file_put_contents($archivo, json_encode($solicitudes, JSON_PRETTY_PRINT));
        header("Location: panel.php");
        exit;
    }

    if ($estado === 'rechazado') {
        unset($solicitudes[$_SESSION['ip']]);
        file_put_contents($archivo, json_encode($solicitudes, JSON_PRETTY_PRINT));
        session_destroy();
        header("Location: https://www.google.com");
        exit;
    }
}

// Si la IP no está registrada en solicitudes, limpiar sesión
if (!isset($solicitudes[$ip])) {
    unset($_SESSION['ip']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consola de acceso</title>
    <style>
        body {
            background: url('https://d2yoo3qu6vrk5d.cloudfront.net/images/20250329185615/diseno-sin-titulo-2025-03-29t185421-085.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #ffffffff;
            font-family: "Courier New", monospace;
            font-size: 16px;
            padding: 20px;
        }
        #formulario {
            margin-top: 20px;
        }
        input {
            background: black;
            border: none;
            color: #0f0;
            font-size: 16px;
            font-family: monospace;
            outline: none;
        }
        ::placeholder {
            color: #0f0;
        }
        .imagen {
            position: fixed;
            bottom: 0;
            right: 0;
            opacity: 0.08;
            width: 400px;
            z-index: -1;
        }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['ip'])): ?>
    <form method="POST" id="formulario">
        Identifícate: <span style="color: yellow">(Ingresa en minúscula. Un solo nombre)</span><br>
        <input type="text" name="nombre" id="nombre" autofocus required>
        <input type="submit" style="display:none">
    </form>
    <div id="mensaje"></div>
<?php else: ?>
    <div id="autorizacion">
        <p>Analizando identidad...</p>
        <p>(<?= htmlspecialchars($solicitudes[$_SESSION['ip']]['nombre'] ?? '...'); ?>...), ¿Eres tú?</p>
        <p>háblame al interno ratero pa darte ingreso</p>
        <p>Esperando autorización del núcleo remoto</p>
    </div>
<?php endif; ?>

<img src="4915687338852593202.jpg" class="imagen">

<script>
document.getElementById('formulario')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const nombre = document.getElementById('nombre').value.trim().toLowerCase();

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'nombre=' + encodeURIComponent(nombre)
    }).then(() => {
        document.getElementById('formulario').style.display = 'none';
        document.getElementById('mensaje').innerHTML = `
            <p>Analizando identidad...</p>
            <p>(${nombre}...), ¿Eres tú?</p>
            <p>háblame al interno ratero pa darte ingreso</p>
            <p>Esperando autorización del núcleo remoto</p>
        `;
    });
});

setInterval(() => {
    fetch('estados.php')
        .then(res => res.json())
        .then(data => {
            if (data.estado === 'aceptado') {
                location.reload(); // ✅ Así index.php ejecuta el código PHP que mueve la IP a autorizados.json y redirige correctamente
            } else if (data.estado === 'rechazado') {
                window.location.href = 'https://www.google.com';
            }
        });
}, 3000);

</script>

</body>
</html>
