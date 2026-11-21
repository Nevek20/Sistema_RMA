<?php
define('RMA_APP', true);
require_once 'assets/php/db.php';
require_once 'assets/php/auth.php';
iniciarSessao();
exigirLogin();

$pagina = $_GET['pagina'] ?? 'listar';
$paginas_validas = ['dashboard', 'listar', 'vincular', 'produto', 'cliente', 'historico', 'backup'];
if (!in_array($pagina, $paginas_validas, true)) {
    $pagina = 'listar';
}

// Permissões conferidas aqui, antes de qualquer HTML. Valem mesmo se alguém
// montar o POST na mão, porque o servidor não confia no que a tela esconde.
$paginas_admin = ['historico', 'backup'];
$acoes_admin   = ['excluir', 'salvar_edicao', 'fazer_backup'];

if (in_array($pagina, $paginas_admin, true)) {
    exigirAdmin();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    foreach ($acoes_admin as $acao) {
        if (isset($_POST[$acao])) exigirAdmin();
    }
}

$usuario = usuarioAtual();
$conn = getConn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema RMA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header>
    <nav>
        <div class="nav-spacer"></div>
        <div class="nav-links">
            <a href="?pagina=dashboard">Dashboard</a>
            <a href="?pagina=listar">Vinculados</a>
            <a href="?pagina=vincular">Vincular</a>
            <a href="?pagina=produto">Processadores</a>
            <a href="?pagina=cliente">Clientes</a>
            <?php if (ehAdmin()): ?>
                <a href="?pagina=historico">Histórico</a>
                <a href="?pagina=backup">Backup</a>
            <?php endif; ?>
        </div>
        <div class="nav-usuario">
            <?php if (ehAdmin()): ?>
                <button id="btn-lock" class="btn-lock" onclick="alternarModoEdicao()" title="Modo de edição">🔒</button>
            <?php endif; ?>
            <span class="nav-nome" title="Perfil: <?= htmlspecialchars($usuario['perfil']) ?>">
                <?= htmlspecialchars($usuario['usuario']) ?>
            </span>
            <form method="POST" action="logout.php">
                <?= csrfCampo() ?>
                <button type="submit" class="btn-sair">Sair</button>
            </form>
        </div>
    </nav>
</header>

<main>
    <div class="container">
        <?php require_once "assets/php/$pagina.php"; ?>
    </div>
</main>

<?php if (ehAdmin()): ?>
<!-- Barra de ações (só admin edita e exclui) -->
<div id="barra-acoes" class="barra-acoes">
    <span id="barra-contagem">0 selecionado(s)</span>
    <div class="barra-botoes">
        <button id="btn-editar-sel" onclick="abrirModalEditar()">Editar</button>
        <button class="btn-excluir-barra" onclick="confirmarExcluir()">Excluir</button>
    </div>
</div>
<?php endif; ?>

<footer>
    Made by Matheus Guida • <a href="https://github.com/Nevek20">GitHub</a>
</footer>

<script src="assets/js/processadores.js"></script>
<?php $conn->close(); ?>
</body>
</html>
