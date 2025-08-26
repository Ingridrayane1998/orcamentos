<?php
require_once 'config.php';
verificarLogin();

// Buscar cotações
$filtro = $_GET['filtro'] ?? '';
$where = '';
$params = [];

if ($filtro) {
    $where = "WHERE (c.numero_cotacao LIKE ? OR c.titulo LIKE ? OR c.status = ?)";
    $params = ["%$filtro%", "%$filtro%", $filtro];
}

$stmt = $pdo->prepare("
    SELECT c.*, cat.nome as categoria_nome, u.nome as usuario_nome
    FROM cotacoes c
    LEFT JOIN categorias cat ON c.categoria_id = cat.id
    LEFT JOIN usuarios u ON c.usuario_id = u.id
    $where
    ORDER BY c.created_at DESC
");
$stmt->execute($params);
$cotacoes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotações - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            margin: 0 2px;
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
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?= $_SESSION['usuario_nome'] ?>
                    </a>
                    <ul class="dropdown-menu">
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link active" href="cotacoes.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">Gerenciar Cotações</h2>
                    <div>
                        <a href="nova_cotacao.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nova Cotação
                        </a>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-3">
                            <div class="flex-grow-1">
                                <input type="text" name="filtro" class="form-control" 
                                       placeholder="Buscar por número, título ou status..." 
                                       value="<?= htmlspecialchars($filtro) ?>">
                            </div>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search me-2"></i>Buscar
                            </button>
                            <a href="cotacoes.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpar
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Tabela de Cotações -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Cotações</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Número</th>
                                        <th>Título</th>
                                        <th>Categoria</th>
                                        <th>Status</th>
                                        <th>Valor Inicial</th>
                                        <th>Valor Negociado</th>
                                        <th>Economia</th>
                                        <th>Criado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cotacoes)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                            <p class="text-muted">Nenhuma cotação encontrada</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($cotacoes as $cotacao): ?>
                                        <tr>
                                            <td><strong><?= $cotacao['numero_cotacao'] ?></strong></td>
                                            <td>
                                                <div class="fw-semibold"><?= $cotacao['titulo'] ?></div>
                                                <small class="text-muted">por <?= $cotacao['usuario_nome'] ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $cotacao['categoria_nome'] ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = [
                                                    'pendente' => 'secondary',
                                                    'cotando' => 'warning',
                                                    'negociando' => 'info',
                                                    'fechado' => 'success',
                                                    'cancelado' => 'danger'
                                                ];
                                                ?>
                                                <span class="badge bg-<?= $badge_class[$cotacao['status']] ?>">
                                                    <?= ucfirst($cotacao['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= formatarMoeda($cotacao['valor_inicial']) ?></td>
                                            <td>
                                                <?php if ($cotacao['valor_negociado']): ?>
                                                    <span class="text-success fw-semibold">
                                                        <?= formatarMoeda($cotacao['valor_negociado']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($cotacao['economia'] > 0): ?>
                                                    <span class="text-success fw-bold">
                                                        <?= formatarMoeda($cotacao['economia']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($cotacao['created_at'])) ?></td>
                                            <td>
                                                <a href="editar_cotacao.php?id=<?= $cotacao['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary btn-action" 
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="negociar_cotacao.php?id=<?= $cotacao['id'] ?>" 
                                                   class="btn btn-sm btn-outline-success btn-action" 
                                                   title="Negociar">
                                                    <i class="fas fa-handshake"></i>
                                                </a>
                                                <a href="detalhes_cotacao.php?id=<?= $cotacao['id'] ?>" 
                                                   class="btn btn-sm btn-outline-info btn-action" 
                                                   title="Detalhes">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>