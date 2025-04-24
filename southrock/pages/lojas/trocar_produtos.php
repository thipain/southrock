<?php
// Configura exibição de erros para desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verifica se é uma requisição AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    // Inclui o arquivo de conexão
    require_once '../../includes/db.php';
    
    $searchTerm = isset($_GET['term']) ? $_GET['term'] : '';
    $response = array('success' => false, 'products' => array());
    
    try {
        if (trim($searchTerm) !== '') {
            // Prepara a consulta SQL
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
            
            // Converte os resultados para um array
            $products = array();
            while ($produto = $resultado->fetch_assoc()) {
                $products[] = $produto;
            }
            
            $response['success'] = true;
            $response['products'] = $products;
            
            $stmt->close();
        }
        
        // Retorna os resultados como JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        
        $conn->close();
        exit;
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Inclui o arquivo de conexão para a página principal
require_once '../../includes/db.php';

try {
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
        <link rel="stylesheet" href="../../css/trocar_produtos.css">
    </head>
    <body>
        <div class="container-fluid px-4 py-4">
            <div class="row mb-4 align-items-center">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <h1 class="h2 text-primary">
                        <i class="bi bi-box-fill me-2"></i>Lista de Produtos
                    </h1>
                    <div>
                        <a href="fazer_pedidos.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left me-1"></i>Voltar ao Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <div class="card card-custom border-0">
                <div class="card-body p-4">
                    <div class="mb-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" id="search-input" class="form-control" placeholder="Pesquisar por SKU, Nome ou Categoria">
                            <div id="search-loading" class="search-loading"></div>
                        </div>
                        <div class="search-indicator mt-2" id="search-indicator">
                            <i class="bi bi-info-circle me-2"></i>
                            Digite para começar a pesquisar
                        </div>
                    </div>
                    
                    <div id="results-container">
                        <div class="text-center py-5" id="initial-message">
                            <i class="bi bi-search" style="font-size: 48px; color: #adb5bd;"></i>
                            <p class="mt-3 text-muted">Digite o nome do produto, SKU ou grupo para realizar uma pesquisa</p>
                        </div>
                        
                        <div id="products-table-container" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>SKU</th>
                                            <th>Nome do Produto</th>
                                            <th>Categoria</th>
                                            <th class="text-center">Adicionar</th>
                                        </tr>
                                    </thead>
                                    <tbody id="products-table-body">
                                        <!-- Os resultados da pesquisa serão inseridos aqui via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning" id="no-results" style="display: none;">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Nenhum produto encontrado para "<strong id="search-term-display"></strong>".
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botão do carrinho -->
        <div class="cart-button" id="cart-btn">
            <div class="cart-icon"><i class="bi bi-cart"></i></div>
            <div class="cart-count" id="cart-count">0</div>
        </div>
        
        <!-- Overlay para fechar o carrinho ao clicar fora -->
        <div class="overlay" id="overlay"></div>
        
        <!-- Carrinho lateral -->
        <div class="cart-sidebar" id="cart-sidebar">
            <div class="cart-header">
                <div class="cart-title">Seu carrinho</div>
                <button class="close-cart" id="close-cart">&times;</button>
            </div>
            
            <div class="cart-items" id="cart-items">
                <!-- Os itens do carrinho serão inseridos dinamicamente via JavaScript -->
                <div class="empty-cart-message" id="empty-cart-message">
                    Seu carrinho está vazio
                </div>
            </div>
            
            <div class="cart-footer">
                <button class="checkout-btn" id="checkout-btn">Finalizar requisição</button>
            </div>
        </div>

        <!-- Bootstrap JS e Dependências -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- SweetAlert2 para notificações -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="../../js/trocar_produtos.js"></script>
        
    </body>
    </html>
    <?php
    
    $conn->close();

} catch (Exception $e) {
    // Tratamento de erro
    echo "Erro: " . $e->getMessage();
}
?>