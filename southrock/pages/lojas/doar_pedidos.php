<?php
// Configura exibição de erros para desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclui o arquivo de conexão para a página principal
require_once '../../includes/db.php';

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
        // Consulta SQL para buscar usuários (filiais/lojas)
        $sql = "SELECT id, nome, nome_filial, cidade, uf FROM usuarios WHERE 
                tipo_usuario = 2 AND eh_filial = TRUE AND
                (nome LIKE ? OR nome_filial LIKE ? OR cidade LIKE ?) 
                ORDER BY nome_filial";

        // Se o termo de pesquisa estiver vazio, retorna todos os usuários filiais
        if (trim($searchTerm) === '') {
            $sql = "SELECT id, nome, nome_filial, cidade, uf FROM usuarios WHERE 
                    tipo_usuario = 2 AND eh_filial = TRUE
                    ORDER BY nome_filial";
            $stmt = $conn->prepare($sql);
        } else {
            $stmt = $conn->prepare($sql);
            $likeTerm = '%' . $searchTerm . '%';
            $stmt->bind_param('sss', $likeTerm, $likeTerm, $likeTerm);
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
        $usuario_id = 1; // Substitua pelo ID do usuário logado atual
        $items = json_decode($_POST['items'], true);

        if (empty($items)) {
            throw new Exception("O carrinho está vazio");
        }

        // Inserir registro na tabela de pedidos
        $sql = "INSERT INTO pedidos (tipo_pedido, status, filial_usuario_id, usuario_id, observacoes) 
                VALUES ('doacao', 'novo', ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $destinatario_id, $usuario_id, $observacoes);

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

// Página HTML principal
try {
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <title>Doação de Produtos - SouthRock</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
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
                <button class="checkout-btn" id="checkout-btn" disabled>Finalizar doação</button>
            </div>
        </div>

        <!-- Modal de seleção de destinatário -->
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
                            <!-- Lista de destinatários será carregada aqui -->
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

        <!-- Modal de observações finais -->
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

        <!-- Bootstrap JS e Dependências -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- SweetAlert2 para notificações -->
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
                let selectedRecipientId = null;
                let selectedRecipientName = null;

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
                                            const row = document.createElement('tr');

                                            row.innerHTML = `
                                                <td>${product.sku}</td>
                                                <td>${product.produto}</td>
                                                <td>${product.grupo}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-primary add-to-cart-btn" 
                                                            data-sku="${product.sku}" 
                                                            data-name="${product.produto}" 
                                                            data-category="${product.grupo}">
                                                        <i class="bi bi-plus-circle me-1"></i>Adicionar
                                                    </button>
                                                </td>
                                            `;

                                            productsTableBody.appendChild(row);
                                        });

                                        // Adicionar event listeners aos botões
                                        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                                            button.addEventListener('click', function() {
                                                const sku = this.getAttribute('data-sku');
                                                const name = this.getAttribute('data-name');
                                                const category = this.getAttribute('data-category');

                                                addToCart(sku, name, category);
                                            });
                                        });

                                        searchIndicator.innerHTML = `<i class="bi bi-check-circle me-2"></i>${data.products.length} produtos encontrados`;
                                    } else {
                                        // Sem resultados
                                        productsTableContainer.style.display = 'none';
                                        noResults.style.display = 'block';
                                        searchTermDisplay.textContent = term;
                                        searchIndicator.innerHTML = '<i class="bi bi-x-circle me-2"></i>Nenhum produto encontrado';
                                    }
                                } else {
                                    // Erro na pesquisa
                                    initialMessage.style.display = 'none';
                                    productsTableContainer.style.display = 'none';
                                    noResults.style.display = 'block';
                                    searchTermDisplay.textContent = term;
                                    searchIndicator.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Erro ao pesquisar produtos';

                                    console.error('Erro na busca:', data.error);
                                }
                            })
                            .catch(error => {
                                searchLoading.style.display = 'none';
                                searchIndicator.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Erro ao conectar ao servidor';
                                console.error('Erro:', error);
                            });
                    }, 500);
                }

                // Função para adicionar produto ao carrinho
                function addToCart(sku, name, category) {
                    // Verificar se o produto já está no carrinho
                    const existingItemIndex = cart.findIndex(item => item.sku == sku);

                    if (existingItemIndex !== -1) {
                        // Incrementar quantidade se já existir
                        cart[existingItemIndex].quantidade++;

                        // Atualizar interface
                        updateCartUI();

                        Swal.fire({
                            title: 'Quantidade Atualizada!',
                            text: `${name} agora tem ${cart[existingItemIndex].quantidade} unidades no carrinho`,
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    } else {
                        // Adicionar novo item
                        cart.push({
                            sku: sku,
                            nome: name,
                            categoria: category,
                            quantidade: 1,
                            observacao: ''
                        });

                        // Atualizar interface
                        updateCartUI();

                        Swal.fire({
                            title: 'Produto Adicionado!',
                            text: `${name} foi adicionado ao carrinho`,
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                }

                // Função para atualizar a interface do carrinho
                function updateCartUI() {
                    // Atualizar contador do carrinho
                    const totalItems = cart.reduce((total, item) => total + item.quantidade, 0);
                    cartCount.textContent = totalItems;

                    // Atualizar botão de checkout
                    checkoutBtn.disabled = totalItems === 0;

                    // Atualizar lista de itens
                    if (totalItems === 0) {
                        emptyCartMessage.style.display = 'block';
                        cartItems.querySelectorAll('.cart-item').forEach(el => el.remove());
                    } else {
                        emptyCartMessage.style.display = 'none';

                        // Limpar itens atuais
                        cartItems.querySelectorAll('.cart-item').forEach(el => el.remove());

                        // Adicionar itens atualizados
                        cart.forEach((item, index) => {
                            const itemElement = document.createElement('div');
                            itemElement.className = 'cart-item';

                            itemElement.innerHTML = `
                                <div class="item-details">
                                    <div class="item-title">${item.nome}</div>
                                    <div class="item-sku">SKU: ${item.sku} | ${item.categoria}</div>
                                    <div class="mt-2">
                                        <input type="text" class="form-control form-control-sm item-note-input" 
                                               placeholder="Adicionar observação" value="${item.observacao || ''}" 
                                               data-index="${index}">
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="item-quantity">
                                        <div class="quantity-btn quantity-decrease" data-index="${index}">-</div>
                                        <input type="number" min="1" value="${item.quantidade}" class="quantity-input" data-index="${index}">
                                        <div class="quantity-btn quantity-increase" data-index="${index}">+</div>
                                    </div>
                                    <div class="remove-item" data-index="${index}"><i class="bi bi-trash"></i></div>
                                </div>
                            `;

                            cartItems.insertBefore(itemElement, emptyCartMessage);
                        });

                        // Adicionar event listeners aos controles
                        document.querySelectorAll('.quantity-decrease').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const index = this.getAttribute('data-index');
                                if (cart[index].quantidade > 1) {
                                    cart[index].quantidade--;
                                    updateCartUI();
                                }
                            });
                        });

                        document.querySelectorAll('.quantity-increase').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const index = this.getAttribute('data-index');
                                cart[index].quantidade++;
                                updateCartUI();
                            });
                        });

                        document.querySelectorAll('.quantity-input').forEach(input => {
                            input.addEventListener('change', function() {
                                const index = this.getAttribute('data-index');
                                const value = parseInt(this.value);

                                if (value >= 1) {
                                    cart[index].quantidade = value;
                                } else {
                                    this.value = cart[index].quantidade;
                                }

                                updateCartUI();
                            });
                        });

                        document.querySelectorAll('.item-note-input').forEach(input => {
                            input.addEventListener('change', function() {
                                const index = this.getAttribute('data-index');
                                cart[index].observacao = this.value.trim();
                            });
                        });

                        document.querySelectorAll('.remove-item').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const index = this.getAttribute('data-index');

                                Swal.fire({
                                    title: 'Remover item?',
                                    text: `Deseja remover ${cart[index].nome} do carrinho?`,
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonText: 'Sim, remover',
                                    cancelButtonText: 'Cancelar'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        cart.splice(index, 1);
                                        updateCartUI();

                                        Swal.fire({
                                            title: 'Item removido!',
                                            icon: 'success',
                                            toast: true,
                                            position: 'top-end',
                                            showConfirmButton: false,
                                            timer: 3000,
                                            timerProgressBar: true
                                        });
                                    }
                                });
                            });
                        });
                    }
                }

                // Função para carregar os destinatários
                function loadRecipients(searchTerm = '') {
                    recipientsLoading.style.display = 'block';
                    recipientsContainer.querySelectorAll('.recipient-item').forEach(el => el.remove());
                    confirmRecipientBtn.disabled = true;

                    fetch(`?ajax=2&term=${encodeURIComponent(searchTerm)}`)
                        .then(response => response.json())
                        .then(data => {
                            recipientsLoading.style.display = 'none';

                            if (data.success && data.users.length > 0) {
                                data.users.forEach(user => {
                                    const recipientEl = document.createElement('div');
                                    recipientEl.className = 'recipient-item';
                                    recipientEl.setAttribute('data-id', user.id);
                                    recipientEl.setAttribute('data-name', user.nome_filial || user.nome);

                                    recipientEl.innerHTML = `
                                        <div class="recipient-name">
                                            ${user.nome_filial || user.nome}
                                        </div>
                                        <div class="recipient-details">
                                            <div><i class="bi bi-person me-2"></i>${user.nome}</div>
                                            <div><i class="bi bi-geo-alt me-2"></i>${user.cidade} - ${user.uf}</div>
                                        </div>
                                    `;

                                    recipientEl.addEventListener('click', function() {
                                        // Remover seleção atual
                                        document.querySelectorAll('.recipient-item').forEach(el => {
                                            el.classList.remove('selected');
                                        });

                                        // Marcar este como selecionado
                                        this.classList.add('selected');

                                        // Armazenar ID e nome do destinatário selecionado
                                        selectedRecipientId = this.getAttribute('data-id');
                                        selectedRecipientName = this.getAttribute('data-name');

                                        // Habilitar botão de confirmação
                                        confirmRecipientBtn.disabled = false;
                                    });

                                    recipientsContainer.appendChild(recipientEl);
                                });
                            } else {
                                // Nenhum destinatário encontrado
                                const noResultsEl = document.createElement('div');
                                noResultsEl.className = 'alert alert-warning';
                                noResultsEl.innerHTML = `
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Nenhum destinatário encontrado${searchTerm ? ` para "${searchTerm}"` : ''}.
                                `;
                                recipientsContainer.appendChild(noResultsEl);
                            }
                        })
                        .catch(error => {
                            recipientsLoading.style.display = 'none';

                            const errorEl = document.createElement('div');
                            errorEl.className = 'alert alert-danger';
                            errorEl.innerHTML = `
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                Erro ao carregar destinatários. Por favor, tente novamente.
                            `;
                            recipientsContainer.appendChild(errorEl);

                            console.error('Erro ao carregar destinatários:', error);
                        });
                }

                // Event listeners para busca de produtos
                searchInput.addEventListener('input', function() {
                    const term = this.value.trim();
                    searchProducts(term);
                });

                // Event listeners do carrinho
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

                // Event listener para finalizar doação (abrir modal de destinatário)
                checkoutBtn.addEventListener('click', function() {
                    // Resetar seleção
                    selectedRecipientId = null;
                    selectedRecipientName = null;
                    confirmRecipientBtn.disabled = true;

                    // Carregar destinatários
                    loadRecipients();

                    // Fechar carrinho e abrir modal
                    cartSidebar.classList.remove('open');
                    overlay.style.display = 'none';
                    recipientModal.show();
                });

                // Event listener para pesquisa de destinatários
                recipientSearch.addEventListener('input', function() {
                    const term = this.value.trim();
                    loadRecipients(term);
                });

                // Event listener para confirmar destinatário
                confirmRecipientBtn.addEventListener('click', function() {
                    // Fechar modal atual
                    recipientModal.hide();

                    // Atualizar texto do destinatário selecionado
                    selectedRecipientInfo.textContent = selectedRecipientName;

                    // Limpar observações
                    observationsText.value = '';

                    // Abrir modal de observações
                    observationsModal.show();
                });

                // Event listener para finalizar doação
                submitDonationBtn.addEventListener('click', function() {
                    const observations = observationsText.value.trim();

                    // Verificações finais
                    if (!selectedRecipientId) {
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Selecione um destinatário para a doação.',
                            icon: 'error'
                        });
                        return;
                    }

                    if (cart.length === 0) {
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Adicione pelo menos um produto ao carrinho.',
                            icon: 'error'
                        });
                        return;
                    }

                    // Mostrar indicador de carregamento
                    submitDonationBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processando...';
                    submitDonationBtn.disabled = true;

                    // Preparar dados para envio
                    const formData = new FormData();
                    formData.append('save_donation', '1');
                    formData.append('destinatario_id', selectedRecipientId);
                    formData.append('observacoes', observations);
                    formData.append('items', JSON.stringify(cart));

                    // Enviar pedido
                    fetch('doar_pedidos.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            submitDonationBtn.innerHTML = 'Finalizar Doação';
                            submitDonationBtn.disabled = false;

                            if (data.success) {
                                // Fechar modal
                                observationsModal.hide();

                                // Limpar carrinho
                                cart = [];
                                updateCartUI();

                                // Mostrar mensagem de sucesso
                                Swal.fire({
                                    title: 'Doação Registrada!',
                                    text: `Seu pedido de doação foi registrado com sucesso! Número do pedido: ${data.pedido_id}`,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    // Opcional: redirecionar para página de confirmação ou dashboard
                                    // window.location.href = 'fazer_pedidos.php';
                                });
                            } else {
                                // Mostrar mensagem de erro
                                Swal.fire({
                                    title: 'Erro!',
                                    text: data.error || 'Ocorreu um erro ao processar a doação. Tente novamente.',
                                    icon: 'error'
                                });
                            }
                        })
                        .catch(error => {
                            submitDonationBtn.innerHTML = 'Finalizar Doação';
                            submitDonationBtn.disabled = false;

                            Swal.fire({
                                title: 'Erro!',
                                text: 'Ocorreu um erro ao conectar ao servidor. Verifique sua conexão e tente novamente.',
                                icon: 'error'
                            });

                            console.error('Erro ao enviar doação:', error);
                        });
                });

                // Inicializar carrinho
                updateCartUI();
            });
        </script>
    </body>

    </html>
<?php

    $conn->close();
} catch (Exception $e) {
    // Tratamento de erro
    echo "Erro: " . $e->getMessage();
}
?>