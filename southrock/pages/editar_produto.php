<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include '../includes/db.php';

$sku = '';
$produto = '';
$grupo = '';
$mensagem = '';

// Verifica se o SKU foi passado na URL
if (isset($_GET['sku'])) {
    $sku = $_GET['sku'];

    // Prepara a consulta SQL para buscar o produto
    $sqlSelect = "SELECT produto, grupo FROM produtos WHERE sku = ?";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->bind_param('i', $sku);
    $stmtSelect->execute();
    $resultado = $stmtSelect->get_result();

    if ($resultado->num_rows > 0) {
        $row = $resultado->fetch_assoc();
        $produto = $row['produto'];
        $grupo = $row['grupo'];
    } else {
        $mensagem = "Produto não encontrado.";
    }

    $stmtSelect->close();
}

// Processa a atualização do produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar'])) {
    $produto = $_POST['produto'];
    $grupo = $_POST['grupo'];

    // Prepara a consulta SQL para atualizar o produto
    $sqlUpdate = "UPDATE produtos SET produto = ?, grupo = ? WHERE sku = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param('ssi', $produto, $grupo, $sku);

    if ($stmtUpdate->execute()) {
        $mensagem = "Produto atualizado com sucesso!";
    } else {
        $mensagem = "Erro ao atualizar produto: " . $stmtUpdate->error;
    }

    $stmtUpdate->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto - Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .card-custom {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid px-4 py-4">
        <h1 class="h2 text-primary">
            <i class="bi bi-pencil-fill me-2"></i>Editar Produto
        </h1>

        <!-- Mensagem de sucesso ou erro -->
        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <div class="card card-custom border-0">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="produto" class="form-label">Nome do Produto</label>
                        <input type="text" name="produto" id="produto" value="<?php echo htmlspecialchars($produto); ?>" class="form-control" placeholder="Nome do Produto" required>
                    </div>
                    <div class="mb-3">
                        <label for="grupo" class="form-label">Categoria</label>
                        <input type="text" name="grupo" id="grupo" value="<?php echo htmlspecialchars($grupo); ?>" class="form-control" placeholder="Categoria" required>
                    </div>
                    <button type="submit" name="atualizar" class="btn btn-success">Atualizar</button>
                </form>
            </div>
        </div>

        <br>
        <a href="produtos.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Voltar para a lista de produtos
        </a>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Fecha a conexão
$conn->close();
?>