<?php
defined('RMA_APP') || exit;
$msg = '';
$backupDir = BACKUP_DIR;   // definido no config.local.php
$ultimoBackup = null;
$diasDesdeUltimo = null;

if (isset($_POST['fazer_backup'])) {
    if (!is_dir($backupDir) && !@mkdir($backupDir, 0775, true)) {
        $msg = "Não foi possível criar a pasta de backup. Confira o BACKUP_DIR no config.local.php.";
    } else {
        $arquivo = $backupDir . '/controle_rma_' . date('Y-m-d_H-i-s') . '.sql';

        // Cada parte passa pelo escapeshellarg, então nenhum valor da config
        // vira comando no terminal. O --result-file evita depender do ">" do shell.
        $partes = [
            escapeshellarg(MYSQLDUMP_PATH),
            '--host=' . escapeshellarg(DB_HOST),
            '--user=' . escapeshellarg(DB_USER),
        ];
        if (DB_PASS !== '') {
            $partes[] = '--password=' . escapeshellarg(DB_PASS);
        }
        $partes[] = '--result-file=' . escapeshellarg($arquivo);
        $partes[] = escapeshellarg(DB_NAME);

        exec(implode(' ', $partes) . ' 2>&1', $output, $retorno);

        if ($retorno === 0) {
            $msg = "Backup realizado com sucesso!";
            registrarLog($conn, 'BACKUP', 'banco', basename($arquivo));
        } else {
            $msg = "Erro ao realizar backup. Confira o MYSQLDUMP_PATH no config.local.php.";
            error_log('Sistema RMA: backup falhou: ' . implode(' | ', $output));
        }
    }
}

$arquivos = glob($backupDir . "/*.sql");
if ($arquivos) {
    $ultimoArquivo = array_reduce($arquivos, function ($a, $b) {
        // FIX: sem valor inicial, $a é null na primeira chamada — passá-lo direto para
        //      filemtime() causava warning no PHP 8 e TypeError no PHP 8.1+
        if ($a === null) return $b;
        return filemtime($a) > filemtime($b) ? $a : $b;
    });
    $ultimoBackup = filemtime($ultimoArquivo);
    $diasDesdeUltimo = floor((time() - $ultimoBackup) / 86400);
}
?>

<h2>Backup do Banco</h2>

<div class="msg">
    <?php if ($msg): ?>
        <?= htmlspecialchars($msg) ?>
    <?php elseif ($diasDesdeUltimo !== null): ?>
        Último backup há <strong><?= $diasDesdeUltimo ?></strong> dia(s).
    <?php else: ?>
        Nenhum backup encontrado na pasta.
    <?php endif; ?>
</div>

<form method="POST" class="form-box" id="formBackup">
    <?= csrfCampo() ?>
    <button name="fazer_backup">Fazer Backup Agora</button>
</form>

<p style="font-size:13px;color:#64748b;text-align:center;margin-top:12px">
    Destino: <?= htmlspecialchars($backupDir) ?>
</p>
