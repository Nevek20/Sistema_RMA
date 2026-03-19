<?php
require_once 'assets/php/db.php';
$conn = getConn();

$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'listar';
$paginas_validas = ['listar', 'vincular', 'produto', 'cliente', 'backup'];
if (!in_array($pagina, $paginas_validas)) {
    $pagina = 'listar';
}
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
            <a href="?pagina=listar">Processadores vinculados</a>
            <a href="?pagina=vincular">Vincular processador</a>
            <a href="?pagina=produto">Processadores</a>
            <a href="?pagina=cliente">Clientes</a>
            <a href="?pagina=backup">Backup do banco</a>
        </div>
        <button id="btn-lock" class="btn-lock" onclick="abrirModalSenha()" title="Modo de edição">🔒</button>
    </nav>
</header>

<!-- Modal: Senha -->
<div id="modal-senha" class="modal-overlay">
    <div class="modal-box">
        <h3>Modo de Edição</h3>
        <input type="password" id="input-senha" placeholder="Senha"
               onkeydown="if(event.key==='Enter') confirmarSenha()">
        <p id="senha-erro" class="erro-msg">Senha incorreta.</p>
        <div class="modal-actions">
            <button type="button" class="btn-secundario" onclick="fecharModalSenha()">Cancelar</button>
            <button type="button" onclick="confirmarSenha()">Entrar</button>
        </div>
    </div>
</div>

<main>
    <div class="container">
        <?php require_once "assets/php/$pagina.php"; ?>
    </div>
</main>

<!-- Barra de ações -->
<div id="barra-acoes" class="barra-acoes">
    <span id="barra-contagem">0 selecionado(s)</span>
    <div class="barra-botoes">
        <button id="btn-editar-sel" onclick="abrirModalEditar()">Editar</button>
        <button class="btn-excluir-barra" onclick="confirmarExcluir()">Excluir</button>
    </div>
</div>

<footer>
    Made by Matheus Guida • <a href="https://github.com/Nevek20">GitHub</a>
</footer>

<script>const ADMIN_PASS = "<?= htmlspecialchars(ADMIN_PASS, ENT_QUOTES) ?>";</script>
<script src="assets/js/processadores.js"></script>
</body>
</html>
