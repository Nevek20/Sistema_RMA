<?php
$msg = '';
$backupDir = "C:/Users/Estoque/Desktop/Backup BD";
$ultimoBackup = null;
$diasDesdeUltimo = null;

$arquivos = glob($backupDir . "/*.sql");
if ($arquivos) {
    $ultimoArquivo = array_reduce($arquivos, function ($a, $b) {
        return filemtime($a) > filemtime($b) ? $a : $b;
    });
    $ultimoBackup = filemtime($ultimoArquivo);
    $diasDesdeUltimo = floor((time() - $ultimoBackup) / 86400);
}

if (isset($_POST['fazer_backup'])) {
    $data = date("Y-m-d_H-i-s");
    $arquivo = $backupDir . "/controle_rma_$data.sql";

    $mysqldump = '"C:/xampp/mysql/bin/mysqldump.exe"';
    $comando = "$mysqldump -u root controle_rma > \"$arquivo\"";

    exec($comando, $output, $retorno);

    $msg = ($retorno === 0)
        ? "Backup realizado com sucesso!"
        : "Erro ao realizar backup. Verifique se a pasta existe.";
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
    <button name="fazer_backup">Fazer Backup Agora</button>
</form>

<p style="font-size:13px;color:#64748b;text-align:center;margin-top:12px">
    Destino: <?= htmlspecialchars($backupDir) ?>
</p>
