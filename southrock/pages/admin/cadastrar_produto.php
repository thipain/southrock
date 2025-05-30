<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit();
}

include '../../includes/db.php';

$path_to_css_folder_from_page = '../../css/';
$logo_image_path_from_page = '../../images/zamp.png';
$logout_script_path_from_page = '../../logout/logout.php';

$link_dashboard = 'dashboard.php';
$link_pedidos_admin = 'pedidos.php';
$link_produtos_admin = 'produtos.php';
$link_usuarios_admin = 'usuarios.php';
$link_cadastro_usuario_admin = 'cadastro_usuario.php';

$sku = '';
$produto = '';
$grupo = '';
$mensagem = '';
$mensagem_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar'])) {
    $sku = $_POST['sku'];
    $produto = trim($_POST['produto']);
    $grupo = trim($_POST['grupo']);

    if (empty($sku) || empty($produto) || empty($grupo)) {
        $mensagem = "Todos os campos são obrigatórios.";
        $mensagem_tipo = "danger";
    } else {
        $sql_check_sku = "SELECT sku FROM produtos WHERE sku = ?";
        $stmt_check_sku = $conn->prepare($sql_check_sku);
        $stmt_check_sku->bind_param('s', $sku);
        $stmt_check_sku->execute();
        $stmt_check_sku->store_result();

        if ($stmt_check_sku->num_rows > 0) {
            $mensagem = "Erro ao cadastrar produto: O SKU informado ('" . htmlspecialchars($sku) . "') já existe.";
            $mensagem_tipo = "danger";
        } else {
            $sqlInsert = "INSERT INTO produtos (sku, produto, grupo) VALUES (?, ?, ?)";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->bind_param('sss', $sku, $produto, $grupo);

            if ($stmtInsert->execute()) {
                $mensagem = "Produto cadastrado com sucesso!";
                $mensagem_tipo = "success";
                $sku = '';
                $produto = '';
                $grupo = '';
            } else {
                $mensagem = "Erro ao cadastrar produto: " . $stmtInsert->error;
                $mensagem_tipo = "danger";
            }
            $stmtInsert->close();
        }
        $stmt_check_sku->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Produto - SouthRock</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php
        if (file_exists(__DIR__ . '/../../includes/header_com_menu.php')) {
            include __DIR__ . '/../../includes/header_com_menu.php';
        }
    ?>
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/produtos.css">
</head>
<body class="hcm-body-fixed-header">
    <div class="hcm-main-content">
        <div class="container-fluid px-4 py-4">
            <div class="row mb-4">
                <div class="col-md-8 mx-auto">
                    <div class="painel-titulo">
                        <i class="bi bi-plus-circle me-2"></i>Cadastrar Novo Produto
                    </div>
                    <hr class="barrinha">
                </div>
            </div>
           
            <?php if ($mensagem): ?>
                <div class="row mb-3">
                    <div class="col-md-8 mx-auto">
                        <div class="alert alert-<?php echo htmlspecialchars($mensagem_tipo ?: 'info'); ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($mensagem); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card estatistica-card">
                        <div class="card-body p-4">
                            <form method="POST" action="cadastrar_produto.php">
                                <div class="mb-3">
                                    <label for="sku" class="form-label">SKU</label>
                                    <input type="text" class="form-control" id="sku" name="sku" value="<?php echo htmlspecialchars($sku); ?>" placeholder="Digite o código SKU" required>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var alertElement = document.querySelector('.alert');
            if (alertElement && typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                var bsAlert = bootstrap.Alert.getOrCreateInstance(alertElement);
                 if(bsAlert){
                    setTimeout(function() {
                        bsAlert.close();
                    }, 5000);
                }
            }
        });
    </script>
</body>
</html>
<?php
if(isset($conn)) {
    $conn->close();
}
?>