<?php
require_once 'config.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_POST) {
    $email = $_POST['email'];
    $senha = md5($_POST['senha']);
    
    $stmt = $pdo->prepare("SELECT id, nome, perfil FROM usuarios WHERE email = ? AND senha = ? AND ativo = 1");
    $stmt->execute([$email, $senha]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_perfil'] = $usuario['perfil'];
        header('Location: dashboard.php');
        exit;
    } else {
        $erro = 'Email ou senha inválidos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-building fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold"><?= SITE_NAME ?></h3>
                        <p class="text-muted">Sistema de Cotações</p>
                    </div>
                    
                    <?php if ($erro): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= $erro ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-envelope me-2"></i>Email
                            </label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-lock me-2"></i>Senha
                            </label>
                            <input type="password" class="form-control" name="senha" required>
                        </div>
                        
                        <button type="submit" class="btn btn-login text-white w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar
                        </button>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <small class="text-muted">
                            <strong>Usuário de teste:</strong><br>
                            admin@facilities.com / admin123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>