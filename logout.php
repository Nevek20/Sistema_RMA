<?php
define('RMA_APP', true);
require_once 'assets/php/db.php';
require_once 'assets/php/auth.php';
iniciarSessao();

// Só sai por POST com token: um link ou imagem em outro site não desloga ninguém
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    if (usuarioAtual() !== null) {
        $conn = getConn();
        registrarLog($conn, 'LOGOUT', 'usuário', 'Saiu do sistema');
        $conn->close();
    }
    encerrarSessao();
}

header('Location: login.php');
exit;
