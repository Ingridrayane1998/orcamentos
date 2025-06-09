<?php
// Configurar conexão com banco
$host = "localhost";
$dbname = "controle_orcamentos";
$user = "root"; // usuário padrão do XAMPP
$pass = "";     // senha padrão é vazia

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Inserir dados ao enviar formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = $_POST["data_orcamento"];
    $categoria = $_POST["categoria"];
    $fornecedor = $_POST["fornecedor"];
    $valor_proposto = $_POST["valor_proposto"];
    $valor_final = $_POST["valor_final"];
    $status = $_POST["status"];

    $stmt = $conn->prepare("INSERT INTO orcamentos (data_orcamento, categoria, fornecedor, valor_proposto, valor_final, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddss", $data, $categoria, $fornecedor, $valor_proposto, $valor_final, $status);
    $stmt->execute();
    $stmt->close();

    echo "<p class='success'>✅ Orçamento cadastrado com sucesso!</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<title>Cadastro de Orçamentos</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f7f9fc;
        color: #333;
        padding: 20px;
        display: flex;
        justify-content: center;
    }
    .container {
        background: white;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgb(0 0 0 / 0.1);
        width: 400px;
    }
    h1 {
        text-align: center;
        margin-bottom: 25px;
        color: #2c3e50;
    }
    form label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
    }
    form input[type="text"],
    form input[type="date"],
    form input[type="number"],
    form select {
        width: 100%;
        padding: 10px 12px;
        margin-bottom: 20px;
        border: 1.8px solid #d1d9e6;
        border-radius: 6px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }
    form input[type="text"]:focus,
    form input[type="date"]:focus,
    form input[type="number"]:focus,
    form select:focus {
        border-color: #2980b9;
        outline: none;
    }
    button {
        width: 100%;
        background-color: #2980b9;
        border: none;
        padding: 14px 0;
        color: white;
        font-weight: 700;
        font-size: 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    button:hover {
        background-color: #1c5980;
    }
    a.dashboard-link {
        display: inline-block;
        margin-bottom: 20px;
        background-color: #27ae60;
        color: white !important;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }
    a.dashboard-link:hover {
        background-color: #1f874b;
    }
    p.success {
        background-color: #dff0d8;
        color: #3c763d;
        padding: 10px 15px;
        border-radius: 6px;
        text-align: center;
        margin-bottom: 20px;
        font-weight: 600;
    }
</style>
</head>
<body>
<div class="container">

<a href="dashboard_orcamentos.php" class="dashboard-link">📊 Ver Dashboard</a>

<h1>Cadastro de Orçamentos</h1>
<form method="POST" action="">
    <label for="data_orcamento">Data do Orçamento:</label>
    <input type="date" id="data_orcamento" name="data_orcamento" required />

    <label for="categoria">Categoria:</label>
    <input type="text" id="categoria" name="categoria" required />

    <label for="fornecedor">Fornecedor:</label>
    <input type="text" id="fornecedor" name="fornecedor" />

    <label for="valor_proposto">Valor Proposto:</label>
    <input type="number" step="0.01" id="valor_proposto" name="valor_proposto" required />

    <label for="valor_final">Valor Final:</label>
    <input type="number" step="0.01" id="valor_final" name="valor_final" required />

    <label for="status">Status:</label>
    <select id="status" name="status" required>
        <option value="Aprovado">Aprovado</option>
        <option value="Reprovado">Reprovado</option>
        <option value="Pendente">Pendente</option>
    </select>

    <button type="submit">Cadastrar Orçamento</button>
</form>
</div>
</body>
</html>
