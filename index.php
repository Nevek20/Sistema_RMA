<?php
$conn = new mysqli("localhost", "root", "", "controle_rma");
if ($conn->connect_error) die("Erro de conexão");

$limite = 20;
$pagina_atual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$pagina_atual = max($pagina_atual, 1);
$inicio = ($pagina_atual - 1) * $limite;

$busca = isset($_GET['busca']) ? $_GET['busca'] : '';
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'listar';

$msg = "";

/* ================= BACKUP ================= */

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
        : "Erro ao realizar backup.";
}

/* ================= CLIENTE ================= */

if (isset($_POST['add_cliente'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $conn->query("INSERT INTO clientes (nome) VALUES ('$nome')");
    $msg = "Cliente cadastrado.";
}

/* ================= PRODUTO ================= */

if (isset($_POST['add_produto'])) {
    $modelo = $conn->real_escape_string($_POST['modelo']);
    $conn->query("INSERT INTO processadores (modelo) VALUES ('$modelo')");
    $msg = "Modelo cadastrado.";
}

/* ================= VINCULAR ================= */

if (isset($_POST['vincular'])) {

    $id_cli = (int)$_POST['cliente'];

    $processadores = $_POST['processador'];
    $sns = $_POST['sn'];

    $erros = 0;
    $sucesso = 0;

    for ($i = 0; $i < count($processadores); $i++) {

        $id_proc = (int)$processadores[$i];
        $sn = $conn->real_escape_string($sns[$i]);

        if ($id_proc && $sn) {

            $sql = "INSERT INTO processador_cliente 
                    (cliente_id, processador_id, serial_number) 
                    VALUES ($id_cli, $id_proc, '$sn')";

            if ($conn->query($sql)) {
                $sucesso++;
            } else {
                $erros++;
            }
        }
    }

    $msg = "$sucesso processador(es) vinculado(s).";
    if ($erros > 0) {
        $msg .= " $erros erro(s) ocorreram (SN duplicado?).";
    }
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
    <a href="?pagina=listar">Processadores vinculados</a>
    <a href="?pagina=vincular">Vincular processador</a>
    <a href="?pagina=produto">Processadores</a>
    <a href="?pagina=cliente">Clientes</a>
    <a href="?pagina=backup">Backup do banco</a>
</nav>
</header>

<main>
<div class="container">

<?php if ($msg): ?>
<div class="msg"><?= $msg ?></div>
<?php endif; ?>

<?php if ($pagina == "cliente"): ?>

<h2>Cadastrar Cliente</h2>
<form method="POST" class="form-box">
    <input type="text" name="nome" placeholder="Nome do cliente" required>
    <button name="add_cliente">Cadastrar</button>
</form>

<form method="GET" class="search-box">
    <input type="hidden" name="pagina" value="cliente">
    <input type="text" name="busca" placeholder="Buscar cliente" value="<?= htmlspecialchars($busca) ?>">
    <button>Buscar</button>
</form>

<table>
<tr><th>Clientes</th></tr>
<?php
$where = $busca ? "WHERE nome LIKE '%".$conn->real_escape_string($busca)."%'" : "";
$res = $conn->query("SELECT nome FROM clientes $where ORDER BY nome");
while ($c = $res->fetch_assoc()):
?>
<tr><td><?= htmlspecialchars($c['nome']) ?></td></tr>
<?php endwhile; ?>
</table>

<?php elseif ($pagina == "produto"): ?>

<h2>Cadastrar Processador</h2>
<form method="POST" class="form-box">
    <input type="text" name="modelo" placeholder="Modelo" required>
    <button name="add_produto">Cadastrar</button>
</form>

<form method="GET" class="search-box">
    <input type="hidden" name="pagina" value="produto">
    <input type="text" name="busca" placeholder="Buscar processador" value="<?= htmlspecialchars($busca) ?>">
    <button>Buscar</button>
</form>

<table>
<tr><th>Modelos</th></tr>
<?php
$where = $busca ? "WHERE modelo LIKE '%".$conn->real_escape_string($busca)."%'" : "";
$res = $conn->query("SELECT modelo FROM processadores $where ORDER BY modelo");
while ($p = $res->fetch_assoc()):
?>
<tr><td><?= htmlspecialchars($p['modelo']) ?></td></tr>
<?php endwhile; ?>
</table>

<?php elseif ($pagina == "vincular"): ?>

<h2>Vincular Processador</h2>

<form method="POST" class="form-box" id="formVincular">

<div id="processadores-container">

<div class="item-processador">

<div class="linha-topo">
<span>Processador</span>
<button type="button" class="btn-plus" onclick="addProcessador()">+</button>
</div>

<select name="processador[]" required>
<option value="">Modelo</option>
<?php
$res = $conn->query("SELECT id, modelo FROM processadores ORDER BY modelo");
while ($p = $res->fetch_assoc()):
?>
<option value="<?= $p['id'] ?>"><?= $p['modelo'] ?></option>
<?php endwhile; ?>
</select>

<input type="text" name="sn[]" placeholder="Serial Number" required>

</div>

</div>

<select name="cliente" required>
<option value="">Cliente</option>
<?php
$res = $conn->query("SELECT id, nome FROM clientes ORDER BY nome");
while ($c = $res->fetch_assoc()):
?>
<option value="<?= $c['id'] ?>"><?= $c['nome'] ?></option>
<?php endwhile; ?>
</select>

<button name="vincular">Vincular</button>

</form>

<?php elseif ($pagina == "backup"): ?>

<h2>Backup do Banco</h2>

<div class="msg">
<?php if ($diasDesdeUltimo !== null): ?>
Último backup há <strong><?= $diasDesdeUltimo ?></strong> dia(s).
<?php else: ?>
Nenhum backup encontrado.
<?php endif; ?>
</div>

<form method="POST" class="form-box"
      onsubmit="return confirm('Deseja realmente fazer o backup do banco de dados?');">
<button name="fazer_backup">Fazer Backup Agora</button>
</form>

<p style="font-size:13px;color:#64748b;text-align:center">
C:\Users\Estoque\Desktop\Backup BD
</p>

<?php else: ?>

<h2>Processadores Vinculados</h2>

<form method="GET" class="search-box">
<input type="hidden" name="pagina" value="listar">
<input type="text" name="busca" placeholder="Buscar cliente, modelo ou SN" value="<?= htmlspecialchars($busca) ?>">
<button>Buscar</button>
</form>

<table>
<tr>
<th>Modelo</th>
<th>SN</th>
<th>Cliente</th>
<th>Data</th>
</tr>
<?php
$where = "";
if ($busca) {
    $b = $conn->real_escape_string($busca);
    $where = "WHERE c.nome LIKE '%$b%' OR p.modelo LIKE '%$b%' OR pc.serial_number LIKE '%$b%'";
}

$sql = "
SELECT c.nome cliente, p.modelo, pc.serial_number, pc.data_cadastro
FROM processador_cliente pc
JOIN clientes c ON c.id = pc.cliente_id
JOIN processadores p ON p.id = pc.processador_id
$where
ORDER BY pc.data_cadastro DESC
LIMIT $inicio, $limite
";

$res = $conn->query($sql);
while ($r = $res->fetch_assoc()):
?>
<tr>
<td><?= htmlspecialchars($r['modelo']) ?></td>
<td><?= htmlspecialchars($r['serial_number']) ?></td>
<td><?= htmlspecialchars($r['cliente']) ?></td>
<td><?= $r['data_cadastro'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<?php endif; ?>

</div>
</main>

<footer>
Made by Matheus Guida • <a href="https://github.com/Nevek20">GitHub</a>
</footer>
<script src="assets/js/processadores.js"></script>
</body>
</html>