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
    <title>Cadastrar Produto - SouthRock</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/produtos.css">
</head>
<body>
    <!-- Layout com sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">SR</div>
        <a href="dashboard.php">
            <i class="bi bi-speedometer2 icon"></i>
            <span class="text">Dashboard</span>
        </a>
        <a href="produtos.php" class="active">
            <i class="bi bi-box-fill icon"></i>
            <span class="text">Produtos</span>
        </a>
        <a href="usuarios.php">
            <i class="bi bi-people-fill icon"></i>
            <span class="text">Usuários</span>
        </a>
        <a href="relatorios.php">
            <i class="bi bi-file-earmark-bar-graph-fill icon"></i>
            <span class="text">Relatórios</span>
        </a>
        <div style="margin-top: auto;">
            <a href="logout.php">
                <i class="bi bi-box-arrow-right icon"></i>
                <span class="text">Logout</span>
            </a>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid px-4 py-4">
            <div class="row mb-4">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div class="dashboard-header">
                        <div class="painel-titulo">
                            <i class="bi bi-plus-circle me-2"></i>Cadastrar Novo Produto
                        </div>
                        <div class="user-info">
                            Bem-vindo, <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensagem de sucesso ou erro -->
            <?php if ($mensagem): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($mensagem); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card estatistica-card">
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="sku" class="form-label">SKU</label>
                                    <input type="number" class="form-control" id="sku" name="sku" value="<?php echo htmlspecialchars($sku); ?>" placeholder="Digite o código SKU" required>
                                </div>
                                <div class="mb-3">
                                    <label for="produto" class="form-label">Nome do Produto</label>
                                    <input type="text" class="form-control" id="produto" name="produto" value="<?php echo htmlspecialchars($produto); ?>" placeholder="Digite o nome do produto" required>
                                </div>
                                <div class="mb-3">
                                    <label for="grupo" class="form-label">Categoria</label>
                                    <input type="text" class="form-control" id="grupo" name="grupo" value="<?php echo htmlspecialchars($grupo); ?>" placeholder="Digite a categoria do produto" required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" name="cadastrar" class="btn btn-success">
                                        <i class="bi bi-save me-1"></i>Cadastrar Produto
                                    </button>
                                    <a href="produtos.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>Voltar para a lista de produtos
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="sistema-info text-center mt-3">
                Sistema de Gerenciamento SouthRock © <?php echo date('Y'); ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS e Dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Fecha a conexão
$conn->close();
?>