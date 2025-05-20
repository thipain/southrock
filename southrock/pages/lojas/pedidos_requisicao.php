<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['finalizar'])) {
    require_once '../../includes/db.php';
    $dados = json_decode(file_get_contents('php://input'), true);
    $response = ['success' => false];

    if (!isset($dados['items']) || !is_array($dados['items'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos.']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Obter ID do usuário logado
        $username = $_SESSION['username'];
        $stmt = $conn->prepare("SELECT id, tipo_usuario, eh_filial FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();

        if (!$usuario) {
            throw new Exception("Usuário não encontrado");
        }

        $usuario_id = $usuario['id'];
        $tipo_usuario = $usuario['tipo_usuario'];
        $eh_filial = $usuario['eh_filial'];

        // Determinar filial_usuario_id baseado no tipo de usuário
        $filial_usuario_id = null;
        if ($tipo_usuario == 2) { // Se o usuário é do tipo loja
            if ($eh_filial) {
                // Se o próprio usuário é uma filial, use o ID do próprio usuário
                $filial_usuario_id = $usuario_id;
            } else {
                // Buscar a filial associada ao usuário pelo mesmo CNPJ
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE cnpj = (SELECT cnpj FROM usuarios WHERE id = ?) AND eh_filial = TRUE LIMIT 1");
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $filial = $result->fetch_assoc();
                $stmt->close();
                
                if ($filial) {
                    $filial_usuario_id = $filial['id'];
                } else {
                    throw new Exception("Filial não encontrada para este usuário");
                }
            }
        } else {
            // Para usuário tipo matriz, usamos NULL no campo filial_usuario_id
            // ou podemos deixar o usuário escolher a filial em um formulário posterior
            $filial_usuario_id = null;
        }

        // Inserir o pedido com tipo 'requisicao' e status 'novo'
        $stmtPedido = $conn->prepare(
            "INSERT INTO pedidos (tipo_pedido, status, filial_usuario_id, usuario_id) 
             VALUES ('requisicao', 'novo', ?, ?)"
        );
        $stmtPedido->bind_param("ii", $filial_usuario_id, $usuario_id);
        $stmtPedido->execute();
        $pedidoId = $stmtPedido->insert_id;
        $stmtPedido->close();

        $stmtItem = $conn->prepare("INSERT INTO pedido_itens (pedido_id, sku, quantidade) VALUES (?, ?, ?)");
        foreach ($dados['items'] as $item) {
            $stmtItem->bind_param('isi', $pedidoId, $item['sku'], $item['quantidade']);
            $stmtItem->execute();
        }
        $stmtItem->close();

        $conn->commit();
        $response['success'] = true;
        $response['pedido_id'] = $pedidoId;
    } catch (Exception $e) {
        $conn->rollback();
        $response['error'] = $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

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
                    CAST(sku AS CHAR) LIKE ? OR 
                    produto LIKE ? OR 
                    grupo LIKE ? 
                    ORDER BY sku
                    LIMIT 50";
            
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
        <link rel="stylesheet" href="../../css/pedidos_requisicao.css">
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
        <script>
            // Variáveis globais para o carrinho
            let cartItems = [];
            let cartCount = 0;
            let searchTimeout = null;
            
            // Elementos DOM
            const cartBtn = document.getElementById('cart-btn');
            const cartSidebar = document.getElementById('cart-sidebar');
            const closeCartBtn = document.getElementById('close-cart');
            const overlay = document.getElementById('overlay');
            const cartItemsContainer = document.getElementById('cart-items');
            const emptyCartMessage = document.getElementById('empty-cart-message');
            const cartCountElement = document.getElementById('cart-count');
            const checkoutBtn = document.getElementById('checkout-btn');
            
            // Elementos de pesquisa
            const searchInput = document.getElementById('search-input');
            const searchLoading = document.getElementById('search-loading');
            const initialMessage = document.getElementById('initial-message');
            const productsTableContainer = document.getElementById('products-table-container');
            const productsTableBody = document.getElementById('products-table-body');
            const noResults = document.getElementById('no-results');
            const searchTermDisplay = document.getElementById('search-term-display');
            const searchIndicator = document.getElementById('search-indicator');
            
            // Função para realizar pesquisa em tempo real
            function performSearch(searchTerm) {
                // Adicionamos um log para depuração
                console.log("Pesquisando por:", searchTerm);
                
                // Mostra o indicador de carregamento
                searchLoading.style.display = 'block';
                searchIndicator.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Buscando...';
                
                // Atualiza o texto de exibição do termo pesquisado
                searchTermDisplay.textContent = searchTerm;
                
                // Faz a requisição AJAX
                fetch(`?ajax=1&term=${encodeURIComponent(searchTerm)}`)
                    .then(response => {
                        console.log("Status da resposta:", response.status);
                        return response.json();
                    })
                    .then(data => {
                        // Adicionamos um log para ver a resposta
                        console.log("Resposta da pesquisa:", data);
                        
                        // Esconde o indicador de carregamento
                        searchLoading.style.display = 'none';
                        
                        // Processa os resultados
                        if (data.success) {
                            // Atualiza o indicador de pesquisa
                            if (data.products.length > 0) {
                                searchIndicator.innerHTML = `<i class="bi bi-check-circle me-2"></i>${data.products.length} produto(s) encontrado(s)`;
                            } else {
                                searchIndicator.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Nenhum produto encontrado';
                            }
                            
                            // Limpa a tabela de resultados
                            productsTableBody.innerHTML = '';
                            
                            if (data.products.length > 0) {
                                // Preenche a tabela com os resultados
                                data.products.forEach(product => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td>${product.sku}</td>
                                        <td>${product.produto}</td>
                                        <td>${product.grupo}</td>
                                        <td class="text-center">
                                            <button class="add-to-cart-btn" 
                                                data-id="${product.sku}" 
                                                data-title="${product.produto}">
                                                <i class="bi bi-cart-plus me-1"></i>Adicionar
                                            </button>
                                        </td>
                                    `;
                                    productsTableBody.appendChild(row);
                                });
                                
                                // Mostra a tabela e esconde outros elementos
                                initialMessage.style.display = 'none';
                                productsTableContainer.style.display = 'block';
                                noResults.style.display = 'none';
                                
                                // Adiciona event listeners aos botões de adicionar ao carrinho
                                document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                                    button.addEventListener('click', function() {
                                        addToCart(this);
                                    });
                                });
                            } else {
                                // Não há resultados
                                initialMessage.style.display = 'none';
                                productsTableContainer.style.display = 'none';
                                noResults.style.display = 'block';
                            }
                        } else {
                            // Erro na pesquisa
                            searchIndicator.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Erro ao realizar a pesquisa';
                            console.error('Erro na pesquisa:', data.error);
                        }
                    })
                    .catch(error => {
                        // Esconde o indicador de carregamento
                        searchLoading.style.display = 'none';
                        searchIndicator.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Erro ao conectar com o servidor';
                        console.error('Erro na requisição:', error);
                    });
            }
            
            // Event listener para o campo de pesquisa (com debounce)
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.trim();
                
                // Limpa o timeout anterior
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                // Atualiza o indicador de pesquisa
                if (searchTerm === '') {
                    searchIndicator.innerHTML = '<i class="bi bi-info-circle me-2"></i>Digite para começar a pesquisar';
                    initialMessage.style.display = 'block';
                    productsTableContainer.style.display = 'none';
                    noResults.style.display = 'none';
                    searchLoading.style.display = 'none';
                    return;
                } else if (searchTerm.length < 2) {
                    searchIndicator.innerHTML = '<i class="bi bi-info-circle me-2"></i>Digite pelo menos 2 caracteres';
                    return;
                } else {
                    searchIndicator.innerHTML = '<i class="bi bi-keyboard me-2"></i>Digitando...';
                }
                
                // Define um novo timeout (300ms de delay para evitar muitas requisições)
                searchTimeout = setTimeout(() => {
                    performSearch(searchTerm);
                }, 300);
            });
            
            // Funções para manipular o carrinho
            
            // Abrir o carrinho
            function openCart() {
                cartSidebar.classList.add('open');
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Impedir rolagem da página
            }
            
            // Fechar o carrinho
            function closeCart() {
                cartSidebar.classList.remove('open');
                overlay.style.display = 'none';
                document.body.style.overflow = 'auto'; // Permitir rolagem da página
            }
            
            // Adicionar item ao carrinho
            function addToCart(button) {
                // Obter dados do botão usando data attributes
                const id = button.getAttribute('data-id');
                const title = button.getAttribute('data-title');
                
                // Verificar se o item já está no carrinho
                const existingItemIndex = cartItems.findIndex(item => item.id === id);
                
                if (existingItemIndex !== -1) {
                    // Aumentar quantidade
                    cartItems[existingItemIndex].quantity += 1;
                } else {
                    // Adicionar novo item
                    cartItems.push({
                        id: id,
                        title: title,
                        quantity: 1
                    });
                }
                
                updateCart();
                
                // Feedback visual
                Swal.fire({
                    position: 'top-end',
                    icon: 'success',
                    title: 'Produto adicionado!',
                    showConfirmButton: false,
                    timer: 1000
                });
            }
            
            // Remover item do carrinho
            function removeItem(id) {
                cartItems = cartItems.filter(item => item.id !== id);
                updateCart();
            }
            
            // Atualizar quantidade de um item
            function updateQuantity(id, newQuantity) {
                if (newQuantity < 1) return;
                
                const itemIndex = cartItems.findIndex(item => item.id === id);
                if (itemIndex !== -1) {
                    cartItems[itemIndex].quantity = newQuantity;
                    updateCart();
                }
            }
            
            // Atualizar o carrinho na interface
            function updateCart() {
                // Atualizar contador do carrinho
                cartCount = cartItems.reduce((total, item) => total + item.quantity, 0);
                cartCountElement.textContent = cartCount;
                
                // Atualizar itens no carrinho
                renderCartItems();
                
                // Salvar no localStorage para persistência
                localStorage.setItem('cartItems', JSON.stringify(cartItems));
            }
            
            // Renderizar itens do carrinho
            function renderCartItems() {
                // Limpar container exceto a mensagem de carrinho vazio
                const children = [...cartItemsContainer.children];
                children.forEach(child => {
                    if (child !== emptyCartMessage) {
                        cartItemsContainer.removeChild(child);
                    }
                });
                
                // Mostrar mensagem se o carrinho estiver vazio
                if (cartItems.length === 0) {
                    emptyCartMessage.style.display = 'block';
                    return;
                } else {
                    emptyCartMessage.style.display = 'none';
                }
                
                // Adicionar cada item ao container
                cartItems.forEach(item => {
                    const cartItemElement = document.createElement('div');
                    cartItemElement.className = 'cart-item';
                    cartItemElement.innerHTML = `
                        <div class="cart-item-details">
                            <div class="cart-item-title">
                                <i class="bi bi-box me-2"></i>${item.title}
                            </div>
                            <div class="cart-item-actions">
                                <div class="quantity-control">
                                    <button class="quantity-btn minus-btn" data-id="${item.id}">-</button>
                                    <input type="text" class="quantity-input" value="${item.quantity}" data-id="${item.id}">
                                    <button class="quantity-btn plus-btn" data-id="${item.id}">+</button>
                                </div>
                                <button class="remove-item" data-id="${item.id}">Remover</button>
                            </div>
                        </div>
                    `;
                    
                    cartItemsContainer.insertBefore(cartItemElement, emptyCartMessage);
                });
                
                // Adicionar event listeners aos botões de quantidade e remoção
                document.querySelectorAll('.minus-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const item = cartItems.find(item => item.id === id);
                        if (item) updateQuantity(id, item.quantity - 1);
                    });
                });
                
                document.querySelectorAll('.plus-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const item = cartItems.find(item => item.id === id);
                        if (item) updateQuantity(id, item.quantity + 1);
                    });
                });
                
                document.querySelectorAll('.quantity-input').forEach(input => {
                    input.addEventListener('change', function() {
                        const id = this.getAttribute('data-id');
                        const newValue = parseInt(this.value) || 1;
                        updateQuantity(id, newValue);
                    });
                });
                
                document.querySelectorAll('.remove-item').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        removeItem(id);
                    });
                });
            }
            
            // Finalizar requisição
            function checkout() {
                if (cartItems.length === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Carrinho vazio',
                        text: 'Adicione produtos ao carrinho antes de finalizar a requisição.'
                    });
                    return;
                }

                // Preparar dados para envio
                const requisicaoData = {
                    items: cartItems.map(item => ({
                        sku: item.id,
                        quantidade: item.quantity
                    }))
                };

                // Enviar dados ao servidor
                fetch('?finalizar=1', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requisicaoData)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Requisição concluída!',
                            text: `Sua requisição #${data.pedido_id} foi registrada com sucesso como "nova requisição".`,
                            confirmButtonText: 'Ver pedidos',
                            showCancelButton: true,
                            cancelButtonText: 'Continuar comprando'
                        }).then((result) => {
                            // Limpar carrinho em ambos os casos
                            cartItems = [];
                            localStorage.removeItem('cartItems');
                            updateCart();
                            closeCart();
                            
                            if (result.isConfirmed) {
                                // Redirecionar para a lista de pedidos
                                window.location.href = 'pedidos.php';
                            }
                        });
                    } else {
                        Swal.fire('Erro', data.error || 'Falha ao registrar o pedido.', 'error');
                    }
                })
                .catch((error) => {
                    console.error('Erro na finalização:', error);
                    Swal.fire('Erro', 'Erro de comunicação com o servidor.', 'error');
                });
            }
            
            // Event Listeners
            document.addEventListener('DOMContentLoaded', function() {
                console.log('Página carregada, inicializando script...');
                
                // Carregar carrinho do localStorage
                const savedCart = localStorage.getItem('cartItems');
                if (savedCart) {
                    try {
                        cartItems = JSON.parse(savedCart);
                        updateCart();
                    } catch (e) {
                        console.error("Erro ao carregar carrinho:", e);
                        localStorage.removeItem('cartItems');
                    }
                }
                
                // Focar no campo de pesquisa ao carregar a página
                searchInput.focus();
                
                // Abrir carrinho
                cartBtn.addEventListener('click', openCart);
                
                // Fechar carrinho
                closeCartBtn.addEventListener('click', closeCart);
                overlay.addEventListener('click', closeCart);
                
                // Finalizar compra
                checkoutBtn.addEventListener('click', checkout);
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