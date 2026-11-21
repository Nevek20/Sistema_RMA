<?php
defined('RMA_APP') || exit;

// Tentativas erradas permitidas por usuário dentro da janela, antes de bloquear
const LOGIN_MAX_TENTATIVAS = 5;
const LOGIN_JANELA_MINUTOS = 15;

// Hash de uma senha que não existe. Quando o usuário digitado não existe, o
// password_verify roda contra ele, para o tempo de resposta não revelar quais
// nomes de usuário são válidos.
const HASH_FALSO = '$2y$10$baN6zJPBMcgY9ke81z2y7.aWWaBvtEeD.7fHJaIs2SB57CnxfKVEa';

function iniciarSessao(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    ini_set('session.use_strict_mode', '1');
    session_name('rma_sessao');
    session_set_cookie_params([
        'httponly' => true,     // JavaScript não lê o cookie
        'samesite' => 'Lax',    // outro site não envia o cookie em POST
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();

    $ultimo = $_SESSION['ultimo_acesso'] ?? null;
    if ($ultimo !== null && time() - $ultimo > SESSAO_MINUTOS * 60) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['ultimo_acesso'] = time();
}

function usuarioAtual(): ?array {
    if (!isset($_SESSION['usuario_id'])) return null;
    return [
        'id'      => $_SESSION['usuario_id'],
        'usuario' => $_SESSION['usuario'],
        'perfil'  => $_SESSION['perfil'],
    ];
}

function ehAdmin(): bool {
    return ($_SESSION['perfil'] ?? '') === 'admin';
}

function exigirLogin(): void {
    if (usuarioAtual() === null) {
        header('Location: login.php');
        exit;
    }
}

function negarAcesso(string $motivo): void {
    http_response_code(403);
    exit(htmlspecialchars($motivo));
}

function exigirAdmin(): void {
    if (!ehAdmin()) negarAcesso('Acesso restrito a administradores.');
}

// ── CSRF ─────────────────────────────────────────────────────────────────────
// Todo formulário POST leva um token da sessão. Um site de fora não consegue
// ler esse token, então não consegue forjar um POST em nome do usuário logado.

function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCampo(): string {
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

function validarCsrf(): void {
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrfToken(), $enviado)) {
        negarAcesso('Formulário expirado ou inválido. Recarregue a página e tente de novo.');
    }
}

// ── Login ────────────────────────────────────────────────────────────────────

// Devolve 'ok', 'invalido' ou 'bloqueado'.
function tentarLogin(mysqli $conn, string $usuario, string $senha): string {
    $usuario = mb_substr($usuario, 0, 50);   // mesmo tamanho da coluna

    // Bloqueio por excesso de tentativas, contado no próprio histórico
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM logs
         WHERE acao = 'LOGIN_FALHOU' AND usuario = ?
           AND criado_em > NOW() - INTERVAL ? MINUTE"
    );
    $janela = LOGIN_JANELA_MINUTOS;
    $stmt->bind_param('si', $usuario, $janela);
    $stmt->execute();
    $falhas = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    if ($falhas >= LOGIN_MAX_TENTATIVAS) return 'bloqueado';

    $stmt = $conn->prepare("SELECT id, usuario, senha_hash, perfil FROM usuarios WHERE usuario = ?");
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $conta = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $senhaConfere = password_verify($senha, $conta['senha_hash'] ?? HASH_FALSO);
    if (!$conta || !$senhaConfere) {
        registrarLog($conn, 'LOGIN_FALHOU', 'usuário', 'Usuário ou senha inválidos', $usuario);
        return 'invalido';
    }

    // ID de sessão novo no login: impede fixação de sessão
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int)$conta['id'];
    $_SESSION['usuario']    = $conta['usuario'];
    $_SESSION['perfil']     = $conta['perfil'];
    unset($_SESSION['csrf']);

    registrarLog($conn, 'LOGIN', 'usuário', 'Entrou no sistema');
    return 'ok';
}

function encerrarSessao(): void {
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $p['path'],
        'domain'   => $p['domain'],
        'secure'   => $p['secure'],
        'httponly' => $p['httponly'],
        'samesite' => $p['samesite'],
    ]);
    session_destroy();
}
