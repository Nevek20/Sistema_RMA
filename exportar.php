<?php
/**
 * exportar.php — script standalone para exportar vínculos como CSV
 * Acesse diretamente: http://localhost/Sistema_RMA/exportar.php?...
 * Aceita os mesmos filtros de listar.php via GET.
 */
require_once __DIR__ . '/assets/php/db.php';
$conn = getConn();

$busca      = trim($_GET['busca']      ?? '');
$cliente_id = (int)($_GET['cliente_id'] ?? 0);
$modelo_id  = (int)($_GET['modelo_id']  ?? 0);
$data_de    = trim($_GET['data_de']    ?? '');
$data_ate   = trim($_GET['data_ate']   ?? '');

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

$stmt = $conn->prepare(
    "SELECT pc.serial_number, p.modelo, c.nome AS cliente, pc.data_cadastro
     $baseJoin $whereSQL
     ORDER BY pc.data_cadastro DESC"
);
if ($paramTypes) $stmt->bind_param($paramTypes, ...$paramValues);
$stmt->execute();
$res = $stmt->get_result();

// Headers CSV — BOM UTF-8 para Excel abrir corretamente
$filename = 'rma_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

fputcsv($out, ['Serial Number', 'Modelo', 'Cliente', 'Data de Cadastro'], ';');

while ($r = $res->fetch_assoc()) {
    fputcsv($out, [
        $r['serial_number'],
        $r['modelo'],
        $r['cliente'],
        date('d/m/Y H:i', strtotime($r['data_cadastro'])),
    ], ';');
}

fclose($out);
$res->free();
$stmt->close();
$conn->close();
