<?php
$limite       = 30;
$pagina_atual = isset($_GET['p']) ? max((int)$_GET['p'], 1) : 1;
$inicio       = ($pagina_atual - 1) * $limite;

$countResult = $conn->query("SELECT COUNT(*) AS total FROM logs");
$total       = (int)$countResult->fetch_assoc()['total'];
$countResult->free();
$total_paginas = max(ceil($total / $limite), 1);

$stmt = $conn->prepare("SELECT id, acao, entidade, detalhe, criado_em FROM logs ORDER BY criado_em DESC LIMIT ?,?");
$stmt->bind_param('ii', $inicio, $limite);
$stmt->execute();
$res = $stmt->get_result();

$badgeClass = [
    'VINCULAR' => 'badge-vincular',
    'INSERIR'  => 'badge-inserir',
    'EDITAR'   => 'badge-editar',
    'EXCLUIR'  => 'badge-excluir',
];
?>

<h2>Histórico de Ações</h2>

<?php if ($total === 0): ?>
    <p style="text-align:center;color:var(--muted);margin-top:32px">Nenhuma ação registrada ainda.</p>
<?php else: ?>

<table>
    <tr>
        <th>Data/Hora</th>
        <th>Ação</th>
        <th>Entidade</th>
        <th style="text-align:left">Detalhe</th>
    </tr>
    <?php while ($r = $res->fetch_assoc()):
        $cls = $badgeClass[$r['acao']] ?? 'badge-inserir';
    ?>
    <tr>
        <td style="white-space:nowrap"><?= date('d/m/Y H:i:s', strtotime($r['criado_em'])) ?></td>
        <td><span class="badge <?= $cls ?>"><?= htmlspecialchars($r['acao']) ?></span></td>
        <td><?= htmlspecialchars($r['entidade']) ?></td>
        <td style="text-align:left;font-size:13px;color:var(--secondary)"><?= htmlspecialchars($r['detalhe']) ?></td>
    </tr>
    <?php endwhile;
    $res->free();
    $stmt->close();
    ?>
</table>

<?php if ($total_paginas > 1): ?>
<div class="paginacao">
    <?php if ($pagina_atual > 1): ?>
        <a href="?pagina=historico&p=<?= $pagina_atual - 1 ?>">← Anterior</a>
    <?php endif; ?>
    <span>Página <?= $pagina_atual ?> de <?= $total_paginas ?></span>
    <?php if ($pagina_atual < $total_paginas): ?>
        <a href="?pagina=historico&p=<?= $pagina_atual + 1 ?>">Próxima →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>
