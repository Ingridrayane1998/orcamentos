<?php
require_once 'config.php';
verificarLogin();

// Verificar se é admin
if ($_SESSION['usuario_perfil'] != 'admin') {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
$sucesso = '';

// Adicionar/Editar usuário
if ($_POST) {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $perfil = $_POST['perfil'];
    
    try {
        if ($id) {
            // Editar
            if ($senha) {
                $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, senha=MD5(?), perfil=? WHERE id=?");
                $stmt->execute([$nome, $email, $senha, $perfil, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, perfil=? WHERE id=?");
                $stmt->execute([$nome, $email, $perfil, $id]);
            }
            $sucesso = "Usuário atualizado com sucesso!";
        } else {
            // Adicionar
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, perfil) VALUES (?, ?, MD5(?), ?)");
            $stmt->execute([$nome, $email, $senha, $perfil]);
            $sucesso = "Usuário cadastrado com sucesso!";
        }
    } catch (Exception $e) {
        $erro = "Erro ao salvar usuário: " . $e->getMessage();
    }
}

// Inativar usuário
if (isset($_GET['inativar'])) {
    try {
        if ($_GET['inativar'] == $_SESSION['usuario_id']) {
            $erro = "Você não pode inativar seu próprio usuário!";
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
            $stmt->execute([$_GET['inativar']]);
            $sucesso = "Usuário inativado com sucesso!";
        }
    } catch (Exception $e) {
        $erro = "Erro ao inativar usuário: " . $e->getMessage();
    }
}

// Buscar usuários
$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(c.id) as total_cotacoes,
           SUM(CASE WHEN c.status = 'fechado' THEN 1 ELSE 0 END) as cotacoes_fechadas,
           SUM(c.economia) as economia_total
    FROM usuarios u
    LEFT JOIN cotacoes c ON u.id = c.usuario_id
    WHERE u.ativo = 1
    GROUP BY u.id, u.nome, u.email, u.senha, u.perfil, u.ativo, u.created_at, u.updated_at
    ORDER BY u.nome
");
$usuarios = $stmt->fetchAll();

// Buscar usuário para edição
$usuario_edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $usuario_edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - <?= SITE_NAME ?></title>
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
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
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
                        <a class="nav-link" href="categorias.php">
                            <i class="fas fa-tags me-2"></i>Categorias
                        </a>
                        <a class="nav-link active" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>Usuários
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">Gerenciar Usuários</h2>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#usuarioModal">
                            <i class="fas fa-plus me-2"></i>Novo Usuário
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

                <!-- Estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <div class="card-body">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <h4><?= count($usuarios) ?></h4>
                                <p class="mb-0">Total de Usuários</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                            <div class="card-body">
                                <i class="fas fa-user-shield fa-2x mb-2"></i>
                                <h4><?= count(array_filter($usuarios, fn($u) => $u['perfil'] == 'admin')) ?></h4>
                                <p class="mb-0">Administradores</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                            <div class="card-body">
                                <i class="fas fa-user fa-2x mb-2"></i>
                                <h4><?= count(array_filter($usuarios, fn($u) => $u['perfil'] == 'usuario')) ?></h4>
                                <p class="mb-0">Usuários</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                            <div class="card-body">
                                <i class="fas fa-file-invoice fa-2x mb-2"></i>
                                <h4><?= array_sum(array_column($usuarios, 'total_cotacoes')) ?></h4>
                                <p class="mb-0">Total Cotações</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Usuários -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Lista de Usuários</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Email</th>
                                        <th>Perfil</th>
                                        <th>Cotações</th>
                                        <th>Fechadas</th>
                                        <th>Economia Total</th>
                                        <th>Cadastrado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= $usuario['nome'] ?></div>
                                                    <?php if ($usuario['id'] == $_SESSION['usuario_id']): ?>
                                                        <small class="text-success"><i class="fas fa-user-check me-1"></i>Você</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="mailto:<?= $usuario['email'] ?>"><?= $usuario['email'] ?></a>
                                        </td>
                                        <td>
                                            <?php if ($usuario['perfil'] == 'admin'): ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-user-shield me-1"></i>Admin
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-user me-1"></i>Usuário
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $usuario['total_cotacoes'] ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?= $usuario['cotacoes_fechadas'] ?></span>
                                        </td>
                                        <td>
                                            <?php if ($usuario['economia_total'] > 0): ?>
                                                <span class="text-success fw-bold">
                                                    <?= formatarMoeda($usuario['economia_total']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($usuario['created_at'])) ?></td>
                                        <td>
                                            <a href="?edit=<?= $usuario['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary btn-action" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                            <a href="?inativar=<?= $usuario['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger btn-action" 
                                               title="Inativar"
                                               onclick="return confirm('Tem certeza que deseja inativar este usuário?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
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

    <!-- Modal Usuário -->
    <div class="modal fade" id="usuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>
                        <?= $usuario_edit ? 'Editar Usuário' : 'Novo Usuário' ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="usuarioForm">
                        <?php if ($usuario_edit): ?>
                            <input type="hidden" name="id" value="<?= $usuario_edit['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user me-2"></i>Nome Completo *
                            </label>
                            <input type="text" class="form-control" name="nome" 
                                   value="<?= $usuario_edit['nome'] ?? '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-envelope me-2"></i>Email *
                            </label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?= $usuario_edit['email'] ?? '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-lock me-2"></i>Senha <?= $usuario_edit ? '(deixe em branco para manter)' : '*' ?>
                            </label>
                            <input type="password" class="form-control" name="senha" 
                                   <?= !$usuario_edit ? 'required' : '' ?>>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user-tag me-2"></i>Perfil *
                            </label>
                            <select class="form-select" name="perfil" required>
                                <option value="usuario" <?= ($usuario_edit['perfil'] ?? '') == 'usuario' ? 'selected' : '' ?>>
                                    Usuário
                                </option>
                                <option value="admin" <?= ($usuario_edit['perfil'] ?? '') == 'admin' ? 'selected' : '' ?>>
                                    Administrador
                                </option>
                            </select>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?= $usuario_edit ? 'Atualizar' : 'Cadastrar' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($usuario_edit): ?>
        // Abrir modal automaticamente se estiver editando
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('usuarioModal')).show();
        });
        <?php endif; ?>
    </script>
</body>
</html>