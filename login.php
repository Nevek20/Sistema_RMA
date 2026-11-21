<?php
define('RMA_APP', true);
require_once 'assets/php/db.php';
require_once 'assets/php/auth.php';
iniciarSessao();

if (usuarioAtual() !== null) {
    header('Location: index.php');
    exit;
}

$erro    = '';
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $usuario = trim((string)($_POST['usuario'] ?? ''));
    $senha   = (string)($_POST['senha'] ?? '');

    $conn = getConn();
    $resultado = tentarLogin($conn, $usuario, $senha);
    $conn->close();

    if ($resultado === 'ok') {
        header('Location: index.php');
        exit;
    }
    $erro = $resultado === 'bloqueado'
        ? 'Muitas tentativas erradas. Aguarde ' . LOGIN_JANELA_MINUTOS . ' minutos e tente de novo.'
        : 'Usuário ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Entrar | Sistema RMA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="pagina-login">

<main>
    <form method="POST" class="modal-box login-box">
        <h3>Sistema RMA</h3>
        <?= csrfCampo() ?>
        <input type="text" name="usuario" placeholder="Usuário" autocomplete="username"
               value="<?= htmlspecialchars($usuario) ?>" required autofocus>
        <input type="password" name="senha" placeholder="Senha" autocomplete="current-password" required>
        <?php if ($erro): ?>
            <p class="erro-msg login-erro"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>
        <div class="modal-actions">
            <button type="submit">Entrar</button>
        </div>
    </form>
</main>

</body>
</html>
