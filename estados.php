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
$estado = 'esperando';

if (isset($_SESSION['ip'])) {
    $solicitudes = file_exists($archivo) ? json_decode(file_get_contents($archivo), true) : [];
    $estado = $solicitudes[$_SESSION['ip']]['estado'] ?? 'esperando';
}

echo json_encode(['estado' => $estado]);