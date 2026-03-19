<?php
$msg  = '';
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

if (isset($_POST['excluir']) && !empty($_POST['excluir_ids'])) {
    $ids = array_filter(array_map('intval', explode(',', $_POST['excluir_ids'])));
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("DELETE FROM processadores WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $msg = $stmt->execute()
            ? $stmt->affected_rows . " modelo(s) excluído(s)."
            : "Erro: processador possui vínculos ativos.";
    }
}

if (isset($_POST['salvar_edicao'])) {
    $id     = (int)$_POST['editar_id'];
    $modelo = trim($_POST['modelo']);
    $stmt = $conn->prepare("UPDATE processadores SET modelo=? WHERE id=?");
    $stmt->bind_param('si', $modelo, $id);
    $msg = $stmt->execute() ? "Modelo atualizado." : "Erro (modelo duplicado?).";
}

if (isset($_POST['add_produto'])) {
    $modelo = trim($_POST['modelo_novo']);
    $stmt = $conn->prepare("INSERT INTO processadores (modelo) VALUES (?)");
    $stmt->bind_param('s', $modelo);
    $msg = $stmt->execute() ? "Modelo cadastrado." : "Erro: modelo já existe.";
}
?>

<h2>Cadastrar Processador</h2>

<?php if ($msg): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="POST" class="form-box">
    <input type="text" name="modelo_novo" placeholder="Modelo" required>
    <button name="add_produto">Cadastrar</button>
</form>

<form method="GET" class="search-box">
    <input type="hidden" name="pagina" value="produto">
    <input type="text" name="busca" placeholder="Buscar processador" value="<?= htmlspecialchars($busca) ?>">
    <button>Buscar</button>
</form>

<?php
if ($busca) {
    $like = "%$busca%";
    $stmt = $conn->prepare("SELECT id, modelo FROM processadores WHERE modelo LIKE ? ORDER BY modelo");
    $stmt->bind_param('s', $like);
} else {
    $stmt = $conn->prepare("SELECT id, modelo FROM processadores ORDER BY modelo");
}
$stmt->execute();
$res = $stmt->get_result();
?>

<table>
    <tr>
        <th class="col-check"><input type="checkbox" id="check-all" onchange="toggleAll(this)"></th>
        <th>Modelos</th>
    </tr>
    <?php while ($p = $res->fetch_assoc()): ?>
    <tr data-id="<?= $p['id'] ?>"
        data-modelo="<?= htmlspecialchars($p['modelo'], ENT_QUOTES) ?>">
        <td class="col-check"><input type="checkbox" class="row-check" onchange="atualizarBarra()"></td>
        <td><?= htmlspecialchars($p['modelo']) ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<form method="POST" id="form-excluir" style="display:none">
    <input type="hidden" name="excluir_ids" id="excluir-ids">
    <input type="hidden" name="excluir" value="1">
</form>

<div id="modal-editar" class="modal-overlay">
    <div class="modal-box">
        <h3>Editar Processador</h3>
        <form method="POST" class="form-box">
            <input type="hidden" name="editar_id" id="edit-id">
            <label>Modelo</label>
            <input type="text" name="modelo" id="edit-modelo" placeholder="Modelo" required>
            <div class="modal-actions">
                <button type="button" class="btn-secundario" onclick="fecharModalEditar()">Cancelar</button>
                <button type="submit" name="salvar_edicao">Salvar</button>
            </div>
        </form>
    </div>
</div>