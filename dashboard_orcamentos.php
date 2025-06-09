<?php
$host = "localhost";
$dbname = "controle_orcamentos";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Filtros do formulário
$filtroMes = $_GET['mes'] ?? '';
$filtroCategoria = $_GET['categoria'] ?? '';

$where = [];
if ($filtroMes) {
    $where[] = "MONTH(data_orcamento) = " . (int)$filtroMes;
}
if ($filtroCategoria) {
    $where[] = "categoria = '" . $conn->real_escape_string($filtroCategoria) . "'";
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT *, (valor_proposto - valor_final) AS economia FROM orcamentos $whereSQL";
$result = $conn->query($sql);

$orcamentos = [];
$categoria_economia = [];
$mes_gasto = [];

$total_orcado = 0;
$total_gasto = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orcamentos[] = $row;
        $total_orcado += $row['valor_proposto'];
        $total_gasto += $row['valor_final'];

        $cat = $row['categoria'];
        $categoria_economia[$cat] = ($categoria_economia[$cat] ?? 0) + ($row['valor_proposto'] - $row['valor_final']);

        $mes = date('M', strtotime($row['data_orcamento']));
        $mes_gasto[$mes] = ($mes_gasto[$mes] ?? 0) + $row['valor_final'];
    }
}

// Ordenar os meses para exibição correta na tabela
$mes_gasto_ordenado = [];
for ($i = 1; $i <= 12; $i++) {
    $nomeMes = date('M', mktime(0, 0, 0, $i, 10));
    if (isset($mes_gasto[$nomeMes])) {
        $mes_gasto_ordenado[$nomeMes] = $mes_gasto[$nomeMes];
    }
}

$resultCategorias = $conn->query("SELECT DISTINCT categoria FROM orcamentos");
$categoriasDisponiveis = [];
while ($row = $resultCategorias->fetch_assoc()) {
    $categoriasDisponiveis[] = $row['categoria'];
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Dashboard de Orçamentos</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    h1 { font-size: 24px; }
    .filtros, .cards { margin-bottom: 20px; }
    .card { display: inline-block; background: #f0f0f0; padding: 15px; margin: 10px; border-radius: 8px; min-width: 180px; text-align: center; }

    /* Container para os gráficos, limita largura e centraliza */
    .grafico-container {
        max-width: 600px;
        margin-bottom: 40px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Canvas ocupa 100% do container e altura fixa */
    canvas {
        width: 100% !important;
        height: 300px !important;
    }

    /* Estilo da tabela */
    table {
        border-collapse: collapse;
        width: 100%;
        max-width: 700px;
        margin: 0 auto 40px auto;
        font-size: 14px;
    }
    th, td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: left;
    }
    thead tr {
        background-color: #4CAF50;
        color: white;
    }
    thead tr.mes {
        background-color: #2196F3;
    }
</style>
</head>
<body>

<a href="form_orcamentos.php" style="color: black; text-decoration: none;">➕ Novo Orçamento</a>

<h1>💼 Dashboard de Orçamentos</h1>

<form method="GET" class="filtros">
    <label>Mês:
        <select name="mes">
            <option value="">Todos</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $filtroMes == $m ? 'selected' : '' ?>>
                    <?= date("F", mktime(0, 0, 0, $m, 10)) ?>
                </option>
            <?php endfor; ?>
        </select>
    </label>
    <label>Categoria:
        <select name="categoria">
            <option value="">Todas</option>
            <?php foreach ($categoriasDisponiveis as $cat): ?>
                <option value="<?= $cat ?>" <?= $filtroCategoria == $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Filtrar</button>
</form>

<div class="cards">
    <div class="card">
        <h2><?= count($orcamentos) ?></h2>
        <p>📌 Total de Pedidos</p>
    </div>
    <div class="card">
        <h2>R$ <?= number_format($total_orcado, 2, ',', '.') ?></h2>
        <p>💰 Total Orçado</p>
    </div>
    <div class="card">
        <h2>R$ <?= number_format($total_gasto, 2, ',', '.') ?></h2>
        <p>✅ Total Gasto</p>
    </div>
    <div class="card">
        <h2>R$ <?= number_format($total_orcado - $total_gasto, 2, ',', '.') ?></h2>
        <p>📉 Economia Total</p>
    </div>
</div>

<h2>📈 Economia por Categoria</h2>
<div class="grafico-container">
    <canvas id="graficoEconomia"></canvas>
</div>

<h2>📊 Gasto por Mês</h2>
<div class="grafico-container">
    <canvas id="graficoMes"></canvas>
</div>

<!-- Tabelas com dados detalhados -->
<h2>📋 Dados Detalhados</h2>

<table>
    <thead>
        <tr>
            <th>Categoria</th>
            <th>Economia (R$)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categoria_economia as $cat => $eco): ?>
        <tr>
            <td><?= htmlspecialchars($cat) ?></td>
            <td>R$ <?= number_format($eco, 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<table>
    <thead>
        <tr class="mes">
            <th>Mês</th>
            <th>Gasto (R$)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($mes_gasto_ordenado as $mes => $gasto): ?>
        <tr>
            <td><?= htmlspecialchars($mes) ?></td>
            <td>R$ <?= number_format($gasto, 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
    const categorias = <?= json_encode(array_keys($categoria_economia)) ?>;
    const economias = <?= json_encode(array_values($categoria_economia)) ?>;
    const meses = <?= json_encode(array_keys($mes_gasto_ordenado)) ?>;
    const gastos = <?= json_encode(array_values($mes_gasto_ordenado)) ?>;

    new Chart(document.getElementById('graficoEconomia'), {
        type: 'bar',
        data: {
            labels: categorias,
            datasets: [{
                label: 'Economia (R$)',
                data: economias,
                backgroundColor: '#4CAF50'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    new Chart(document.getElementById('graficoMes'), {
        type: 'line',
        data: {
            labels: meses,
            datasets: [{
                label: 'Gasto Mensal (R$)',
                data: gastos,
                borderColor: '#2196F3',
                backgroundColor: '#BBDEFB',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

</body>
</html>
