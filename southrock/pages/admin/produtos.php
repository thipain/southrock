<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit();
}

if (isset($_GET['ajax_search_produtos']) && $_GET['ajax_search_produtos'] == 1) {
    require_once '../../includes/db.php'; 
    
    $output = '';
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
        
    if (empty($searchTerm)) {
        $output = "<tr><td colspan='4' class='text-center py-3'>Digite algo na busca para ver os produtos.</td></tr>";
    } else {
        $likeTerm = '%' . $searchTerm . '%';
        $sql = "SELECT sku, produto, grupo FROM produtos WHERE 
                CAST(sku AS CHAR) LIKE ? OR 
                produto LIKE ? OR 
                grupo LIKE ? 
                ORDER BY sku LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sss', $likeTerm, $likeTerm, $likeTerm);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 0) {
                while ($produto = $resultado->fetch_assoc()) {
                    $output .= "<tr>";
                    $output .= "<td>" . htmlspecialchars($produto['sku']) . "</td>";
                    $output .= "<td>" . htmlspecialchars($produto['produto']) . "</td>";
                    $output .= "<td>" . htmlspecialchars($produto['grupo']) . "</td>";
                    $output .= "<td class='text-center'>
                                    <div class='btn-group' role='group'>
                                        <a href='editar_produto.php?sku=" . htmlspecialchars($produto['sku']) . "' class='btn btn-sm btn-outline-primary'><i class='bi bi-pencil'></i></a>
                                        <button onclick='confirmDelete(\"" . htmlspecialchars(addslashes($produto['sku'])) . "\")' class='btn btn-sm btn-outline-danger'><i class='bi bi-trash'></i></button>
                                    </div>
                                </td>";
                    $output .= "</tr>";
                }
            } else {
                $output = "<tr><td colspan='4' class='text-center py-3'>Nenhum produto encontrado para \"".htmlspecialchars($searchTerm)."\".</td></tr>";
            }
            $stmt->close();
        } else {
            $output = "<tr><td colspan='4' class='text-center py-3'>Erro ao preparar a consulta.</td></tr>";
        }
    }
    if (isset($conn)) { 
        $conn->close(); 
    }
    echo $output;
    exit; 
}

require_once '../../includes/db.php'; 

$path_to_css_folder_from_page = '../../css/';
$logo_image_path_from_page = '../../images/zamp.png';
$logout_script_path_from_page = '../../logout/logout.php';

$link_dashboard = 'dashboard.php';
$link_pedidos_admin = 'pedidos.php';
$link_produtos_admin = 'produtos.php';
$link_usuarios_admin = 'usuarios.php';
$link_cadastro_usuario_admin = 'cadastro_usuario.php';

$mensagem_flash = ''; 
if (isset($_SESSION['flash_message'])) { 
    $mensagem_flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

if (isset($_GET['delete'])) {
    $skuToDelete = $_GET['delete'];
    $sqlDelete = "DELETE FROM produtos WHERE sku = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    if ($stmtDelete) {
        $stmtDelete->bind_param('s', $skuToDelete); 
        if ($stmtDelete->execute()) {
            $_SESSION['flash_message'] = "Produto SKU: " . htmlspecialchars($skuToDelete) . " excluído com sucesso!";
        } else {
            $_SESSION['flash_message'] = "Erro ao excluir produto: " . $stmtDelete->error;
        }
        $stmtDelete->close();
    } else {
        $_SESSION['flash_message'] = "Erro ao preparar para excluir produto.";
    }
    header("Location: produtos.php"); 
    exit();
}

$searchTermPageLoad = ''; 
if (isset($_GET['search'])) { 
    $searchTermPageLoad = trim($_GET['search']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Produtos - SouthRock</title>
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
    <link rel="stylesheet" href="../../css/produtos.css">
</head>
<body class="hcm-body-fixed-header">
    <div class="hcm-main-content">
        <div class="container-fluid px-4 py-4">
            <div class="row mb-4">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div class="dashboard-header">
                        <div class="painel-titulo">
                            <i class="bi bi-box-fill me-2"></i>Gerenciar Produtos
                        </div>
                    </div>
                    <div class="button-novo">
                        <a href="cadastrar_produto.php" class="btn btn-success me-2">
                            <i class="bi bi-plus-circle me-1"></i>Novo Produto
                        </a>
                    </div>
                </div>
            </div>

            <?php if (!empty($mensagem_flash)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($mensagem_flash); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card estatistica-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <div class="mb-3 p-3">
                            <input type="text" name="search" id="searchInput" value="<?php echo htmlspecialchars($searchTermPageLoad); ?>" class="form-control" placeholder="Pesquisar por SKU, Nome ou Categoria...">
                        </div>
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>SKU</th>
                                    <th>Nome do Produto</th>
                                    <th>Categoria</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <tr><td colspan="4" class="text-center py-3">Digite algo na busca para ver os produtos.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="sistema-info text-center mt-3">
                Sistema de Gerenciamento SouthRock © <?php echo date('Y'); ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(sku) {
            Swal.fire({
                title: 'Confirmar exclusão',
                text: "Você realmente deseja excluir este produto SKU: " + sku + "?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `produtos.php?delete=${encodeURIComponent(sku)}`;
                }
            });
        }

        $(document).ready(function() {
            var debounceTimer;
            function fetchProducts() {
                var searchTerm = $('#searchInput').val().trim();
                
                if (searchTerm.length === 0) {
                    $('#productsTableBody').html('<tr><td colspan="4" class="text-center py-3">Digite algo na busca para ver os produtos.</td></tr>');
                    return;
                }
                
                $('#productsTableBody').html('<tr><td colspan="4" class="text-center py-3"><i class="fas fa-spinner fa-spin me-2"></i>Carregando...</td></tr>');
                
                $.ajax({
                    url: 'produtos.php', 
                    type: 'GET', 
                    data: { 
                        ajax_search_produtos: 1,
                        term: searchTerm 
                    },
                    success: function(response) {
                        $('#productsTableBody').html(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Erro AJAX:", textStatus, errorThrown);
                        $('#productsTableBody').html('<tr><td colspan="4" class="text-center py-3">Erro ao buscar produtos. Verifique o console para detalhes.</td></tr>');
                    }
                });
            }

            $('#searchInput').on('keyup input', function(e) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(fetchProducts, 400); 
            });

            if ($('#searchInput').val().trim().length > 0) {
               fetchProducts(); 
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            var alertElement = document.querySelector('.alert-info.alert-dismissible');
            if (alertElement) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Alert && alertElement.querySelector('.btn-close')) { 
                     var bsAlert = bootstrap.Alert.getOrCreateInstance(alertElement);
                     if(bsAlert) { setTimeout(function() { bsAlert.close(); }, 5000); }
                } else if ($.fn.alert) { 
                    setTimeout(function() { $(alertElement).alert('close'); }, 5000);
                }
            }
        });
    </script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) { 
    $conn->close();
}
?>