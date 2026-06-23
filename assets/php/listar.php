<?php
$msg = '';

if (isset($_POST['excluir']) && !empty($_POST['excluir_ids'])) {
    $ids = array_filter(array_map('intval', explode(',', $_POST['excluir_ids'])));
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("DELETE FROM processador_cliente WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        if ($stmt->execute()) {
            $msg = $stmt->affected_rows . " vínculo(s) excluído(s).";
            registrarLog($conn, 'EXCLUIR', 'vínculo', "IDs excluídos: " . implode(', ', $ids));
        } else {
            $msg = "Erro ao excluir.";
        }
        $stmt->close();
    }
}

if (isset($_POST['salvar_edicao'])) {
    $id      = (int)$_POST['editar_id'];
    $proc_id = (int)$_POST['processador_id'];
    $cli_id  = (int)$_POST['cliente_id'];
    $sn      = trim($_POST['serial_number']);
    $stmt = $conn->prepare("UPDATE processador_cliente SET processador_id=?, cliente_id=?, serial_number=? WHERE id=?");
    $stmt->bind_param('iisi', $proc_id, $cli_id, $sn, $id);
    if ($stmt->execute()) {
        $msg = "Vínculo atualizado.";
        registrarLog($conn, 'EDITAR', 'vínculo', "ID: $id | Proc. ID: $proc_id | Cliente ID: $cli_id | SN: $sn");
    } else {
        $msg = "Erro (SN duplicado?).";
    }
    $stmt->close();
}

// ── Filtros ──────────────────────────────────────────────────────────────────
$limite       = 20;
$pagina_atual = isset($_GET['p']) ? max((int)$_GET['p'], 1) : 1;
$inicio       = ($pagina_atual - 1) * $limite;
$busca        = trim($_GET['busca']    ?? '');
$cliente_id   = (int)($_GET['cliente_id'] ?? 0);
$modelo_id    = (int)($_GET['modelo_id']  ?? 0);
$data_de      = trim($_GET['data_de']  ?? '');
$data_ate     = trim($_GET['data_ate'] ?? '');

$baseJoin = "FROM processador_cliente pc
    JOIN clientes c ON c.id = pc.cliente_id
    JOIN processadores p ON p.id = pc.processador_id";

$conditions  = [];
$paramTypes  = '';
$paramValues = [];

if ($busca !== '') {
    $like = "%$busca%";
    $conditions[] = "(c.nome LIKE ? OR p.modelo LIKE ? OR pc.serial_number LIKE ?)";
    $paramTypes .= 'sss';
    array_push($paramValues, $like, $like, $like);
}
if ($cliente_id) {
    $conditions[] = "pc.cliente_id = ?";
    $paramTypes  .= 'i';
    $paramValues[] = $cliente_id;
}
if ($modelo_id) {
    $conditions[] = "pc.processador_id = ?";
    $paramTypes  .= 'i';
    $paramValues[] = $modelo_id;
}
if ($data_de !== '') {
    $conditions[] = "DATE(pc.data_cadastro) >= ?";
    $paramTypes  .= 's';
    $paramValues[] = $data_de;
}
if ($data_ate !== '') {
    $conditions[] = "DATE(pc.data_cadastro) <= ?";
    $paramTypes  .= 's';
    $paramValues[] = $data_ate;
}

$whereSQL = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Count
$stmtCount = $conn->prepare("SELECT COUNT(*) AS total $baseJoin $whereSQL");
if ($paramTypes) $stmtCount->bind_param($paramTypes, ...$paramValues);
$stmtCount->execute();
$countResult = $stmtCount->get_result();
$total = $countResult->fetch_assoc()['total'];
$countResult->free();
$stmtCount->close();

$total_paginas = max(ceil($total / $limite), 1);

// Main query
$stmt = $conn->prepare(
    "SELECT pc.id, pc.serial_number, pc.data_cadastro,
            c.id AS cliente_id, c.nome AS cliente,
            p.id AS processador_id, p.modelo
     $baseJoin $whereSQL
     ORDER BY pc.data_cadastro DESC
     LIMIT ?,?"
);
$mainTypes  = $paramTypes . 'ii';
$mainValues = array_merge($paramValues, [$inicio, $limite]);
$stmt->bind_param($mainTypes, ...$mainValues);
$stmt->execute();
$res = $stmt->get_result();

// Query string para paginação e exportação
$queryParams = array_filter([
    'pagina'     => 'listar',
    'busca'      => $busca,
    'cliente_id' => $cliente_id ?: '',
    'modelo_id'  => $modelo_id  ?: '',
    'data_de'    => $data_de,
    'data_ate'   => $data_ate,
]);
$queryString = http_build_query($queryParams);
?>

<div class="listar-header">
    <h2>Processadores Vinculados</h2>
    <a href="exportar.php?<?= $queryString ?>" class="btn-exportar" title="Exportar resultado atual como CSV">
        ↓ Exportar CSV
    </a>
</div>

<?php if ($msg): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Filtros avançados -->
<form method="GET" class="filtros-grid">
    <input type="hidden" name="pagina" value="listar">
    <input type="text" name="busca" placeholder="Buscar SN, modelo..." value="<?= htmlspecialchars($busca) ?>">
    <select name="cliente_id">
        <option value="">Todos os clientes</option>
        <?php
        $rc = $conn->query("SELECT DISTINCT c.id, c.nome FROM clientes c JOIN processador_cliente pc ON pc.cliente_id=c.id ORDER BY c.nome");
        while ($c = $rc->fetch_assoc()):
            $sel = ($cliente_id === $c['id']) ? 'selected' : '';
        ?><option value="<?= $c['id'] ?>" <?= $sel ?>><?= htmlspecialchars($c['nome']) ?></option>
        <?php endwhile; $rc->free(); ?>
    </select>
    <select name="modelo_id">
        <option value="">Todos os modelos</option>
        <?php
        $rp = $conn->query("SELECT DISTINCT p.id, p.modelo FROM processadores p JOIN processador_cliente pc ON pc.processador_id=p.id ORDER BY p.modelo");
        while ($p = $rp->fetch_assoc()):
            $sel = ($modelo_id === $p['id']) ? 'selected' : '';
        ?><option value="<?= $p['id'] ?>" <?= $sel ?>><?= htmlspecialchars($p['modelo']) ?></option>
        <?php endwhile; $rp->free(); ?>
    </select>
    <input type="date" name="data_de"  value="<?= htmlspecialchars($data_de) ?>" title="De">
    <input type="date" name="data_ate" value="<?= htmlspecialchars($data_ate) ?>" title="Até">
    <button>Filtrar</button>
    <a href="?pagina=listar" class="btn-limpar">Limpar</a>
</form>

<p class="total-registros"><?= $total ?> resultado(s)</p>

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
        <td><?= date('d/m/Y H:i', strtotime($r['data_cadastro'])) ?></td>
    </tr>
    <?php endwhile;
    $res->free();
    $stmt->close();
    ?>
</table>

<?php if ($total_paginas > 1): ?>
<div class="paginacao">
    <?php if ($pagina_atual > 1): ?>
        <a href="?<?= $queryString ?>&p=<?= $pagina_atual - 1 ?>">← Anterior</a>
    <?php endif; ?>
    <span>Página <?= $pagina_atual ?> de <?= $total_paginas ?></span>
    <?php if ($pagina_atual < $total_paginas): ?>
        <a href="?<?= $queryString ?>&p=<?= $pagina_atual + 1 ?>">Próxima →</a>
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
                <?php endwhile; $rp->free(); ?>
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
                <?php endwhile; $rc->free(); ?>
            </select>
            <div class="modal-actions">
                <button type="button" class="btn-secundario" onclick="fecharModalEditar()">Cancelar</button>
                <button type="submit" name="salvar_edicao">Salvar</button>
            </div>
        </form>
    </div>
</div>
