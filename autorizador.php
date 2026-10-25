<?php
session_start();

// Cerrar sesión si se solicita
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: autorizador.php");
    exit;
}

// Configura tus credenciales aquí
$usuario_valido = 'coste';
$contrasena_valida = 'elmaspro';

// Login simple
if (isset($_POST['login'])) {
    if ($_POST['usuario'] === $usuario_valido && $_POST['contrasena'] === $contrasena_valida) {
        $_SESSION['autorizado'] = true;
    } else {
        $error_login = "Credenciales incorrectas.";
    }
}

// Si no está logueado, mostrar el formulario
if (!isset($_SESSION['autorizado'])) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body {
            background: #111;
            color: white;
            font-family: monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }

        form {
            background: #222;
            padding: 30px;
            border: 1px solid #555;
        }

        input {
            display: block;
            margin-bottom: 15px;
            padding: 10px;
            width: 250px;
            background: #333;
            color: white;
            border: 1px solid #555;
        }

        button {
            padding: 10px;
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            width: 100%;
        }

        .error {
            color: #dc3545;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Acceso autorizado</h2>
        <?php if (isset($error_login)) echo "<div class='error'>$error_login</div>"; ?>
        <input type="text" name="usuario" placeholder="Usuario" required>
        <input type="password" name="contrasena" placeholder="Contraseña" required>
        <button type="submit" name="login">Ingresar</button>
    </form>
</body>
</html>
<?php
exit;
}

// ------------------ PANEL DE AUTORIZADOR -------------------

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $ip = $_POST['ip'];
    $accion = $_POST['accion'];

    if (isset($solicitudes[$ip])) {
        if ($accion === 'aceptar') {
            $solicitudes[$ip]['estado'] = 'aceptado';

            $autorizados = file_exists($autorizadosFile) ? json_decode(file_get_contents($autorizadosFile), true) : [];
            if (!is_array($autorizados)) $autorizados = [];

            if (!in_array($ip, $autorizados)) {
                $autorizados[] = $ip;
                file_put_contents($autorizadosFile, json_encode($autorizados, JSON_PRETTY_PRINT));
            }
        } elseif ($accion === 'rechazar') {
            $solicitudes[$ip]['estado'] = 'rechazado';
        }

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

        .logout {
            background: #ffc107;
            color: black;
            border: none;
            padding: 10px 20px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <form method="POST" style="margin-bottom: 20px;">
        <button type="submit" name="logout" class="logout">🔒 Cerrar sesión</button>
    </form>

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
