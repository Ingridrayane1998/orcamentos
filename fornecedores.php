<?php
require_once 'config.php';
verificarLogin();

$erro = '';
$sucesso = '';

// Adicionar/Editar fornecedor
if ($_POST) {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $cnpj = $_POST['cnpj'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];
    $contato_responsavel = $_POST['contato_responsavel'];
    
    try {
        if ($id) {
            // Editar
            $stmt = $pdo->prepare("UPDATE fornecedores SET nome=?, cnpj=?, email=?, telefone=?, endereco=?, contato_responsavel=? WHERE id=?");
            $stmt->execute([$nome, $cnpj, $email, $telefone, $endereco, $contato_responsavel, $id]);
            $sucesso = "Fornecedor atualizado com sucesso!";
        } else {
            // Adicionar
            $stmt = $pdo->prepare("INSERT INTO fornecedores (nome, cnpj, email, telefone, endereco, contato_responsavel) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $cnpj, $email, $telefone, $endereco, $contato_responsavel]);
            $sucesso = "Fornecedor cadastrado com sucesso!";
        }
    } catch (Exception $e) {
        $erro = "Erro ao salvar fornecedor: " . $e->getMessage();
    }
}

// Inativar fornecedor
if (isset($_GET['inativar'])) {
    try {
        $stmt = $pdo->prepare("UPDATE fornecedores SET ativo = 0 WHERE id = ?");
        $stmt->execute([$_GET['inativar']]);
        $sucesso = "Fornecedor inativado com sucesso!";
    } catch (Exception $e) {
        $erro = "Erro ao inativar fornecedor: " . $e->getMessage();
    }
}

// Buscar fornecedores
$filtro = $_GET['filtro'] ?? '';
$where = 'WHERE ativo = 1';
$params = [];

if ($filtro) {
    $where .= " AND (nome LIKE ? OR cnpj LIKE ? OR email LIKE ?)";
    $params = ["%$filtro%", "%$filtro%", "%$filtro%"];
}

$stmt = $pdo->prepare("SELECT * FROM fornecedores $where ORDER BY nome");
$stmt->execute($params);
$fornecedores = $stmt->fetchAll();

// Buscar fornecedor para edição
$fornecedor_edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $fornecedor_edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fornecedores - <?= SITE_NAME ?></title>
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
                        <a class="nav-link active" href="fornecedores.php">
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
                    <h2 class="fw-bold">Gerenciar Fornecedores</h2>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#fornecedorModal">
                            <i class="fas fa-plus me-2"></i>Novo Fornecedor
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

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-3">
                            <div class="flex-grow-1">
                                <input type="text" name="filtro" class="form-control" 
                                       placeholder="Buscar por nome, CNPJ ou email..." 
                                       value="<?= htmlspecialchars($filtro) ?>">
                            </div>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search me-2"></i>Buscar
                            </button>
                            <a href="fornecedores.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpar
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Lista de Fornecedores -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Lista de Fornecedores</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome</th>
                                        <th>CNPJ</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th>Contato Responsável</th>
                                        <th>Cadastrado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($fornecedores)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                            <p class="text-muted">Nenhum fornecedor encontrado</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($fornecedores as $fornecedor): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?= $fornecedor['nome'] ?></div>
                                                <?php if ($fornecedor['endereco']): ?>
                                                <small class="text-muted"><?= $fornecedor['endereco'] ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $fornecedor['cnpj'] ?? '-' ?></td>
                                            <td>
                                                <?php if ($fornecedor['email']): ?>
                                                    <a href="mailto:<?= $fornecedor['email'] ?>"><?= $fornecedor['email'] ?></a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($fornecedor['telefone']): ?>
                                                    <a href="tel:<?= $fornecedor['telefone'] ?>"><?= $fornecedor['telefone'] ?></a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $fornecedor['contato_responsavel'] ?? '-' ?></td>
                                            <td><?= date('d/m/Y', strtotime($fornecedor['created_at'])) ?></td>
                                            <td>
                                                <a href="?edit=<?= $fornecedor['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary btn-action" 
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?inativar=<?= $fornecedor['id'] ?>" 
                                                   class="btn btn-sm btn-outline-danger btn-action" 
                                                   title="Inativar"
                                                   onclick="return confirm('Tem certeza que deseja inativar este fornecedor?')">
                                                    <i class="fas fa-trash"></i>
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

    <!-- Modal Fornecedor -->
    <div class="modal fade" id="fornecedorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-truck me-2"></i>
                        <?= $fornecedor_edit ? 'Editar Fornecedor' : 'Novo Fornecedor' ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="fornecedorForm">
                        <?php if ($fornecedor_edit): ?>
                            <input type="hidden" name="id" value="<?= $fornecedor_edit['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-building me-2"></i>Nome da Empresa *
                                </label>
                                <input type="text" class="form-control" name="nome" 
                                       value="<?= $fornecedor_edit['nome'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-id-card me-2"></i>CNPJ
                                </label>
                                <input type="text" class="form-control" name="cnpj" 
                                       value="<?= $fornecedor_edit['cnpj'] ?? '' ?>" 
                                       placeholder="00.000.000/0000-00">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?= $fornecedor_edit['email'] ?? '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-phone me-2"></i>Telefone
                                </label>
                                <input type="text" class="form-control" name="telefone" 
                                       value="<?= $fornecedor_edit['telefone'] ?? '' ?>" 
                                       placeholder="(00) 0000-0000">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-map-marker-alt me-2"></i>Endereço
                            </label>
                            <textarea class="form-control" name="endereco" rows="2" 
                                      placeholder="Endereço completo da empresa"><?= $fornecedor_edit['endereco'] ?? '' ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user-tie me-2"></i>Contato Responsável
                            </label>
                            <input type="text" class="form-control" name="contato_responsavel" 
                                   value="<?= $fornecedor_edit['contato_responsavel'] ?? '' ?>" 
                                   placeholder="Nome do responsável comercial">
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?= $fornecedor_edit ? 'Atualizar' : 'Cadastrar' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($fornecedor_edit): ?>
        // Abrir modal automaticamente se estiver editando
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('fornecedorModal')).show();
        });
        <?php endif; ?>

        // Formatação CNPJ
        document.querySelector('input[name="cnpj"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value;
        });

        // Formatação telefone
        document.querySelector('input[name="telefone"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value;
        });
    </script>
</body>
</html>