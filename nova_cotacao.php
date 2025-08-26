<?php
require_once 'config.php';
verificarLogin();

$erro = '';
$sucesso = '';

// Buscar categorias para o select
$stmt = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome");
$categorias = $stmt->fetchAll();

if ($_POST) {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $categoria_id = $_POST['categoria_id'];
    $valor_inicial = str_replace(['R$ ', '.', ','], ['', '', '.'], $_POST['valor_inicial']);
    $data_vencimento = $_POST['data_vencimento'];
    $observacoes = $_POST['observacoes'];
    
    // Gerar número da cotação
    $numero_cotacao = gerarNumeroCotacao();
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cotacoes (numero_cotacao, titulo, descricao, categoria_id, usuario_id, 
                                valor_inicial, data_vencimento, observacoes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente')
        ");
        $stmt->execute([
            $numero_cotacao, $titulo, $descricao, $categoria_id, $_SESSION['usuario_id'],
            $valor_inicial, $data_vencimento, $observacoes
        ]);
        
        $sucesso = "Cotação criada com sucesso! Número: $numero_cotacao";
        
        // Limpar formulário
        $_POST = [];
        
    } catch (Exception $e) {
        $erro = "Erro ao criar cotação: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Cotação - <?= SITE_NAME ?></title>
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
                    <h2 class="fw-bold">Nova Cotação</h2>
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
                        <a href="cotacoes.php" class="alert-link ms-2">Ver todas as cotações</a>
                    </div>
                <?php endif; ?>

                <!-- Formulário -->
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Criar Nova Cotação</h5>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-heading me-2"></i>Título da Cotação *
                                            </label>
                                            <input type="text" class="form-control" name="titulo" 
                                                   value="<?= $_POST['titulo'] ?? '' ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-tags me-2"></i>Categoria *
                                            </label>
                                            <select class="form-select" name="categoria_id" required>
                                                <option value="">Selecione uma categoria</option>
                                                <?php foreach ($categorias as $categoria): ?>
                                                <option value="<?= $categoria['id'] ?>" 
                                                        <?= ($_POST['categoria_id'] ?? '') == $categoria['id'] ? 'selected' : '' ?>>
                                                    <?= $categoria['nome'] ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-file-alt me-2"></i>Descrição
                                        </label>
                                        <textarea class="form-control" name="descricao" rows="3" 
                                                  placeholder="Descreva detalhadamente o produto ou serviço a ser cotado..."><?= $_POST['descricao'] ?? '' ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-dollar-sign me-2"></i>Valor Inicial *
                                            </label>
                                            <input type="text" class="form-control" name="valor_inicial" 
                                                   value="<?= $_POST['valor_inicial'] ?? '' ?>" 
                                                   placeholder="R$ 0,00" required id="valor_inicial">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-calendar me-2"></i>Data de Vencimento
                                            </label>
                                            <input type="date" class="form-control" name="data_vencimento" 
                                                   value="<?= $_POST['data_vencimento'] ?? '' ?>">
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-sticky-note me-2"></i>Observações
                                        </label>
                                        <textarea class="form-control" name="observacoes" rows="3" 
                                                  placeholder="Observações adicionais sobre a cotação..."><?= $_POST['observacoes'] ?? '' ?></textarea>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="cotacoes.php" class="btn btn-outline-secondary me-md-2">
                                            <i class="fas fa-times me-2"></i>Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Criar Cotação
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Formatação de moeda
        document.getElementById('valor_inicial').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (value/100).toFixed(2) + '';
            value = value.replace(".", ",");
            value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
            value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
            e.target.value = 'R$ ' + value;
        });
    </script>
</body>
</html>