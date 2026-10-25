<?php
$id = $_GET['id'] ?? '';
if (!$id) {
    echo json_encode(['error' => 'ID no recibido']);
    exit;
}

try {
    $db = new PDO('sqlite:usuarios.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT estado FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['estado' => $row ? $row['estado'] : '']);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error en base de datos']);
}
?>
