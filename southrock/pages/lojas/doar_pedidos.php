<?php
// Configura exibição de erros para desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicia a sessão para acessar o ID do usuário logado e sua filial
session_start();

// Inclui o arquivo de conexão para a página principal
require_once '../../includes/db.php';

// Mock do ID do usuário logado e da filial de origem (substitua pela lógica de sessão real)
// ATENÇÃO: Em um ambiente real, você obteria esses valores de forma segura da sessão após o login.
$loggedInUserId = 1; // Ex: $_SESSION['user_id']
$loggedInUserBranchId = 2; // Ex: $_SESSION['branch_id'] (ID da filial associada ao usuário logado, se aplicável)

// Se o usuário logado for de uma filial (tipo_usuario = 2 e eh_filial = TRUE),
// a filial de origem será a própria filial do usuário.
// Se for um usuário 'matriz' (tipo_usuario = 1), a filial de origem pode ser a matriz ou nula/selecionável dependendo da regra de negócio.
// Para uma doação "fixa" da origem, vamos assumir que o usuário logado é de uma filial e essa é a origem.
// Ajuste esta lógica conforme a sua necessidade de negócio.
$originBranchIdForDonation = null;
if (isset($loggedInUserBranchId)) {
    $originBranchIdForDonation = $loggedInUserBranchId;
} else {
    // Caso o usuário logado não tenha uma filial associada (ex: um admin da matriz),
    // você pode definir uma filial padrão para doações, ou exigir que seja selecionada.
    // Por simplicidade, vamos usar o ID 1 para a matriz como origem padrão se não houver filial.
    // CONSIDERE IMPLEMENTAR UMA LÓGICA DE SELEÇÃO OU ERRO SE ISSO NÃO DEVE ACONTECER.
    $originBranchIdForDonation = 1; // ID da matriz, por exemplo
}


// Verifica se é uma requisição AJAX para pesquisa de produtos
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
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

// Verifica se é uma requisição AJAX para busca de destinatários
if (isset($_GET['ajax']) && $_GET['ajax'] == 2) {
    $searchTerm = isset($_GET['term']) ? $_GET['term'] : '';
    $response = array('success' => false, 'users' => array());

    try {
        // Consulta SQL para buscar usuários que são filiais/lojas (tipo_usuario = 2 e eh_filial = TRUE)
        // E que NÃO SÃO a filial de origem (para evitar doação para si mesma)
        $sql = "SELECT id, nome, nome_filial, cidade, uf FROM usuarios WHERE
                 tipo_usuario = 2 AND eh_filial = TRUE AND id != ? AND
                 (nome LIKE ? OR nome_filial LIKE ? OR cidade LIKE ?)
                 ORDER BY nome_filial";

        // Se o termo de pesquisa estiver vazio, retorna todos os usuários filiais, exceto a de origem
        if (trim($searchTerm) === '') {
            $sql = "SELECT id, nome, nome_filial, cidade, uf FROM usuarios WHERE
                     tipo_usuario = 2 AND eh_filial = TRUE AND id != ?
                     ORDER BY nome_filial";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $originBranchIdForDonation); // Bind the origin branch ID
        } else {
            $stmt = $conn->prepare($sql);
            $likeTerm = '%' . $searchTerm . '%';
            $stmt->bind_param('isss', $originBranchIdForDonation, $likeTerm, $likeTerm, $likeTerm); // Bind origin and search terms
        }

        // Executa a consulta
        $stmt->execute();
        $resultado = $stmt->get_result();

        // Converte os resultados para um array
        $users = array();
        while ($user = $resultado->fetch_assoc()) {
            $users[] = $user;
        }

        $response['success'] = true;
        $response['users'] = $users;

        $stmt->close();

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

// Endpoint para salvar o pedido de doação
if (isset($_POST['save_donation'])) {
    $response = array('success' => false);

    try {
        // Iniciar transação
        $conn->begin_transaction();

        // Obter dados do formulário
        $destinatario_id = $_POST['destinatario_id'];
        $observacoes = $_POST['observacoes'];
        // A filial de origem é o ID da filial do usuário logado (já definido acima)
        $filial_origem_id = $originBranchIdForDonation;
        $items = json_decode($_POST['items'], true);

        if (empty($items)) {
            throw new Exception("O carrinho está vazio");
        }
        if (empty($destinatario_id)) {
            throw new Exception("Nenhum destinatário foi selecionado.");
        }
        if ($filial_origem_id == $destinatario_id) {
            throw new Exception("A filial de origem não pode ser a mesma que a filial de destino.");
        }


        // Inserir registro na tabela de pedidos
        // Agora, filial_usuario_id é a origem (quem está doando), e filial_destino_id é o destinatário.
        $sql = "INSERT INTO pedidos (tipo_pedido, status, filial_usuario_id, filial_destino_id, usuario_id, observacoes)
                 VALUES ('doacao', 'novo', ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiis', $filial_origem_id, $destinatario_id, $loggedInUserId, $observacoes);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao inserir pedido: " . $stmt->error);
        }

        $pedido_id = $conn->insert_id;
        $stmt->close();

        // Inserir itens do pedido
        $sql = "INSERT INTO pedido_itens (pedido_id, sku, quantidade, observacao) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        foreach ($items as $item) {
            $stmt->bind_param('iiis', $pedido_id, $item['sku'], $item['quantidade'], $item['observacao']);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao inserir item: " . $stmt->error);
            }
        }

        $stmt->close();

        // Confirmar transação
        $conn->commit();

        $response['success'] = true;
        $response['message'] = "Pedido de doação criado com sucesso!";
        $response['pedido_id'] = $pedido_id;
    } catch (Exception $e) {
        // Reverter em caso de erro
        $conn->rollback();
        $response['error'] = $e->getMessage();
    }

    // Retornar resposta como JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// ... (Restante do seu HTML e JavaScript)
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Doação de Produtos - SouthRock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Styles for the cart button */
        .cart-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background-color: #0d6efd;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            transition: transform 0.2s;
        }

        .cart-button:hover {
            transform: scale(1.05);
        }

        .cart-icon {
            color: white;
            font-size: 24px;
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
            font-weight: bold;
        }

        /* Styles for the cart sidebar */
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100%;
            background-color: white;
            box-shadow: -4px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1001;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .cart-sidebar.open {
            right: 0;
        }

        .cart-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-title {
            font-size: 20px;
            font-weight: bold;
            color: #212529;
        }

        .close-cart {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
        }

        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .empty-cart-message {
            text-align: center;
            color: #6c757d;
            margin-top: 40px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .item-details {
            flex: 1;
        }

        .item-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .item-sku {
            color: #6c757d;
            font-size: 14px;
        }

        .item-note {
            font-size: 14px;
            color: #495057;
            margin-top: 5px;
            font-style: italic;
        }

        .item-quantity {
            display: flex;
            align-items: center;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            font-weight: bold;
        }

        .quantity-input {
            width: 50px;
            height: 30px;
            border: 1px solid #dee2e6;
            text-align: center;
            margin: 0 5px;
        }

        .remove-item {
            color: #dc3545;
            cursor: pointer;
            margin-left: 15px;
            font-size: 18px;
        }

        .cart-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
        }

        .checkout-btn {
            width: 100%;
            padding: 12px 0;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .checkout-btn:hover {
            background-color: #0b5ed7;
        }

        .checkout-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
        }

        /* Search loading animation */
        .search-loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }

        @keyframes spin {
            0% {
                transform: translateY(-50%) rotate(0deg);
            }

            100% {
                transform: translateY(-50%) rotate(360deg);
            }
        }

        .search-indicator {
            font-size: 14px;
            color: #6c757d;
        }

        /* Customizar tabela */
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }

        .card-custom {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
        }

        /* Modal de destinatário */
        .recipient-item {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .recipient-item:hover {
            background-color: #f8f9fa;
            border-color: #0d6efd;
        }

        .recipient-item.selected {
            background-color: rgba(13, 110, 253, 0.1);
            border-color: #0d6efd;
        }

        .recipient-name {
            font-weight: bold;
            font-size: 18px;
        }

        .recipient-details {
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="container-fluid px-4 py-4">
        <div class="row mb-4 align-items-center">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h1 class="h2 text-primary">
                    <i class="bi bi-gift-fill me-2"></i>Doação de Produtos
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

    <div class="cart-button" id="cart-btn">
        <div class="cart-icon"><i class="bi bi-cart"></i></div>
        <div class="cart-count" id="cart-count">0</div>
    </div>

    <div class="overlay" id="overlay"></div>

    <div class="cart-sidebar" id="cart-sidebar">
        <div class="cart-header">
            <div class="cart-title">Seu carrinho</div>
            <button class="close-cart" id="close-cart">&times;</button>
        </div>

        <div class="cart-items" id="cart-items">
            <div class="empty-cart-message" id="empty-cart-message">
                Seu carrinho está vazio
            </div>
        </div>

        <div class="cart-footer">
            <button class="checkout-btn" id="checkout-btn" disabled>Finalizar doação</button>
        </div>
    </div>

    <div class="modal fade" id="recipientModal" tabindex="-1" aria-labelledby="recipientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recipientModalLabel">Selecionar Destinatário da Doação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-4">
                        <span class="input-group-text bg-light">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="recipient-search" class="form-control" placeholder="Pesquisar por nome, filial ou cidade">
                    </div>

                    <div id="recipients-container">
                        <div class="text-center py-4" id="recipients-loading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-2">Carregando destinatários...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirm-recipient" disabled>Confirmar Destinatário</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="observationsModal" tabindex="-1" aria-labelledby="observationsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="observationsModalLabel">Adicionar Observações</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="observations-text" class="form-label">Observações sobre esta doação:</label>
                        <textarea class="form-control" id="observations-text" rows="4" placeholder="Adicione informações importantes sobre esta doação..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="bi bi-info-circle-fill fs-4"></i>
                            </div>
                            <div>
                                <strong>Destinatário:</strong>
                                <div id="selected-recipient-info">Nenhum destinatário selecionado</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="submit-donation">Finalizar Doação</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos DOM
            const searchInput = document.getElementById('search-input');
            const searchLoading = document.getElementById('search-loading');
            const searchIndicator = document.getElementById('search-indicator');
            const initialMessage = document.getElementById('initial-message');
            const productsTableContainer = document.getElementById('products-table-container');
            const productsTableBody = document.getElementById('products-table-body');
            const noResults = document.getElementById('no-results');
            const searchTermDisplay = document.getElementById('search-term-display');
            const cartBtn = document.getElementById('cart-btn');
            const cartSidebar = document.getElementById('cart-sidebar');
            const overlay = document.getElementById('overlay');
            const closeCart = document.getElementById('close-cart');
            const cartItems = document.getElementById('cart-items');
            const emptyCartMessage = document.getElementById('empty-cart-message');
            const cartCount = document.getElementById('cart-count');
            const checkoutBtn = document.getElementById('checkout-btn');
            const recipientSearch = document.getElementById('recipient-search');
            const recipientsContainer = document.getElementById('recipients-container');
            const recipientsLoading = document.getElementById('recipients-loading');
            const confirmRecipientBtn = document.getElementById('confirm-recipient');
            const observationsText = document.getElementById('observations-text');
            const selectedRecipientInfo = document.getElementById('selected-recipient-info');
            const submitDonationBtn = document.getElementById('submit-donation');

            // Bootstrap Modals
            const recipientModal = new bootstrap.Modal(document.getElementById('recipientModal'));
            const observationsModal = new bootstrap.Modal(document.getElementById('observationsModal'));

            // Variáveis globais
            let cart = [];
            let searchTimeout;
            let recipientSearchTimeout;
            let selectedRecipientId = null;
            let selectedRecipientName = null;

            // Variáveis do PHP (para o ID da filial de origem)
            const originBranchId = <?php echo json_encode($originBranchIdForDonation); ?>;


            // Função para pesquisar produtos
            function searchProducts(term) {
                // Resetar a interface
                clearTimeout(searchTimeout);

                if (term.trim() === '') {
                    initialMessage.style.display = 'block';
                    productsTableContainer.style.display = 'none';
                    noResults.style.display = 'none';
                    searchIndicator.innerHTML = '<i class="bi bi-info-circle me-2"></i>Digite para começar a pesquisar';
                    return;
                }

                // Mostrar indicador de carregamento
                searchLoading.style.display = 'block';
                searchIndicator.innerHTML = '<i class="bi bi-clock me-2"></i>Pesquisando...';

                // Definir um timeout para evitar muitas requisições
                searchTimeout = setTimeout(() => {
                    fetch(`?ajax=1&term=${encodeURIComponent(term)}`)
                        .then(response => response.json())
                        .then(data => {
                            searchLoading.style.display = 'none';

                            if (data.success) {
                                initialMessage.style.display = 'none';

                                if (data.products.length > 0) {
                                    // Exibir os resultados
                                    productsTableContainer.style.display = 'block';
                                    noResults.style.display = 'none';

                                    // Limpar a tabela
                                    productsTableBody.innerHTML = '';

                                    // Adicionar produtos à tabela
                                    data.products.forEach(product => {
                                        const row = productsTableBody.insertRow();
                                        row.innerHTML = `
                                            <td>${product.sku}</td>
                                            <td>${product.produto}</td>
                                            <td>${product.grupo}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary add-to-cart-btn"
                                                    data-sku="${product.sku}"
                                                    data-produto="${product.produto}"
                                                    data-grupo="${product.grupo}">
                                                    <i class="bi bi-plus-circle"></i>
                                                </button>
                                            </td>
                                        `;
                                    });

                                    searchIndicator.innerHTML = `<i class="bi bi-check-circle me-2"></i>${data.products.length} produtos encontrados.`;

                                    // Adicionar event listeners para os botões "Adicionar ao carrinho"
                                    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                                        button.addEventListener('click', function() {
                                            const sku = this.dataset.sku;
                                            const produto = this.dataset.produto;
                                            const grupo = this.dataset.grupo;
                                            addToCart({
                                                sku: sku,
                                                produto: produto,
                                                grupo: grupo,
                                                quantidade: 1,
                                                observacao: ''
                                            });
                                        });
                                    });

                                } else {
                                    productsTableContainer.style.display = 'none';
                                    noResults.style.display = 'block';
                                    searchTermDisplay.textContent = term;
                                    searchIndicator.innerHTML = '<i class="bi bi-info-circle me-2"></i>Nenhum produto encontrado.';
                                }
                            } else {
                                Swal.fire('Erro na Pesquisa', data.error || 'Ocorreu um erro ao pesquisar produtos.', 'error');
                                searchIndicator.innerHTML = '<i class="bi bi-x-circle me-2"></i>Erro na pesquisa.';
                            }
                        })
                        .catch(error => {
                            searchLoading.style.display = 'none';
                            Swal.fire('Erro de Conexão', 'Não foi possível conectar ao servidor para pesquisar produtos.', 'error');
                            searchIndicator.innerHTML = '<i class="bi bi-x-circle me-2"></i>Erro de conexão.';
                            console.error('Erro:', error);
                        });
                }, 500); // Atraso de 500ms
            }

            // Função para adicionar item ao carrinho
            function addToCart(product) {
                const existingItemIndex = cart.findIndex(item => item.sku === product.sku);

                if (existingItemIndex > -1) {
                    // Se o produto já existe, incrementa a quantidade
                    cart[existingItemIndex].quantidade++;
                } else {
                    // Caso contrário, adiciona o novo produto
                    cart.push(product);
                }
                updateCartUI();
                Swal.fire({
                    icon: 'success',
                    title: 'Adicionado!',
                    text: `${product.produto} (${product.sku}) adicionado ao carrinho.`,
                    showConfirmButton: false,
                    timer: 1000
                });
            }

            // Função para remover item do carrinho
            function removeFromCart(sku) {
                cart = cart.filter(item => item.sku !== sku);
                updateCartUI();
            }

            // Função para atualizar quantidade do item no carrinho
            function updateItemQuantity(sku, newQuantity) {
                const item = cart.find(item => item.sku === sku);
                if (item) {
                    item.quantidade = parseInt(newQuantity, 10);
                    if (item.quantidade < 1) {
                        removeFromCart(sku);
                    } else {
                        updateCartUI();
                    }
                }
            }

            // Função para atualizar observação do item no carrinho
            function updateItemObservation(sku, observation) {
                const item = cart.find(item => item.sku === sku);
                if (item) {
                    item.observacao = observation; // Atribui a observação ao item
                    updateCartUI(); // Atualiza a UI para refletir a observação
                }
            }

            // Função para atualizar a interface do carrinho
            function updateCartUI() {
                cartItems.innerHTML = ''; // Limpa os itens existentes
                if (cart.length === 0) {
                    emptyCartMessage.style.display = 'block';
                    checkoutBtn.disabled = true;
                } else {
                    emptyCartMessage.style.display = 'none';
                    checkoutBtn.disabled = false;
                    cart.forEach(item => {
                        const itemElement = document.createElement('div');
                        itemElement.classList.add('cart-item');
                        itemElement.innerHTML = `
                            <div class="item-details">
                                <div class="item-title">${item.produto}</div>
                                <div class="item-sku">SKU: ${item.sku}</div>
                                <div class="item-note-container">
                                    <textarea class="form-control form-control-sm item-note"
                                        placeholder="Observação (opcional)"
                                        data-sku="${item.sku}"
                                        rows="1">${item.observacao}</textarea>
                                </div>
                            </div>
                            <div class="item-quantity">
                                <button class="quantity-btn decrease-quantity" data-sku="${item.sku}">-</button>
                                <input type="number" class="quantity-input" value="${item.quantidade}" data-sku="${item.sku}" min="1">
                                <button class="quantity-btn increase-quantity" data-sku="${item.sku}">+</button>
                                <button class="btn btn-sm remove-item" data-sku="${item.sku}"><i class="bi bi-trash"></i></button>
                            </div>
                        `;
                        cartItems.appendChild(itemElement);
                    });

                    // Adicionar event listeners para os botões de quantidade e observação
                    document.querySelectorAll('.decrease-quantity').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const sku = this.dataset.sku;
                            const input = document.querySelector(`.quantity-input[data-sku="${sku}"]`);
                            input.value = parseInt(input.value) - 1;
                            updateItemQuantity(sku, input.value);
                        });
                    });

                    document.querySelectorAll('.increase-quantity').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const sku = this.dataset.sku;
                            const input = document.querySelector(`.quantity-input[data-sku="${sku}"]`);
                            input.value = parseInt(input.value) + 1;
                            updateItemQuantity(sku, input.value);
                        });
                    });

                    document.querySelectorAll('.quantity-input').forEach(input => {
                        input.addEventListener('change', function() {
                            const sku = this.dataset.sku;
                            updateItemQuantity(sku, this.value);
                        });
                    });

                    document.querySelectorAll('.remove-item').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const sku = this.dataset.sku;
                            removeFromCart(sku);
                        });
                    });

                    document.querySelectorAll('.item-note').forEach(textarea => {
                        textarea.addEventListener('input', function() {
                            const sku = this.dataset.sku;
                            updateItemObservation(sku, this.value);
                        });
                    });
                }
                cartCount.textContent = cart.length; // Atualiza o contador de itens no botão do carrinho
            }

            // Função para pesquisar destinatários
            function searchRecipients(term) {
                clearTimeout(recipientSearchTimeout);
                recipientsLoading.style.display = 'block';
                recipientsContainer.innerHTML = ''; // Limpa resultados anteriores

                recipientSearchTimeout = setTimeout(() => {
                    fetch(`?ajax=2&term=${encodeURIComponent(term)}`)
                        .then(response => response.json())
                        .then(data => {
                            recipientsLoading.style.display = 'none';
                            if (data.success && data.users.length > 0) {
                                data.users.forEach(user => {
                                    const recipientItem = document.createElement('div');
                                    recipientItem.classList.add('recipient-item');
                                    recipientItem.dataset.id = user.id;
                                    recipientItem.dataset.name = user.nome_filial || user.nome; // Prioriza nome_filial
                                    recipientItem.innerHTML = `
                                        <div class="recipient-name">${user.nome_filial || user.nome}</div>
                                        <div class="recipient-details">${user.cidade} - ${user.uf}</div>
                                    `;
                                    recipientsContainer.appendChild(recipientItem);

                                    recipientItem.addEventListener('click', function() {
                                        document.querySelectorAll('.recipient-item').forEach(item => item.classList.remove('selected'));
                                        this.classList.add('selected');
                                        selectedRecipientId = this.dataset.id;
                                        selectedRecipientName = this.dataset.name;
                                        confirmRecipientBtn.disabled = false;
                                    });
                                });
                            } else {
                                recipientsContainer.innerHTML = '<div class="alert alert-info text-center">Nenhum destinatário encontrado.</div>';
                                confirmRecipientBtn.disabled = true;
                            }
                        })
                        .catch(error => {
                            recipientsLoading.style.display = 'none';
                            recipientsContainer.innerHTML = '<div class="alert alert-danger text-center">Erro ao carregar destinatários.</div>';
                            Swal.fire('Erro de Conexão', 'Não foi possível conectar ao servidor para pesquisar destinatários.', 'error');
                            console.error('Erro:', error);
                            confirmRecipientBtn.disabled = true;
                        });
                }, 500);
            }


            // Event Listeners
            searchInput.addEventListener('input', function() {
                searchProducts(this.value);
            });

            cartBtn.addEventListener('click', function() {
                cartSidebar.classList.add('open');
                overlay.style.display = 'block';
            });

            closeCart.addEventListener('click', function() {
                cartSidebar.classList.remove('open');
                overlay.style.display = 'none';
            });

            overlay.addEventListener('click', function() {
                cartSidebar.classList.remove('open');
                overlay.style.display = 'none';
            });

            checkoutBtn.addEventListener('click', function() {
                if (cart.length === 0) {
                    Swal.fire('Carrinho Vazio', 'Adicione produtos ao carrinho antes de finalizar a doação.', 'warning');
                    return;
                }
                searchRecipients(''); // Carrega todos os destinatários ao abrir o modal
                recipientModal.show();
            });

            recipientSearch.addEventListener('input', function() {
                searchRecipients(this.value);
            });

            confirmRecipientBtn.addEventListener('click', function() {
                if (selectedRecipientId === null) {
                    Swal.fire('Erro', 'Por favor, selecione um destinatário.', 'warning');
                    return;
                }
                if (selectedRecipientId == originBranchId) {
                    Swal.fire('Erro', 'A filial de destino não pode ser a mesma que a filial de origem.', 'error');
                    return;
                }

                selectedRecipientInfo.textContent = selectedRecipientName;
                recipientModal.hide();
                observationsModal.show();
            });

            submitDonationBtn.addEventListener('click', function() {
                if (selectedRecipientId === null) {
                    Swal.fire('Erro', 'Nenhum destinatário foi selecionado.', 'error');
                    return;
                }
                if (cart.length === 0) {
                    Swal.fire('Erro', 'O carrinho de doação está vazio.', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Confirmar Doação?',
                    text: `Você está prestes a finalizar uma doação para ${selectedRecipientName}. Confirma?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, finalizar doação!',
                    cancelButtonText: 'Não, cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('save_donation', '1');
                        formData.append('destinatario_id', selectedRecipientId);
                        formData.append('observacoes', observationsText.value);
                        formData.append('items', JSON.stringify(cart));

                        fetch('', { // Envia para o próprio script PHP
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Sucesso!',
                                        text: data.message,
                                        showConfirmButton: false,
                                        timer: 2000
                                    }).then(() => {
                                        // Limpar carrinho e fechar modais
                                        cart = [];
                                        selectedRecipientId = null;
                                        selectedRecipientName = null;
                                        observationsText.value = '';
                                        updateCartUI();
                                        cartSidebar.classList.remove('open');
                                        overlay.style.display = 'none';
                                        observationsModal.hide();
                                    });
                                } else {
                                    Swal.fire('Erro ao Salvar', data.error || 'Ocorreu um erro desconhecido.', 'error');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Erro de Conexão', 'Não foi possível conectar ao servidor para salvar a doação.', 'error');
                                console.error('Erro:', error);
                            });
                    }
                });
            });

            // Inicializar UI do carrinho ao carregar
            updateCartUI();
        });
    </script>
</body>

</html>