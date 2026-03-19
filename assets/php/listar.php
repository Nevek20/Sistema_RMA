<?php
$msg = '';

if (isset($_POST['excluir']) && !empty($_POST['excluir_ids'])) {
    $ids = array_filter(array_map('intval', explode(',', $_POST['excluir_ids'])));
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("DELETE FROM processador_cliente WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $msg = $stmt->affected_rows . " vínculo(s) excluído(s).";
    }
}

if (isset($_POST['salvar_edicao'])) {
    $id      = (int)$_POST['editar_id'];
    $proc_id = (int)$_POST['processador_id'];
    $cli_id  = (int)$_POST['cliente_id'];
    $sn      = trim($_POST['serial_number']);
    $stmt = $conn->prepare("UPDATE processador_cliente SET processador_id=?, cliente_id=?, serial_number=? WHERE id=?");
    $stmt->bind_param('iisi', $proc_id, $cli_id, $sn, $id);
    $msg = $stmt->execute() ? "Vínculo atualizado." : "Erro (SN duplicado?).";
}

$limite       = 20;
$pagina_atual = isset($_GET['p']) ? max((int)$_GET['p'], 1) : 1;
$inicio       = ($pagina_atual - 1) * $limite;
$busca        = isset($_GET['busca']) ? trim($_GET['busca']) : '';
?>

<h2>Processadores Vinculados</h2>

<?php if ($msg): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="GET" class="search-box">
    <input type="hidden" name="pagina" value="listar">
    <input type="text" name="busca" placeholder="Buscar cliente, modelo ou SN"
           value="<?= htmlspecialchars($busca) ?>">
    <button>Buscar</button>
</form>

<?php
if ($busca) {
    $like = "%$busca%";
    $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM processador_cliente pc JOIN clientes c ON c.id=pc.cliente_id JOIN processadores p ON p.id=pc.processador_id WHERE c.nome LIKE ? OR p.modelo LIKE ? OR pc.serial_number LIKE ?");
    $stmtCount->bind_param('sss', $like, $like, $like);
} else {
    $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM processador_cliente");
}
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_assoc()['total'];
$total_paginas = max(ceil($total / $limite), 1);

if ($busca) {
    $like = "%$busca%";
    $stmt = $conn->prepare("SELECT pc.id, pc.serial_number, pc.data_cadastro, c.id AS cliente_id, c.nome AS cliente, p.id AS processador_id, p.modelo FROM processador_cliente pc JOIN clientes c ON c.id=pc.cliente_id JOIN processadores p ON p.id=pc.processador_id WHERE c.nome LIKE ? OR p.modelo LIKE ? OR pc.serial_number LIKE ? ORDER BY pc.data_cadastro DESC LIMIT ?,?");
    $stmt->bind_param('sssii', $like, $like, $like, $inicio, $limite);
} else {
    $stmt = $conn->prepare("SELECT pc.id, pc.serial_number, pc.data_cadastro, c.id AS cliente_id, c.nome AS cliente, p.id AS processador_id, p.modelo FROM processador_cliente pc JOIN clientes c ON c.id=pc.cliente_id JOIN processadores p ON p.id=pc.processador_id ORDER BY pc.data_cadastro DESC LIMIT ?,?");
    $stmt->bind_param('ii', $inicio, $limite);
}
$stmt->execute();
$res = $stmt->get_result();
?>

<table>
    <tr>
        <th class="col-check"><input type="checkbox" id="check-all" onchange="toggleAll(this)"></th>
        <th>Modelo</th><th>SN</th><th>Cliente</th><th>Data</th>
    </tr>
    <?php while ($r = $res->fetch_assoc()): ?>
    <tr data-id="<?= $r['id'] ?>"
        data-processador-id="<?= $r['processador_id'] ?>"
        data-cliente-id="<?= $r['cliente_id'] ?>"
        data-sn="<?= htmlspecialchars($r['serial_number'], ENT_QUOTES) ?>">
        <td class="col-check"><input type="checkbox" class="row-check" onchange="atualizarBarra()"></td>
        <td><?= htmlspecialchars($r['modelo']) ?></td>
        <td><?= htmlspecialchars($r['serial_number']) ?></td>
        <td><?= htmlspecialchars($r['cliente']) ?></td>
        <td><?= htmlspecialchars($r['data_cadastro']) ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<?php if ($total_paginas > 1): ?>
<div class="paginacao">
    <?php if ($pagina_atual > 1): ?>
        <a href="?pagina=listar&p=<?= $pagina_atual - 1 ?>&busca=<?= urlencode($busca) ?>">← Anterior</a>
    <?php endif; ?>
    <span>Página <?= $pagina_atual ?> de <?= $total_paginas ?></span>
    <?php if ($pagina_atual < $total_paginas): ?>
        <a href="?pagina=listar&p=<?= $pagina_atual + 1 ?>&busca=<?= urlencode($busca) ?>">Próxima →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<form method="POST" id="form-excluir" style="display:none">
    <input type="hidden" name="excluir_ids" id="excluir-ids">
    <input type="hidden" name="excluir" value="1">
</form>

<div id="modal-editar" class="modal-overlay">
    <div class="modal-box">
        <h3>Editar Vínculo</h3>
        <form method="POST" class="form-box">
            <input type="hidden" name="editar_id" id="edit-id">
            <label>Modelo</label>
            <select name="processador_id" id="edit-processador" required>
                <option value="">Selecione</option>
                <?php
                $rp = $conn->query("SELECT id, modelo FROM processadores ORDER BY modelo");
                while ($p = $rp->fetch_assoc()):
                ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['modelo']) ?></option>
                <?php endwhile; ?>
            </select>
            <label>Serial Number</label>
            <input type="text" name="serial_number" id="edit-sn" placeholder="Serial Number" required>
            <label>Cliente</label>
            <select name="cliente_id" id="edit-cliente" required>
                <option value="">Selecione</option>
                <?php
                $rc = $conn->query("SELECT id, nome FROM clientes ORDER BY nome");
                while ($c = $rc->fetch_assoc()):
                ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                <?php endwhile; ?>
            </select>
            <div class="modal-actions">
                <button type="button" class="btn-secundario" onclick="fecharModalEditar()">Cancelar</button>
                <button type="submit" name="salvar_edicao">Salvar</button>
            </div>
        </form>
    </div>
</div>  