<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include '../../includes/db.php';

$sku = '';
$produto = '';
$grupo = '';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar'])) {
    // Captura os dados do formulário de cadastro
    $sku = $_POST['sku'];
    $produto = $_POST['produto'];
    $grupo = $_POST['grupo'];

    // Prepara a consulta SQL para inserir um novo produto
    $sqlInsert = "INSERT INTO produtos (sku, produto, grupo) VALUES (?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param('iss', $sku, $produto, $grupo);

    if ($stmtInsert->execute()) {
        $mensagem = "Produto cadastrado com sucesso!";
    } else {
        $mensagem = "Erro ao cadastrar produto: " . $stmtInsert->error;
    }

    $stmtInsert->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Produto - Dashboard</title>
    <style>
        body {
            text-align: center;
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .form-container {
            max-width: 400px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .form-container input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        .button:hover {
            background-color: #45a049;
        }
        .mensagem {
            color: green;
            margin-bottom: 20px;
        }
        .erro {
            color: red;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Cadastrar Novo Produto</h1>

    <!-- Mensagem de sucesso ou erro -->
    <?php if ($mensagem): ?>
        <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" action="">
            <input type="number" name="sku" value="<?php echo htmlspecialchars($sku); ?>" placeholder="SKU" required>
            <input type="text" name="produto" value="<?php echo htmlspecialchars($produto); ?>" placeholder="Nome do Produto" required>
            <input type="text" name="grupo" value="<?php echo htmlspecialchars($grupo); ?>" placeholder="Categoria" required>
            <button type="submit" name="cadastrar" class="button">Cadastrar</button>
        </form>
    </div>

    <br>
    <a href="produtos.php">Voltar para a lista de produtos</a>
</body>
</html>

<?php
// Fecha a conexão
$conn->close();
?>