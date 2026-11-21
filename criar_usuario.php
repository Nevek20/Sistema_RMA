<?php
// Cria um usuário, ou troca a senha e o perfil de um que já existe.
// Roda só pelo terminal, dentro da pasta do projeto:
//
//   C:\xampp\php\php.exe criar_usuario.php <usuario> <admin|operador>
//
// A senha é pedida depois, para não ficar salva no histórico do terminal.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('RMA_APP', true);
require_once __DIR__ . '/assets/php/db.php';

$usuario = trim($argv[1] ?? '');
$perfil  = $argv[2] ?? '';

if ($usuario === '' || mb_strlen($usuario) > 50 || !in_array($perfil, ['admin', 'operador'], true)) {
    fwrite(STDERR, "Uso: php criar_usuario.php <usuario> <admin|operador>\n");
    exit(1);
}

echo "Senha para $usuario (mínimo 8 caracteres): ";
$senha = rtrim((string)fgets(STDIN), "\r\n");
echo "Confirme a senha: ";
$confirmacao = rtrim((string)fgets(STDIN), "\r\n");

if (mb_strlen($senha) < 8) {
    fwrite(STDERR, "A senha precisa ter pelo menos 8 caracteres.\n");
    exit(1);
}
if ($senha !== $confirmacao) {
    fwrite(STDERR, "As senhas não conferem.\n");
    exit(1);
}

$hash = password_hash($senha, PASSWORD_DEFAULT);

$conn = getConn();
$stmt = $conn->prepare(
    "INSERT INTO usuarios (usuario, senha_hash, perfil) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE senha_hash = VALUES(senha_hash), perfil = VALUES(perfil)"
);
$stmt->bind_param('sss', $usuario, $hash, $perfil);
if (!$stmt->execute()) {
    fwrite(STDERR, "Erro ao salvar: " . $stmt->error . "\n");
    exit(1);
}

echo $stmt->affected_rows === 1
    ? "Usuário $usuario criado como $perfil.\n"
    : "Usuário $usuario atualizado ($perfil, senha nova).\n";

registrarLog($conn, 'USUARIO', 'usuário', "Criado/atualizado pelo terminal: $usuario ($perfil)", 'terminal');
$stmt->close();
$conn->close();
