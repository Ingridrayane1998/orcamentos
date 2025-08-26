<?php
require_once 'config.php';
verificarLogin();

// Filtros
$filtro_periodo = $_GET['periodo'] ?? '30'; // dias
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Construir WHERE clause baseado nos filtros
$where_conditions = ["1=1"];
$params = [];

if ($filtro_periodo != 'todos') {
    $where_conditions[] = "c.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $params[] = $filtro_periodo;
}

if ($filtro_categoria) {
    $where_conditions[] = "c.categoria_id = ?";
    $params[] = $filtro_categoria;
}

if ($filtro_usuario && $_SESSION['usuario_perfil'] == 'admin') {
    $where_conditions[] = "c.usuario_id = ?";
    $params[] = $filtro_usuario;
}

if ($filtro_status) {
    $where_conditions[] = "c.status = ?";
    $params[] = $filtro_status;
}

$where_clause = implode(" AND ", $where_conditions);

// Estatísticas principais
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_cotacoes,
        SUM(CASE WHEN status = 'fechado' THEN 1 ELSE 0 END) as cotacoes_fechadas,
        SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as cotacoes_pendentes,
        SUM(CASE WHEN status = 'cotando' THEN 1 ELSE 0 END) as cotacoes_cotando,
        SUM(CASE WHEN status = 'negociando' THEN 1 ELSE 0 END) as cotacoes_negociando,
        SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cotacoes_canceladas,
        SUM(valor_inicial) as valor_total_inicial,
        SUM(valor_negociado) as valor_total_negociado,
        SUM(economia) as economia_total,
        AVG(economia) as economia_media,
        AVG(valor_inicial) as ticket_medio
    FROM cotacoes c
    WHERE $where_clause
");
$stmt->execute($params);
$stats = $stmt->fetch();

// Economia por usuário (só para admin ou dados do próprio usuário)
if ($_SESSION['usuario_perfil'] == 'admin') {
    $stmt = $pdo->prepare("
        SELECT 
            u.nome,
            COUNT(c.id) as total_cotacoes,
            SUM(CASE WHEN c.status = 'fechado' THEN 1 ELSE 0 END) as cotacoes_fechadas,
            SUM(c.economia) as economia_total,
            AVG(c.economia) as economia_media
        FROM usuarios u
        LEFT JOIN cotacoes c ON u.id = c.usuario_id AND $where_clause
        WHERE u.ativo = 1
        GROUP BY u.id, u.nome
        ORDER BY economia_total DESC
        LIMIT 10
    ");
    $stmt->execute($params);
    $economia_por_usuario = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT 
            u.nome,
            COUNT(c.id) as total_cotacoes,
            SUM(CASE WHEN c.status = 'fechado' THEN 1 ELSE 0 END) as cotacoes_fechadas,
            SUM(c.economia) as economia_total,
            AVG(c.economia) as economia_media
        FROM usuarios u
        LEFT JOIN cotacoes c ON u.id = c.usuario_id AND $where_clause
        WHERE u.id = ?
        GROUP BY u.id, u.nome
    ");
    $params_user = array_merge($params, [$_SESSION['usuario_id']]);
    $stmt->execute($params_user);
    $economia_por_usuario = $stmt->fetchAll();
}

// Economia por mês (últimos 12 meses)
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(c.created_at, '%Y-%m') as mes,
        DATE_FORMAT(c.created_at, '%M %Y') as mes_nome,
        COUNT(*) as total_cotacoes,
        SUM(CASE WHEN c.status = 'fechado' THEN 1 ELSE 0 END) as cotacoes_fechadas,
        SUM(c.economia) as economia_mes
    FROM cotacoes c
    WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    " . ($filtro_categoria ? "AND c.categoria_id = ?" : "") . "
    " . ($filtro_usuario && $_SESSION['usuario_perfil'] == 'admin' ? "AND c.usuario_id = ?" : "") . "
    " . ($_SESSION['usuario_perfil'] != 'admin' ? "AND c.usuario_id = " . $_SESSION['usuario_id'] : "") . "
    GROUP BY DATE_FORMAT(c.created_at, '%Y-%m')
    ORDER BY mes ASC
");

$params_mes = [];
if ($filtro_categoria) $params_mes[] = $filtro_categoria;
if ($filtro_usuario && $_SESSION['usuario_perfil'] == 'admin') $params_mes[] = $filtro_usuario;

$stmt->execute($params_mes);
$economia_por_mes = $stmt->fetchAll();

// Top categorias com mais economia
$stmt = $pdo->prepare("
    SELECT 
        cat.nome as categoria,
        COUNT(c.id) as total_cotacoes,
        SUM(c.economia) as economia_total,
        AVG(c.economia) as economia_media
    FROM categorias cat
    LEFT JOIN cotacoes c ON cat.id = c.categoria_id AND $where_clause
    WHERE cat.ativo = 1
    GROUP BY cat.id, cat.nome
    HAVING economia_total > 0
    ORDER BY economia_total DESC
    LIMIT 5
");
$stmt->execute($params);
$top_categorias = $stmt->fetchAll();

// Cotações recentes
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        cat.nome as categoria_nome,
        u.nome as usuario_nome
    FROM cotacoes c
    LEFT JOIN categorias cat ON c.categoria_id = cat.id
    LEFT JOIN usuarios u ON c.usuario_id = u.id
    WHERE $where_clause
    ORDER BY c.created_at DESC
    LIMIT 10
");
$stmt->execute($params);
$cotacoes_recentes = $stmt->fetchAll();

// Buscar dados para os filtros
$categorias = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();
$usuarios = [];
if ($_SESSION['usuario_perfil'] == 'admin') {
    $usuarios = $pdo->query("SELECT * FROM usuarios WHERE ativo = 1 ORDER BY nome")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: none;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .sidebar {
            background: white;
            min-height: calc(100vh - 76px);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .nav-link {
            color: #6c757d;
            border-radius: 10px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .metric-card {
            background: linear-gradient(135deg, var(--bg-start) 0%, var(--bg-end) 100%);
            color: white;
            border-radius: 15px;
        }
        .metric-card.primary { --bg-start: #667eea; --bg-end: #764ba2; }
        .metric-card.success { --bg-start: #11998e; --bg-end: #38ef7d; }
        .metric-card.info { --bg-start: #4facfe; --bg-end: #00f2fe; }
        .metric-card.warning { --bg-start: #ffecd2; --bg-end: #fcb69f; color: #333; }
        .metric-card.danger { --bg-start: #f093fb; --bg-end: #f5576c; }
        .metric-card.dark { --bg-start: #434343; --bg-end: #000000; }
        
        .chart-container {
            position: relative;
            height: 400px;
        }
        .filter-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 2px solid #e9ecef;
        }
        .form-select, .form-control {
            border-radius: 10px;
        }
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
        }
        .progress {
            height: 8px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-building me-2"></i><?= SITE_NAME ?>
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="fas fa-calendar me-2"></i>
                    <?= date('d/m/Y H:i') ?>
                </span>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?= $_SESSION['usuario_nome'] ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="perfil.php">
                            <i class="fas fa-user-edit me-2"></i>Meu Perfil
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <div class="sidebar p-3">
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="cotacoes.php">
                            <i class="fas fa-file-invoice me-2"></i>Cotações
                        </a>
                        <a class="nav-link" href="fornecedores.php">
                            <i class="fas fa-truck me-2"></i>Fornecedores
                        </a>
                        <a class="nav-link" href="categorias.php">
                            <i class="fas fa-tags me-2"></i>Categorias
                        </a>
                        <?php if ($_SESSION['usuario_perfil'] == 'admin'): ?>
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>Usuários
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Dashboard</h2>
                        <p class="text-muted mb-0">Visão geral do sistema de cotações</p>
                    </div>
                    <div>
                        <a href="nova_cotacao.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nova Cotação
                        </a>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card filter-card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label"><i class="fas fa-calendar me-1"></i>Período</label>
                                <select name="periodo" class="form-select">
                                    <option value="7" <?= $filtro_periodo == '7' ? 'selected' : '' ?>>Últimos 7 dias</option>
                                    <option value="30" <?= $filtro_periodo == '30' ? 'selected' : '' ?>>Últimos 30 dias</option>
                                    <option value="90" <?= $filtro_periodo == '90' ? 'selected' : '' ?>>Últimos 90 dias</option>
                                    <option value="365" <?= $filtro_periodo == '365' ? 'selected' : '' ?>>Último ano</option>
                                    <option value="todos" <?= $filtro_periodo == 'todos' ? 'selected' : '' ?>>Todos</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><i class="fas fa-tags me-1"></i>Categoria</label>
                                <select name="categoria" class="form-select">
                                    <option value="">Todas as categorias</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>" <?= $filtro_categoria == $categoria['id'] ? 'selected' : '' ?>>
                                        <?= $categoria['nome'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if ($_SESSION['usuario_perfil'] == 'admin'): ?>
                            <div class="col-md-3">
                                <label class="form-label"><i class="fas fa-user me-1"></i>Usuário</label>
                                <select name="usuario" class="form-select">
                                    <option value="">Todos os usuários</option>
                                    <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?= $usuario['id'] ?>" <?= $filtro_usuario == $usuario['id'] ? 'selected' : '' ?>>
                                        <?= $usuario['nome'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-2">
                                <label class="form-label"><i class="fas fa-flag me-1"></i>Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Todos os status</option>
                                    <option value="pendente" <?= $filtro_status == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                    <option value="cotando" <?= $filtro_status == 'cotando' ? 'selected' : '' ?>>Cotando</option>
                                    <option value="negociando" <?= $filtro_status == 'negociando' ? 'selected' : '' ?>>Negociando</option>
                                    <option value="fechado" <?= $filtro_status == 'fechado' ? 'selected' : '' ?>>Fechado</option>
                                    <option value="cancelado" <?= $filtro_status == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-filter me-1"></i>Filtrar
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Cards de Métricas -->
                <div class="row mb-4">
                    <div class="col-md-2 mb-3">
                        <div class="card metric-card primary text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-file-invoice fa-2x mb-2"></i>
                                <h3 class="fw-bold"><?= number_format($stats['total_cotacoes']) ?></h3>
                                <p class="mb-0">Total de Cotações</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card metric-card success text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <h3 class="fw-bold"><?= number_format($stats['cotacoes_fechadas']) ?></h3>
                                <p class="mb-0">Fechadas</p>
                                <?php if ($stats['total_cotacoes'] > 0): ?>
                                <small><?= number_format(($stats['cotacoes_fechadas'] / $stats['total_cotacoes']) * 100, 1) ?>% taxa</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card metric-card info text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-piggy-bank fa-2x mb-2"></i>
                                <h3 class="fw-bold"><?= formatarMoeda($stats['economia_total'] ?? 0) ?></h3>
                                <p class="mb-0">Economia Total</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card metric-card warning text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-chart-line fa-2x mb-2"></i>
                                <h3 class="fw-bold"><?= formatarMoeda($stats['economia_media'] ?? 0) ?></h3>
                                <p class="mb-0">Economia Média</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card metric-card danger text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-ticket-alt fa-2x mb-2"></i>
                                <h3 class="fw-bold"><?= formatarMoeda($stats['ticket_medio'] ?? 0) ?></h3>
                                <p class="mb-0">Ticket Médio</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card metric-card dark text-center h-100">
                            <div class="card-body">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h3 class="fw-bold"><?= number_format($stats['cotacoes_pendentes'] + $stats['cotacoes_cotando'] + $stats['cotacoes_negociando']) ?></h3>
                                <p class="mb-0">Em Andamento</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row mb-4">
                    <!-- Economia por Mês -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Economia por Mês</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="economiaMesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status das Cotações -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Status das Cotações</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Economia por Usuário -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Economia por Usuário</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($economia_por_usuario as $index => $user): ?>
                                    <?php if ($user['economia_total'] > 0): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="mb-0"><?= $user['nome'] ?></h6>
                                            <small class="text-muted">
                                                <?= $user['cotacoes_fechadas'] ?> de <?= $user['total_cotacoes'] ?> cotações fechadas
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-success"><?= formatarMoeda($user['economia_total']) ?></div>
                                            <small class="text-muted">Média: <?= formatarMoeda($user['economia_media'] ?? 0) ?></small>
                                        </div>
                                    </div>
                                    <?php if ($stats['economia_total'] > 0): ?>
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-success" 
                                             style="width: <?= ($user['economia_total'] / $stats['economia_total']) * 100 ?>%"></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Top Categorias -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Top Categorias por Economia</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($top_categorias as $categoria): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-0"><?= $categoria['categoria'] ?></h6>
                                        <small class="text-muted"><?= $categoria['total_cotacoes'] ?> cotações</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success"><?= formatarMoeda($categoria['economia_total']) ?></div>
                                        <small class="text-muted">Média: <?= formatarMoeda($categoria['economia_media']) ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cotações Recentes -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Cotações Recentes</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Número</th>
                                        <th>Título</th>
                                        <th>Categoria</th>
                                        <?php if ($_SESSION['usuario_perfil'] == 'admin'): ?>
                                        <th>Usuário</th>
                                        <?php endif; ?>
                                        <th>Valor Inicial</th>
                                        <th>Economia</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cotacoes_recentes as $cotacao): ?>
                                    <tr>
                                        <td class="fw-bold"><?= $cotacao['numero_cotacao'] ?></td>
                                        <td><?= htmlspecialchars($cotacao['titulo']) ?></td>
                                        <td><span class="badge bg-primary"><?= $cotacao['categoria_nome'] ?></span></td>
                                        <?php if ($_SESSION['usuario_perfil'] == 'admin'): ?>
                                        <td><?= $cotacao['usuario_nome'] ?></td>
                                        <?php endif; ?>
                                        <td><?= formatarMoeda($cotacao['valor_inicial']) ?></td>
                                        <td>
                                            <?php if ($cotacao['economia']): ?>
                                                <span class="text-success fw-bold"><?= formatarMoeda($cotacao['economia']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_badges = [
                                                'pendente' => 'secondary',
                                                'cotando' => 'warning',
                                                'negociando' => 'info',
                                                'fechado' => 'success',
                                                'cancelado' => 'danger'
                                            ];
                                            ?>
                                            <span class="badge bg-<?= $status_badges[$cotacao['status']] ?>">
                                                <?= ucfirst($cotacao['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($cotacao['created_at'])) ?></td>
                                        <td>
                                            <a href="detalhes_cotacao.php?id=<?= $cotacao['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de Economia por Mês
        const economiaMesCtx = document.getElementById('economiaMesChart').getContext('2d');
        const economiaMesChart = new Chart(economiaMesCtx, {
            type: 'line',
            data: {
                labels: [<?= implode(',', array_map(function($item) { return "'" . $item['mes_nome'] . "'"; }, $economia_por_mes)) ?>],
                datasets: [{
                    label: 'Economia (R$)',
                    data: [<?= implode(',', array_column($economia_por_mes, 'economia_mes')) ?>],
                    borderColor: '#11998e',
                    backgroundColor: 'rgba(17, 153, 142, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Cotações Fechadas',
                    data: [<?= implode(',', array_column($economia_por_mes, 'cotacoes_fechadas')) ?>],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Economia (R$)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Cotações Fechadas'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        // Gráfico de Status
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Fechadas', 'Pendentes', 'Cotando', 'Negociando', 'Canceladas'],
                datasets: [{
                    data: [
                        <?= $stats['cotacoes_fechadas'] ?>,
                        <?= $stats['cotacoes_pendentes'] ?>,
                        <?= $stats['cotacoes_cotando'] ?>,
                        <?= $stats['cotacoes_negociando'] ?>,
                        <?= $stats['cotacoes_canceladas'] ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#6c757d',
                        '#ffc107',
                        '#17a2b8',
                        '#dc3545'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>
