<?php
// Configura exibição de erros para desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicia a sessão para verificar login
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Inclui o arquivo de conexão
require_once '../../includes/db.php';

// Verifica se um produto deve ser excluído
if (isset($_GET['delete'])) {
    $skuToDelete = $_GET['delete'];

    // Prepara a consulta SQL para excluir o produto
    $sqlDelete = "DELETE FROM produtos WHERE sku = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param('i', $skuToDelete);

    if ($stmtDelete->execute()) {
        $mensagem = "Produto excluído com sucesso!";
    } else {
        $mensagem = "Erro ao excluir produto: " . $stmtDelete->error;
    }

    $stmtDelete->close();
}

$searchTerm = '';
if (isset($_POST['search'])) {
    $searchTerm = $_POST['search'];
}

try {
    // Prepara a consulta SQL para buscar produtos com base no termo de pesquisa
    $sql = "SELECT sku, produto, grupo FROM produtos WHERE 
            sku LIKE ? OR 
            produto LIKE ? OR 
            grupo LIKE ? 
            ORDER BY sku";

    // Prepara a declaração
    $stmt = $conn->prepare($sql);
    
    // Adiciona os parâmetros de pesquisa
    $likeTerm = '%' . $searchTerm . '%';
    $stmt->bind_param('sss', $likeTerm, $likeTerm, $likeTerm);

    // Executa a consulta
    $stmt->execute();
    $resultado = $stmt->get_result();

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Lista de Produtos - SouthRock</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <link rel="stylesheet" href="../../css/dashboard.css">
        <link rel="stylesheet" href="../../css/produtos.css">
    </head>
    <body>
       <div class="sidebar">
        <div>
            <div class="sidebar-header">
                <i class="fas fa-bars icon"></i><span class="text">Menu</span>
            </div>
            <a href="dashboard.php"><i class="fas fa-home icon"></i><span class="text">Início</span></a>
            <a href="pedidos.php"><i class="fas fa-shopping-cart icon"></i><span class="text">Pedidos</span></a>
            <a href="produtos.php"class="active"><i class="fas fa-box icon"></i><span class="text">Produtos</span></a>
            <a href="usuarios.php"><i class="fas fa-users icon"></i><span class="text">Usuários</span></a>
        </div>
        <a href="../../logout/logout.php"><i class="fas fa-sign-out-alt icon"></i><span class="text">Sair</span></a>
    </div>

        <div class="content">
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

                <?php if (isset($mensagem)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($mensagem); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="card estatistica-card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <form method="POST" action="" class="mb-3 p-3">
                                <div class="input-group">
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" class="form-control" placeholder="Pesquisar por SKU, Nome ou Categoria">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search me-1"></i>Pesquisar
                                    </button>
                                </div>
                            </form>
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>SKU</th>
                                        <th>Nome do Produto</th>
                                        <th>Categoria</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Itera sobre os resultados e exibe cada produto
                                    if ($resultado->num_rows > 0) {
                                        while ($produto = $resultado->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($produto['sku']) . "</td>";
                                            echo "<td>" . htmlspecialchars($produto['produto']) . "</td>";
                                            echo "<td>" . htmlspecialchars($produto['grupo']) . "</td>";
                                            echo "<td class='text-center'>
                                                    <div class='btn-group' role='group'>
                                                        <a href='editar_produto.php?sku=" . htmlspecialchars($produto['sku']) . "' class='btn btn-sm btn-outline-primary'>
                                                            <i class='bi bi-pencil'></i>
                                                        </a>
                                                        <button onclick='confirmDelete(" . htmlspecialchars($produto['sku']) . ")' class='btn btn-sm btn-outline-danger'>
                                                            <i class='bi bi-trash'></i>
                                                        </button>
                                                    </div>
                                                </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center py-3'>Nenhum produto encontrado.</td></tr>";
                                    }
                                    ?>
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

        <!-- Bootstrap JS e Dependências -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- SweetAlert2 para confirmação de exclusão -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            function confirmDelete(sku) {
                Swal.fire({
                    title: 'Confirmar exclusão',
                    text: "Você realmente deseja excluir este produto?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `produtos.php?delete=${sku}`;
                    }
                });
            }
        </script>
    </body>
    </html>
    <?php
    // Fecha a conexão apenas no final do script
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Tratamento de erro
    echo "<div class='alert alert-danger'>Erro: " . $e->getMessage() . "</div>";
}
?>