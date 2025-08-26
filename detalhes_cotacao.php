<?php
require_once 'config.php';
verificarLogin();

$id = $_GET['id'] ?? 0;

// Buscar cotação com categoria
$stmt = $pdo->prepare("
    SELECT c.*, cat.nome as categoria_nome, u.nome as usuario_nome
    FROM cotacoes c
    LEFT JOIN categorias cat ON c.categoria_id = cat.id
    LEFT JOIN usuarios u ON c.usuario_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$cotacao = $stmt->fetch();

if (!$cotacao) {
    header('Location: cotacoes.php');
    exit;
}

// Buscar fornecedores da cotação
$stmt = $pdo->prepare("
    SELECT cf.*, f.nome as fornecedor_nome
    FROM cotacao_fornecedores cf
    LEFT JOIN fornecedores f ON cf.fornecedor_id = f.id
    WHERE cf.cotacao_id = ?
    ORDER BY cf.valor ASC
");
$stmt->execute([$id]);
$fornecedores = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Cotação - <?= SITE_NAME ?></title>
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
        .info-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }
        .info-value {
            color: #6c757d;
        }
        .fornecedor-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .fornecedor-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -18px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #dee2e6;
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
                    <div>
                        <h2 class="fw-bold mb-1">Detalhes da Cotação</h2>
                        <p class="text-muted mb-0"><?= $cotacao['numero_cotacao'] ?></p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="editar_cotacao.php?id=<?= $cotacao['id'] ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        <a href="negociar_cotacao.php?id=<?= $cotacao['id'] ?>" class="btn btn-success">
                            <i class="fas fa-handshake me-2"></i>Negociar
                        </a>
                        <a href="cotacoes.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- Informações da Cotação -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Informações da Cotação
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-heading me-2"></i>Título
                                            </div>
                                            <div class="info-value"><?= $cotacao['titulo'] ?></div>
                                        </div>
                                        
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-tags me-2"></i>Categoria
                                            </div>
                                            <div class="info-value">
                                                <span class="badge bg-primary"><?= $cotacao['categoria_nome'] ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-flag me-2"></i>Status
                                            </div>
                                            <div class="info-value">
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
                                            </div>
                                        </div>
                                        
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-user me-2"></i>Criado por
                                            </div>
                                            <div class="info-value"><?= $cotacao['usuario_nome'] ?></div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-dollar-sign me-2"></i>Valor Inicial
                                            </div>
                                            <div class="info-value h5 text-primary">
                                                <?= formatarMoeda($cotacao['valor_inicial']) ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($cotacao['valor_negociado']): ?>
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-handshake me-2"></i>Valor Negociado
                                            </div>
                                            <div class="info-value h5 text-success">
                                                <?= formatarMoeda($cotacao['valor_negociado']) ?>
                                            </div>
                                        </div>
                                        
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-piggy-bank me-2"></i>Economia
                                            </div>
                                            <div class="info-value h5 text-success">
                                                <?= formatarMoeda($cotacao['valor_inicial'] - $cotacao['valor_negociado']) ?>
                                                <small class="text-muted">
                                                    (<?= number_format((($cotacao['valor_inicial'] - $cotacao['valor_negociado']) / $cotacao['valor_inicial']) * 100, 2) ?>%)
                                                </small>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($cotacao['data_vencimento']): ?>
                                        <div class="info-item">
                                            <div class="info-label">
                                                <i class="fas fa-calendar me-2"></i>Data de Vencimento
                                            </div>
                                            <div class="info-value">
                                                <?= date('d/m/Y', strtotime($cotacao['data_vencimento'])) ?>
                                                <?php if (strtotime($cotacao['data_vencimento']) < time() && $cotacao['status'] != 'fechado'): ?>
                                                    <span class="badge bg-danger ms-2">Vencida</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($cotacao['descricao']): ?>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-file-alt me-2"></i>Descrição
                                    </div>
                                    <div class="info-value"><?= nl2br(htmlspecialchars($cotacao['descricao'])) ?></div>
                                </div>
                                <?php endif; ?>

                                <?php if ($cotacao['observacoes']): ?>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-sticky-note me-2"></i>Observações
                                    </div>
                                    <div class="info-value"><?= nl2br(htmlspecialchars($cotacao['observacoes'])) ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Fornecedores -->
                        <?php if (!empty($fornecedores)): ?>
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-truck me-2"></i>Propostas dos Fornecedores
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($fornecedores as $index => $fornecedor): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card fornecedor-card h-100 <?= $index === 0 ? 'border-success' : '' ?>">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0">
                                                        <?= $fornecedor['fornecedor_nome'] ?>
                                                        <?php if ($index === 0): ?>
                                                            <span class="badge bg-success ms-2">Melhor Oferta</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                </div>
                                                
                                                <div class="mb-2">
                                                    <strong class="text-success h5">
                                                        <?= formatarMoeda($fornecedor['valor']) ?>
                                                    </strong>
                                                </div>
                                                
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        Economia: 
                                                        <span class="text-success fw-bold">
                                                            <?= formatarMoeda($cotacao['valor_inicial'] - $fornecedor['valor']) ?>
                                                        </span>
                                                    </small>
                                                </div>
                                                
                                                <?php if ($fornecedor['observacoes']): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-comment me-1"></i>
                                                        <?= htmlspecialchars($fornecedor['observacoes']) ?>
                                                    </small>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        Cotado em: <?= date('d/m/Y H:i', strtotime($fornecedor['created_at'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Timeline e Estatísticas -->
                    <div class="col-md-4">
                        <!-- Estatísticas Rápidas -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Estatísticas
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <div class="h4 text-primary mb-1"><?= count($fornecedores) ?></div>
                                        <small class="text-muted">Propostas</small>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="h4 text-success mb-1">
                                            <?php if (!empty($fornecedores)): ?>
                                                <?= formatarMoeda($cotacao['valor_inicial'] - min(array_column($fornecedores, 'valor'))) ?>
                                            <?php else: ?>
                                                R$ 0,00
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">Melhor Economia</small>
                                    </div>
                                </div>
                                
                                <?php if (!empty($fornecedores)): ?>
                                <div class="text-center">
                                    <div class="h6 text-info mb-1">
                                        <?= number_format(((min(array_column($fornecedores, 'valor')) / $cotacao['valor_inicial']) * 100), 2) ?>%
                                    </div>
                                    <small class="text-muted">do valor inicial</small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="fas fa-history me-2"></i>Histórico
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="fw-semibold">Cotação Criada</div>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($cotacao['created_at'])) ?>
                                        </small>
                                    </div>
                                    
                                    <?php foreach ($fornecedores as $fornecedor): ?>
                                    <div class="timeline-item">
                                        <div class="fw-semibold">Proposta Recebida</div>
                                        <small class="text-muted d-block">
                                            <?= $fornecedor['fornecedor_nome'] ?>
                                        </small>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($fornecedor['created_at'])) ?>
                                        </small>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if ($cotacao['status'] == 'fechado'): ?>
                                    <div class="timeline-item">
                                        <div class="fw-semibold text-success">Cotação Fechada</div>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($cotacao['updated_at'])) ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>