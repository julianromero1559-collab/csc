<?php
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["userId"])) {
    http_response_code(400);
    exit("Falta userId");
}

$userId = $data["userId"];

try {
    $db = new PDO('sqlite:usuarios.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("UPDATE usuarios SET ultima_actividad = ? WHERE id = ?");
    $stmt->execute([time(), $userId]);

    echo "Ping recibido";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?>
