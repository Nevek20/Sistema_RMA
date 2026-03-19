<?php
$msg  = '';
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

if (isset($_POST['excluir']) && !empty($_POST['excluir_ids'])) {
    $ids = array_filter(array_map('intval', explode(',', $_POST['excluir_ids'])));
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("DELETE FROM clientes WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $msg = $stmt->execute()
            ? $stmt->affected_rows . " cliente(s) excluído(s)."
            : "Erro: cliente possui vínculos ativos.";
    }
}

if (isset($_POST['salvar_edicao'])) {
    $id   = (int)$_POST['editar_id'];
    $nome = trim($_POST['nome']);
    $stmt = $conn->prepare("UPDATE clientes SET nome=? WHERE id=?");
    $stmt->bind_param('si', $nome, $id);
    $msg = $stmt->execute() ? "Cliente atualizado." : "Erro (nome duplicado?).";
}

if (isset($_POST['add_cliente'])) {
    $nome = trim($_POST['nome_novo']);
    $stmt = $conn->prepare("INSERT INTO clientes (nome) VALUES (?)");
    $stmt->bind_param('s', $nome);
    $msg = $stmt->execute() ? "Cliente cadastrado." : "Erro: cliente já existe.";
}
?>

<h2>Cadastrar Cliente</h2>

<?php if ($msg): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="POST" class="form-box">
    <input type="text" name="nome_novo" placeholder="Nome do cliente" required>
    <button name="add_cliente">Cadastrar</button>
</form>

<form method="GET" class="search-box">
    <input type="hidden" name="pagina" value="cliente">
    <input type="text" name="busca" placeholder="Buscar cliente" value="<?= htmlspecialchars($busca) ?>">
    <button>Buscar</button>
</form>

<?php
if ($busca) {
    $like = "%$busca%";
    $stmt = $conn->prepare("SELECT id, nome FROM clientes WHERE nome LIKE ? ORDER BY nome");
    $stmt->bind_param('s', $like);
} else {
    $stmt = $conn->prepare("SELECT id, nome FROM clientes ORDER BY nome");
}
$stmt->execute();
$res = $stmt->get_result();
?>

<table>
    <tr>
        <th class="col-check"><input type="checkbox" id="check-all" onchange="toggleAll(this)"></th>
        <th>Clientes</th>
    </tr>
    <?php while ($c = $res->fetch_assoc()): ?>
    <tr data-id="<?= $c['id'] ?>"
        data-nome="<?= htmlspecialchars($c['nome'], ENT_QUOTES) ?>">
        <td class="col-check"><input type="checkbox" class="row-check" onchange="atualizarBarra()"></td>
        <td><?= htmlspecialchars($c['nome']) ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<form method="POST" id="form-excluir" style="display:none">
    <input type="hidden" name="excluir_ids" id="excluir-ids">
    <input type="hidden" name="excluir" value="1">
</form>

<div id="modal-editar" class="modal-overlay">
    <div class="modal-box">
        <h3>Editar Cliente</h3>
        <form method="POST" class="form-box">
            <input type="hidden" name="editar_id" id="edit-id">
            <label>Nome</label>
            <input type="text" name="nome" id="edit-nome" placeholder="Nome do cliente" required>
            <div class="modal-actions">
                <button type="button" class="btn-secundario" onclick="fecharModalEditar()">Cancelar</button>
                <button type="submit" name="salvar_edicao">Salvar</button>
            </div>
        </form>
    </div>
</div>