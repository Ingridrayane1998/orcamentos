<?php
require_once 'config.php';
verificarLogin();

$id = $_GET['id'] ?? 0;
$erro = '';
$sucesso = '';

// Buscar dados da cotação
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

if ($_POST) {
    $valor_negociado = str_replace(['R$ ', '.', ','], ['', '', '.'], $_POST['valor_negociado']);
    $status = $_POST['status'];
    $observacoes = $_POST['observacoes'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE cotacoes 
            SET valor_negociado = ?, status = ?, observacoes = CONCAT(COALESCE(observacoes, ''), '\n\nNegociação em ', NOW(), ':\n', ?)
            WHERE id = ?
        ");
        $stmt->execute([$valor_negociado, $status, $observacoes, $id]);
        
        $sucesso = "Negociação registrada com sucesso!";
        
        // Recarregar dados
        $stmt = $pdo->prepare("
            SELECT c.*, cat.nome as categoria_nome, u.nome as usuario_nome
            FROM cotacoes c
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
            LEFT JOIN usuarios u ON c.usuario_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $cotacao = $stmt->fetch();
        
    } catch (Exception $e) {
        $erro = "Erro ao registrar negociação: " . $e->getMessage();
    }
}

// Calcular economia
$economia = 0;
$percentual_economia = 0;
if ($cotacao['valor_negociado']) {
    $economia = $cotacao['valor_inicial'] - $cotacao['valor_negociado'];
    $percentual_economia = ($economia / $cotacao['valor_inicial']) * 100;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Negociar Cotação - <?= SITE_NAME ?></title>
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
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .stat-card {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }
        .economia-negativa {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
                    <h2 class="fw-bold">Negociar Cotação</h2>
                    <div>
                        <a href="cotacoes.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                </div>

                <?php if ($erro): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $erro ?>
                    </div>
                <?php endif; ?>

                <?php if ($sucesso): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= $sucesso ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Informações da Cotação -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Dados da Cotação</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Número:</strong></td>
                                        <td><?= $cotacao['numero_cotacao'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Título:</strong></td>
                                        <td><?= $cotacao['titulo'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Categoria:</strong></td>
                                        <td><?= $cotacao['categoria_nome'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Valor Inicial:</strong></td>
                                        <td><span class="text-primary fw-bold"><?= formatarMoeda($cotacao['valor_inicial']) ?></span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status Atual:</strong></td>
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
                                    </tr>
                                    <tr>
                                        <td><strong>Criado por:</strong></td>
                                        <td><?= $cotacao['usuario_nome'] ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Resultado da Negociação -->
                    <div class="col-md-6 mb-4">
                        <?php if ($cotacao['valor_negociado']): ?>
                        <div class="stat-card <?= $economia < 0 ? 'economia-negativa' : '' ?>">
                            <i class="fas fa-handshake fa-3x mb-3"></i>
                            <h4 class="fw-bold">Resultado da Negociação</h4>
                            <div class="row text-center mt-4">
                                <div class="col-6">
                                    <h5>Valor Negociado</h5>
                                    <h3 class="fw-bold"><?= formatarMoeda($cotacao['valor_negociado']) ?></h3>
                                </div>
                                <div class="col-6">
                                    <h5><?= $economia >= 0 ? 'Economia' : 'Aumento' ?></h5>
                                    <h3 class="fw-bold"><?= formatarMoeda(abs($economia)) ?></h3>
                                    <small>(<?= number_format(abs($percentual_economia), 1) ?>%)</small>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aguardando negociação</h5>
                                <p class="text-muted">Utilize o formulário abaixo para registrar o valor negociado</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formulário de Negociação -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-handshake me-2"></i>Registrar Negociação</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-dollar-sign me-2"></i>Valor Negociado *
                                    </label>
                                    <input type="text" class="form-control" name="valor_negociado" 
                                           value="<?= $cotacao['valor_negociado'] ? formatarMoeda($cotacao['valor_negociado']) : '' ?>" 
                                           placeholder="R$ 0,00" required id="valor_negociado">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-flag me-2"></i>Status *
                                    </label>
                                    <select class="form-select" name="status" required>
                                        <option value="negociando" <?= $cotacao['status'] == 'negociando' ? 'selected' : '' ?>>Negociando</option>
                                        <option value="fechado" <?= $cotacao['status'] == 'fechado' ? 'selected' : '' ?>>Fechado</option>
                                        <option value="cancelado" <?= $cotacao['status'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Economia Calculada</label>
                                    <input type="text" class="form-control" id="economia_display" readonly 
                                           style="background-color: #e9ecef;">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-sticky-note me-2"></i>Observações da Negociação
                                </label>
                                <textarea class="form-control" name="observacoes" rows="3" 
                                          placeholder="Descreva detalhes da negociação, justificativas, condições especiais, etc."></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="cotacoes.php" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-handshake me-2"></i>Registrar Negociação
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Observações Existentes -->
                <?php if ($cotacao['observacoes']): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Histórico de Observações</h6>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($cotacao['observacoes']) ?></pre>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const valorInicial = <?= $cotacao['valor_inicial'] ?>;
        
        // Formatação de moeda e cálculo de economia
        document.getElementById('valor_negociado').addEventListener('input', function(e) {
            // Formatação
            let value = e.target.value.replace(/\D/g, '');
            value = (value/100).toFixed(2) + '';
            value = value.replace(".", ",");
            value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
            value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
            e.target.value = 'R$ ' + value;
            
            // Cálculo da economia
            const valorNegociado = parseFloat(value.replace(/[R$ .]/g, '').replace(',', '.'));
            const economia = valorInicial - valorNegociado;
            const percentual = (economia / valorInicial) * 100;
            
            if (!isNaN(valorNegociado)) {
                const economiaFormatada = new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                }).format(Math.abs(economia));
                
                const sinal = economia >= 0 ? '+' : '-';
                const tipo = economia >= 0 ? 'Economia' : 'Aumento';
                
                document.getElementById('economia_display').value = 
                    `${sinal}${economiaFormatada} (${percentual.toFixed(1)}% ${tipo})`;
            }
        });
        
        // Disparar evento ao carregar se já houver valor
        if (document.getElementById('valor_negociado').value) {
            document.getElementById('valor_negociado').dispatchEvent(new Event('input'));
        }
    </script>
</body>
</html>