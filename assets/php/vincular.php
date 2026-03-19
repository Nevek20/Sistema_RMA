<?php
$msg = '';

if (isset($_POST['vincular'])) {
    $id_cli = (int)$_POST['cliente'];
    $processadores = $_POST['processador'] ?? [];
    $sns = $_POST['sn'] ?? [];

    $sucesso = 0;
    $erros = 0;

    $stmt = $conn->prepare("
        INSERT INTO processador_cliente (cliente_id, processador_id, serial_number)
        VALUES (?, ?, ?)
    ");

    for ($i = 0; $i < count($processadores); $i++) {
        $id_proc = (int)$processadores[$i];
        $sn = trim($sns[$i]);

        if ($id_proc && $sn) {
            $stmt->bind_param('iis', $id_cli, $id_proc, $sn);
            if ($stmt->execute()) {
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

<h2>Vincular Processador</h2>

<?php if ($msg): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="POST" class="form-box" id="formVincular">

    <div id="processadores-container">
        <div class="item-processador">
            <div class="linha-topo">
                <span>Processador</span>
            </div>
            <select name="processador[]" required>
                <option value="">Modelo</option>
                <?php
                $res = $conn->query("SELECT id, modelo FROM processadores ORDER BY modelo");
                while ($p = $res->fetch_assoc()):
                ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['modelo']) ?></option>
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
        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
        <?php endwhile; ?>
    </select>

    <button name="vincular">Vincular</button>

</form>
