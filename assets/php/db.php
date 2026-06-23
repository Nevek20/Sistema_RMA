<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'controle_rma');
define('ADMIN_PASS', '1234'); // altere aqui

function getConn(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function registrarLog(mysqli $conn, string $acao, string $entidade, string $detalhe): void {
    $stmt = $conn->prepare("INSERT INTO logs (acao, entidade, detalhe) VALUES (?, ?, ?)");
    if (!$stmt) return;
    $stmt->bind_param('sss', $acao, $entidade, $detalhe);
    $stmt->execute();
    $stmt->close();
}
