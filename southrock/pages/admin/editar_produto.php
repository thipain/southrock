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

$sku_original = '';
$produto = '';
$grupo = '';
$mensagem = '';
$mensagem_tipo = '';
$resultado_fetch = null; // Para verificar se o produto foi encontrado

if (isset($_GET['sku'])) {
    $sku_original = $_GET['sku'];

    $sqlSelect = "SELECT produto, grupo FROM produtos WHERE sku = ?";
    $stmtSelect = $conn->prepare($sqlSelect);
    if ($stmtSelect) {
        $stmtSelect->bind_param('s', $sku_original);
        $stmtSelect->execute();
        $resultado_fetch = $stmtSelect->get_result(); // Atribui a $resultado_fetch

        if ($resultado_fetch && $resultado_fetch->num_rows > 0) {
            $row = $resultado_fetch->fetch_assoc();
            $produto = $row['produto'];
            $grupo = $row['grupo'];
        } else {
            $mensagem = "Produto não encontrado.";
            $mensagem_tipo = "danger";
        }
        $stmtSelect->close();
    } else {
        $mensagem = "Erro ao preparar a consulta do produto.";
        $mensagem_tipo = "danger";
    }
} else {
    $_SESSION['flash_message'] = ['type' => 'warning', 'text' => 'SKU do produto não fornecido.'];
    header("Location: produtos.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar'])) {
    if ($resultado_fetch && $resultado_fetch->num_rows > 0) { // Apenas processar se o produto original foi carregado
        $produto_novo = trim($_POST['produto']);
        $grupo_novo = trim($_POST['grupo']);

        if (empty($produto_novo) || empty($grupo_novo)) {
            $mensagem = "Nome do produto e categoria são obrigatórios.";
            $mensagem_tipo = "danger";
            $produto = $produto_novo; 
            $grupo = $grupo_novo;
        } else {
            $sqlUpdate = "UPDATE produtos SET produto = ?, grupo = ? WHERE sku = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            if ($stmtUpdate) {
                $stmtUpdate->bind_param('sss', $produto_novo, $grupo_novo, $sku_original);

                if ($stmtUpdate->execute()) {
                    $mensagem = "Produto atualizado com sucesso!";
                    $mensagem_tipo = "success";
                    $produto = $produto_novo;
                    $grupo = $grupo_novo;
                     // Considerar adicionar um redirecionamento ou mensagem flash para a página de produtos após sucesso
                    $_SESSION['flash_message'] = ['type' => 'success', 'text' => $mensagem];
                    header("Location: produtos.php?highlight_sku=" . urlencode($sku_original)); // Redireciona para destacar o produto
                    exit();
                } else {
                    $mensagem = "Erro ao atualizar produto: " . $stmtUpdate->error;
                    $mensagem_tipo = "danger";
                    $produto = $produto_novo; 
                    $grupo = $grupo_novo;
                }
                $stmtUpdate->close();
            } else {
                $mensagem = "Erro ao preparar a atualização do produto.";
                $mensagem_tipo = "danger";
            }
        }
    } else {
        $mensagem = "Não é possível atualizar. Produto original não encontrado ou ID inválido.";
        $mensagem_tipo = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto - SouthRock</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <?php
        if (file_exists(__DIR__ . '/../../includes/header_com_menu.php')) {
            include __DIR__ . '/../../includes/header_com_menu.php';
        }
    ?>
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/editar_produto.css">
</head>
<body class="hcm-body-fixed-header">

    <div class="hcm-main-content">
        <div class="container py-4">
            
            <div class="row mb-4">
                <div class="col-md-8 mx-auto"> 
                    <div class="painel-titulo"> 
                        <i class="bi bi-pencil-fill me-2"></i>Editar Produto
                    </div>
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
                        <div class="card-header-flex p-3">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-box me-2"></i>SKU: <?php echo htmlspecialchars($sku_original); ?>
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <?php if ($resultado_fetch && $resultado_fetch->num_rows > 0): ?>
                            <form method="POST" action="editar_produto.php?sku=<?php echo htmlspecialchars($sku_original); ?>">
                                <div class="mb-3">
                                    <label for="produto" class="form-label">Nome do Produto</label>
                                    <input type="text" name="produto" id="produto" value="<?php echo htmlspecialchars($produto); ?>" class="form-control" placeholder="Nome do Produto" required>
                                </div>
                                <div class="mb-3">
                                    <label for="grupo" class="form-label">Categoria</label>
                                    <input type="text" name="grupo" id="grupo" value="<?php echo htmlspecialchars($grupo); ?>" class="form-control" placeholder="Categoria" required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" name="atualizar" class="btn btn-success">
                                        <i class="bi bi-check-circle me-1"></i>Atualizar Produto
                                    </button>
                                    <a href="produtos.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>Voltar para a lista de produtos
                                    </a>
                                </div>
                            </form>
                            <?php elseif ($mensagem_tipo !== "danger" || $mensagem === "Produto não encontrado."): // Para o caso de mensagem inicial "Produto não encontrado" ?>
                                <a href="produtos.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i>Voltar para a lista de produtos
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="sistema-info text-center mt-4">
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
                if(bsAlert) {
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
if(isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>