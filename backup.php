<?php
session_start();

/* ===== CONFIGURAÇÕES ===== */
$host = "localhost";
$user = "root";
$pass = "";
$db   = "controle_rma";

/* ===== PASTA DE BACKUP ===== */
$pasta = __DIR__ . "/backups";
if (!is_dir($pasta)) {
    mkdir($pasta, 0777, true);
}

/* ===== NOME DO ARQUIVO ===== */
$data = date("Y-m-d_H-i-s");
$arquivo = "{$pasta}/backup_{$db}_{$data}.sql";

/* ===== CAMINHO DO MYSQLDUMP (XAMPP) ===== */
$mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";

/* ===== COMANDO ===== */
$comando = "\"$mysqldump\" --user={$user} --password={$pass} --host={$host} {$db} > \"{$arquivo}\"";

/* ===== EXECUTA ===== */
exec($comando, $retorno);

/* ===== RESULTADO ===== */
if ($retorno === 0) {
    header("Location: index.php?msg=Backup realizado com sucesso");
} else {
    header("Location: index.php?msg=Erro ao gerar backup");
}
exit;
