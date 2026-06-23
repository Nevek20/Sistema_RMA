<?php
// ── Stats ────────────────────────────────────────────────────────────────────
$r = $conn->query("SELECT COUNT(*) AS total FROM processador_cliente")->fetch_assoc();
$totalVinculos = (int)$r['total'];

$r = $conn->query("SELECT COUNT(DISTINCT cliente_id) AS total FROM processador_cliente")->fetch_assoc();
$clientesAtivos = (int)$r['total'];

$r = $conn->query("SELECT COUNT(*) AS total FROM processadores")->fetch_assoc();
$totalModelos = (int)$r['total'];

$r = $conn->query("SELECT COUNT(*) AS total FROM processador_cliente WHERE MONTH(data_cadastro)=MONTH(NOW()) AND YEAR(data_cadastro)=YEAR(NOW())")->fetch_assoc();
$vincMes = (int)$r['total'];

// ── Top 5 clientes ───────────────────────────────────────────────────────────
$resTop5Cli = $conn->query("
    SELECT c.nome, COUNT(*) AS total
    FROM processador_cliente pc
    JOIN clientes c ON c.id = pc.cliente_id
    GROUP BY pc.cliente_id
    ORDER BY total DESC
    LIMIT 5
");

// ── Top 5 modelos ────────────────────────────────────────────────────────────
$resTop5Mod = $conn->query("
    SELECT p.modelo, COUNT(*) AS total
    FROM processador_cliente pc
    JOIN processadores p ON p.id = pc.processador_id
    GROUP BY pc.processador_id
    ORDER BY total DESC
    LIMIT 5
");

// ── Últimos 6 meses (para o gráfico) ────────────────────────────────────────
$resMeses = $conn->query("
    SELECT DATE_FORMAT(data_cadastro, '%Y-%m') AS mes,
           DATE_FORMAT(data_cadastro, '%b/%Y')  AS mes_label,
           COUNT(*) AS total
    FROM processador_cliente
    WHERE data_cadastro >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mes, mes_label
    ORDER BY mes
");

$labelsChart = [];
$dataChart   = [];
while ($m = $resMeses->fetch_assoc()) {
    $labelsChart[] = $m['mes_label'];
    $dataChart[]   = (int)$m['total'];
}
$resMeses->free();
?>

<h2>Dashboard</h2>

<!-- Stat cards -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-valor"><?= $totalVinculos ?></div>
        <div class="stat-label">Vínculos cadastrados</div>
    </div>
    <div class="stat-card">
        <div class="stat-valor"><?= $clientesAtivos ?></div>
        <div class="stat-label">Clientes com vínculos</div>
    </div>
    <div class="stat-card">
        <div class="stat-valor"><?= $totalModelos ?></div>
        <div class="stat-label">Modelos cadastrados</div>
    </div>
    <div class="stat-card stat-card-destaque">
        <div class="stat-valor"><?= $vincMes ?></div>
        <div class="stat-label">Vínculos este mês</div>
    </div>
</div>

<!-- Gráfico -->
<div class="dash-box" style="margin-bottom:20px">
    <h3 class="dash-box-titulo">Vínculos nos últimos 6 meses</h3>
    <div class="chart-container">
        <canvas id="graficoMeses"></canvas>
    </div>
</div>

<!-- Top 5 lado a lado -->
<div class="dash-grid">
    <div class="dash-box">
        <h3 class="dash-box-titulo">Top 5 clientes</h3>
        <table class="dash-table">
            <tr><th>Cliente</th><th>Vínculos</th></tr>
            <?php while ($c = $resTop5Cli->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($c['nome']) ?></td>
                <td class="dash-num"><?= $c['total'] ?></td>
            </tr>
            <?php endwhile; $resTop5Cli->free(); ?>
        </table>
    </div>
    <div class="dash-box">
        <h3 class="dash-box-titulo">Top 5 modelos</h3>
        <table class="dash-table">
            <tr><th>Modelo</th><th>Vínculos</th></tr>
            <?php while ($p = $resTop5Mod->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($p['modelo']) ?></td>
                <td class="dash-num"><?= $p['total'] ?></td>
            </tr>
            <?php endwhile; $resTop5Mod->free(); ?>
        </table>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('graficoMeses'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsChart) ?>,
        datasets: [{
            label: 'Vínculos',
            data: <?= json_encode($dataChart) ?>,
            backgroundColor: 'rgba(249, 115, 22, 0.7)',
            borderColor: '#ea580c',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});
</script>
