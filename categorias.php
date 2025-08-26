<?php
require_once 'config.php';
verificarLogin();

$erro = '';
$sucesso = '';

// Adicionar/Editar categoria
if ($_POST) {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    
    try {
        if ($id) {
            // Editar
            $stmt = $pdo->prepare("UPDATE categorias SET nome=?, descricao=? WHERE id=?");
            $stmt->execute([$nome, $descricao, $id]);
            $sucesso = "Categoria atualizada com sucesso!";
        } else {
            // Adicionar
            $stmt = $pdo->prepare("INSERT INTO categorias (nome, descricao) VALUES (?, ?)");
            $stmt->execute([$nome, $descricao]);
            $sucesso = "Categoria cadastrada com sucesso!";
        }
    } catch (Exception $e) {
        $erro = "Erro ao salvar categoria: " . $e->getMessage();
    }
}

// Inativar categoria
if (isset($_GET['inativar'])) {
    try {
        // Verificar se há cotações usando esta categoria
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cotacoes WHERE categoria_id = ?");
        $stmt->execute([$_GET['inativar']]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $erro = "Não é possível inativar esta categoria pois existem cotações vinculadas a ela.";
        } else {
            $stmt = $pdo->prepare("UPDATE categorias SET ativo = 0 WHERE id = ?");
            $stmt->execute([$_GET['inativar']]);
            $sucesso = "Categoria inativada com sucesso!";
        }
    } catch (Exception $e) {
        $erro = "Erro ao inativar categoria: " . $e->getMessage();
    }
}

// Buscar categorias
$stmt = $pdo->query("
    SELECT c.*, 
           COUNT(cot.id) as total_cotacoes,
           SUM(CASE WHEN cot.status = 'fechado' THEN 1 ELSE 0 END) as cotacoes_fechadas,
           SUM(cot.economia) as economia_total
    FROM categorias c
    LEFT JOIN cotacoes cot ON c.id = cot.categoria_id
    WHERE c.ativo = 1
    GROUP BY c.id, c.nome, c.descricao, c.ativo, c.created_at, c.updated_at
    ORDER BY c.nome
");
$categorias = $stmt->fetchAll();

// Buscar categoria para edição
$categoria_edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $categoria_edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - <?= SITE_NAME ?></title>
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
        .categoria-card {
            border-left: 5px solid;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }
        .categoria-card-primary { border-left-color: #667eea; }
        .categoria-card-success { border-left-color: #11998e; }
        .categoria-card-info { border-left-color: #4facfe; }
        .categoria-card-warning { border-left-color: #f093fb; }
        .categoria-card-danger { border-left-color: #f5576c; }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
                        <a class="nav-link" href="cotacoes.php">
                            <i class="fas fa-file-invoice me-2"></i>Cotações
                        </a>
                        <a class="nav-link" href="fornecedores.php">
                            <i class="fas fa-truck me-2"></i>Fornecedores
                        </a>
                        <a class="nav-link active" href="categorias.php">
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
                    <h2 class="fw-bold">Gerenciar Categorias</h2>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoriaModal">
                            <i class="fas fa-plus me-2"></i>Nova Categoria
                        </button>
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

                <!-- Cards de Categorias -->
                <div class="row">
                    <?php if (empty($categorias)): ?>
                    <div class="col-12">
                        <div class="card text-center py-5">
                            <div class="card-body">
                                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhuma categoria cadastrada</h5>
                                <p class="text-muted">Clique em "Nova Categoria" para começar</p>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <?php 
                        $colors = ['primary', 'success', 'info', 'warning', 'danger'];
                        $icons = ['fas fa-wrench', 'fas fa-broom', 'fas fa-shield-alt', 'fas fa-laptop', 'fas fa-chair', 'fas fa-leaf', 'fas fa-box'];
                        ?>
                        <?php foreach ($categorias as $index => $categoria): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card categoria-card categoria-card-<?= $colors[$index % count($colors)] ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <i class="<?= $icons[$index % count($icons)] ?> fa-2x text-<?= $colors[$index % count($colors)] ?>"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="?edit=<?= $categoria['id'] ?>">
                                                        <i class="fas fa-edit me-2"></i>Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" 
                                                       href="?inativar=<?= $categoria['id'] ?>"
                                                       onclick="return confirm('Tem certeza que deseja inativar esta categoria?')">
                                                        <i class="fas fa-trash me-2"></i>Inativar
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <h5 class="card-title fw-bold"><?= $categoria['nome'] ?></h5>
                                    <p class="card-text text-muted small">
                                        <?= $categoria['descricao'] ? $categoria['descricao'] : 'Sem descrição' ?>
                                    </p>
                                    
                                    <div class="row mt-3">
                                        <div class="col-4 text-center">
                                            <div class="fw-bold text-<?= $colors[$index % count($colors)] ?>">
                                                <?= $categoria['total_cotacoes'] ?>
                                            </div>
                                            <small class="text-muted">Cotações</small>
                                        </div>
                                        <div class="col-4 text-center">
                                            <div class="fw-bold text-success">
                                                <?= $categoria['cotacoes_fechadas'] ?>
                                            </div>
                                            <small class="text-muted">Fechadas</small>
                                        </div>
                                        <div class="col-4 text-center">
                                            <div class="fw-bold text-info">
                                                <?= $categoria['economia_total'] > 0 ? formatarMoeda($categoria['economia_total']) : '-' ?>
                                            </div>
                                            <small class="text-muted">Economia</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Categoria -->
    <div class="modal fade" id="categoriaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-tags me-2"></i>
                        <?= $categoria_edit ? 'Editar Categoria' : 'Nova Categoria' ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="categoriaForm">
                        <?php if ($categoria_edit): ?>
                            <input type="hidden" name="id" value="<?= $categoria_edit['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-tag me-2"></i>Nome da Categoria *
                            </label>
                            <input type="text" class="form-control" name="nome" 
                                   value="<?= $categoria_edit['nome'] ?? '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-file-alt me-2"></i>Descrição
                            </label>
                            <textarea class="form-control" name="descricao" rows="3" 
                                      placeholder="Descreva o tipo de produtos ou serviços desta categoria"><?= $categoria_edit['descricao'] ?? '' ?></textarea>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?= $categoria_edit ? 'Atualizar' : 'Cadastrar' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($categoria_edit): ?>
        // Abrir modal automaticamente se estiver editando
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('categoriaModal')).show();
        });
        <?php endif; ?>
    </script>
</body>
</html>