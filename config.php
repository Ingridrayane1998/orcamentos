<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'facilities_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações gerais
define('SITE_NAME', 'Sistema Facilities');
define('SITE_URL', 'http://localhost/facilities_system');

// Conexão com o banco
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Iniciar sessão
session_start();

// Função para verificar login
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Função para formatar moeda
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

// Função para gerar número de cotação
function gerarNumeroCotacao() {
    return 'COT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}
?>