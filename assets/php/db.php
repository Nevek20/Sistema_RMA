<?php
defined('RMA_APP') || exit;

$configLocal = __DIR__ . '/../../config.local.php';
if (!is_file($configLocal)) {
    http_response_code(500);
    exit('Configuração não encontrada: copie config.example.php para config.local.php e ajuste os dados.');
}
require_once $configLocal;

function getConn(): mysqli {
    // A partir do PHP 8.1 o mysqli lança exceção em qualquer erro de SQL. O código
    // do sistema trata erro pelo retorno do execute() (ex.: "cliente já existe"),
    // então desligamos as exceções para esse tratamento funcionar.
    mysqli_report(MYSQLI_REPORT_OFF);

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log('Sistema RMA: falha ao conectar no banco: ' . $conn->connect_error);
        http_response_code(500);
        exit('Erro ao conectar no banco de dados.');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// Grava uma ação no histórico. O usuário vem da sessão; no login com falha, quem
// chama informa o nome digitado.
function registrarLog(mysqli $conn, string $acao, string $entidade, string $detalhe, ?string $usuario = null): void {
    $usuario = $usuario ?? ($_SESSION['usuario'] ?? null);
    if ($usuario !== null) $usuario = mb_substr($usuario, 0, 50);

    $stmt = $conn->prepare("INSERT INTO logs (usuario, acao, entidade, detalhe) VALUES (?, ?, ?, ?)");
    if (!$stmt) return;
    $stmt->bind_param('ssss', $usuario, $acao, $entidade, $detalhe);
    $stmt->execute();
    $stmt->close();
}
