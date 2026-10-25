<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método no permitido";
    exit;
}

$id = $_POST['id'] ?? null;
$estado = $_POST['estado'] ?? null;

if (!$id || !$estado) {
    echo "ID o estado no proporcionado";
    exit;
}

try {
    $db = new PDO('sqlite:usuarios.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);

    header("Location: panel.php");
    exit;
} catch (PDOException $e) {
    echo "Error al actualizar: " . $e->getMessage();
}
?>
