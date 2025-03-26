<?php
// Configura exibição de erros para desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclui o arquivo de conexão
require_once '../includes/db.php';

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
        <title>Lista de Produtos - SouthRock</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            body {
                background-color: #f4f6f9;
            }
            .table-hover tbody tr:hover {
                background-color: rgba(0,0,0,0.075);
            }
            .card-custom {
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
        </style>
    </head>
    <body>
        <div class="container-fluid px-4 py-4">
            <div class="row mb-4 align-items-center">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <h1 class="h2 text-primary">
                        <i class="bi bi-box-fill me-2"></i>Gerenciar Produtos
                    </h1>
                    <div>
                        <a href="cadastrar_produto.php" class="btn btn-success me-2">
                            <i class="bi bi-plus-circle me-1"></i>Novo Produto
                        </a>
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left me-1"></i>Voltar ao Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <div class="card card-custom border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <form method="POST" action="" class="mb-3">
                            <div class="input-group">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" class="form-control" placeholder="Pesquisar por SKU, Nome ou Categoria">
                                <button class="btn btn-outline-secondary" type="submit">Pesquisar</button>
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
                                ?>
                            </tbody>
                        </table>
                    </div>
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
                    title: 'Tem certeza?',
                    text: 'Você não poderá reverter esta ação!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '?delete=' + sku;
                    }
                });
            }
        </script>
    </body>
    </html>
    <?php

    if ($resultado->num_rows === 0) {
        echo "<div class='alert alert-warning'>Nenhum produto encontrado.</div>";
    }

    // Fecha a conexão apenas no final do script
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Tratamento de erro
    echo "Erro: " . $e->getMessage();
}
?>