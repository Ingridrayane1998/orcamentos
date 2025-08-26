<?php
require_once 'config.php';
verificarLogin();

$erro = '';
$sucesso = '';

// Atualizar perfil
if ($_POST) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    try {
        // Verificar se email já existe (exceto o próprio usuário)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['usuario_id']]);
        if ($stmt->fetch()) {
            throw new Exception("Este email já está sendo usado por outro usuário.");
        }
        
        // Se forneceu senha atual, verificar e alterar senha
        if ($senha_atual) {
            $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $usuario = $stmt->fetch();
            
            if (md5($senha_atual) != $usuario['senha']) {
                throw new Exception("Senha atual incorreta.");
            }
            
            if ($nova_senha != $confirmar_senha) {
                throw new Exception("Nova senha e confirmação não coincidem.");
            }
            
            if (strlen($nova_senha) < 6) {
                throw new Exception("Nova senha deve ter pelo menos 6 caracteres.");
            }
            
            // Atualizar com nova senha
            $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, senha=MD5(?) WHERE id=?");
            $stmt->execute([$nome, $email, $nova_senha, $_SESSION['usuario_id']]);
        } else {
            // Atualizar sem alterar senha
            $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=? WHERE id=?");
            $stmt->execute([$nome, $email, $_SESSION['usuario_id']]);
        }
        
        // Atualizar sessão
        $_SESSION['usuario_nome'] = $nome;
        
        $sucesso = "Perfil atualizado com sucesso!";
        
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

// Buscar estatísticas do usuário
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_cotacoes,
        SUM(CASE WHEN status = 'fechado' THEN 1 ELSE 0 END) as cotacoes_fechadas,
        SUM(economia) as economia_total,
        AVG(valor_inicial) as valor_medio
    FROM cotacoes 
    WHERE usuario_id = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - <?= SITE_NAME ?></title>
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
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto 20px;
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
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <h2 class="fw-bold mb-4 text-center">Meu Perfil</h2>

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
                            <!-- Informações do Perfil -->
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-body text-center">
                                        <div class="profile-avatar">
                                            <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                                        </div>
                                        <h5 class="fw-bold"><?= $usuario['nome'] ?></h5>
                                        <p class="text-muted"><?= $usuario['email'] ?></p>
                                        <span class="badge bg-<?= $usuario['perfil'] == 'admin' ? 'danger' : 'primary' ?> mb-3">
                                            <?= $usuario['perfil'] == 'admin' ? 'Administrador' : 'Usuário' ?>
                                        </span>
                                        <div class="text-muted">
                                            <small>
                                                <i class="fas fa-calendar me-2"></i>
                                                Membro desde <?= date('m/Y', strtotime($usuario['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estatísticas -->
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-chart-line me-2"></i>Minhas Estatísticas
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-6 mb-3">
                                                <div class="h4 text-primary"><?= $stats['total_cotacoes'] ?></div>
                                                <small class="text-muted">Cotações</small>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <div class="h4 text-success"><?= $stats['cotacoes_fechadas'] ?></div>
                                                <small class="text-muted">Fechadas</small>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <div class="h5 text-success">
                                                    <?= $stats['economia_total'] ? formatarMoeda($stats['economia_total']) : 'R$ 0,00' ?>
                                                </div>
                                                <small class="text-muted">Economia Total</small>
                                            </div>
                                            <div class="col-12">
                                                <div class="h6 text-info">
                                                    <?= $stats['valor_medio'] ? formatarMoeda($stats['valor_medio']) : 'R$ 0,00' ?>
                                                </div>
                                                <small class="text-muted">Valor Médio</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulário de Edição -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user-edit me-2"></i>Editar Informações
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">
                                                    <i class="fas fa-user me-2"></i>Nome Completo *
                                                </label>
                                                <input type="text" class="form-control" name="nome" 
                                                       value="<?= htmlspecialchars($usuario['nome']) ?>" required>
                                            </div>

                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">
                                                    <i class="fas fa-envelope me-2"></i>Email *
                                                </label>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?= htmlspecialchars($usuario['email']) ?>" required>
                                            </div>

                                            <hr>
                                            <h6 class="fw-bold mb-3">
                                                <i class="fas fa-lock me-2"></i>Alterar Senha
                                                <small class="text-muted">(opcional)</small>
                                            </h6>

                                            <div class="mb-3">
                                                <label class="form-label">Senha Atual</label>
                                                <input type="password" class="form-control" name="senha_atual" 
                                                       placeholder="Digite sua senha atual">
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Nova Senha</label>
                                                    <input type="password" class="form-control" name="nova_senha" 
                                                           placeholder="Digite a nova senha">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Confirmar Nova Senha</label>
                                                    <input type="password" class="form-control" name="confirmar_senha" 
                                                           placeholder="Confirme a nova senha">
                                                </div>
                                            </div>

                                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                                <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">
                                                    <i class="fas fa-times me-2"></i>Cancelar
                                                </a>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Salvar Alterações
                                                </button>
                                            </div>
                                        </form>
                                    </div>
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