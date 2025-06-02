<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once '../../includes/db.php'; // Conexão com o banco

// Verificar se o usuário está logado e é uma filial
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 2) {
    header('Location: ../../index.php'); // Redireciona para o login se não for filial
    exit();
}

$loggedInUserId = $_SESSION['user_id']; // ID do usuário da filial logada

// Buscar lista de outras filiais para seleção (excluindo a própria filial logada)
$stmt_filiais = $conn->prepare("SELECT id, nome_filial, cnpj, cidade, uf FROM usuarios WHERE eh_filial = TRUE AND id != ? ORDER BY nome_filial ASC");
$stmt_filiais->bind_param("i", $loggedInUserId);
$stmt_filiais->execute();
$result_filiais = $stmt_filiais->get_result();
$outras_filiais = [];
while ($row = $result_filiais->fetch_assoc()) {
    $outras_filiais[] = $row;
}
$stmt_filiais->close();

// Lógica de busca AJAX de produtos (será chamada pelo JavaScript)
if (isset($_GET['ajax_search_produtos']) && $_GET['ajax_search_produtos'] == 1) {
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
    $response = ['success' => false, 'products' => []];

    if (!empty($searchTerm)) {
        $likeTerm = '%' . $searchTerm . '%';
        $sql_produtos = "SELECT sku, produto, unidade_medida, grupo FROM produtos WHERE produto LIKE ? OR CAST(sku AS CHAR) LIKE ? ORDER BY produto ASC LIMIT 20";
        $stmt_produtos = $conn->prepare($sql_produtos);
        $stmt_produtos->bind_param("ss", $likeTerm, $likeTerm);
        $stmt_produtos->execute();
        $result_prods = $stmt_produtos->get_result();
        while ($prod = $result_prods->fetch_assoc()) {
            $response['products'][] = $prod;
        }
        $stmt_produtos->close();
        $response['success'] = true;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}


// Lógica para processar o POST do formulário de troca
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['propor_troca'])) {
    $produtos_enviar_skus = $_POST['produtos_enviar_sku'] ?? [];
    $produtos_enviar_quantidades = $_POST['produtos_enviar_quantidade'] ?? [];
    $produtos_enviar_obs = $_POST['produtos_enviar_observacao'] ?? [];

    $produtos_receber_skus = $_POST['produtos_receber_sku'] ?? [];
    $produtos_receber_quantidades = $_POST['produtos_receber_quantidade'] ?? [];
    $produtos_receber_obs = $_POST['produtos_receber_observacao'] ?? [];
    
    $filial_parceira_id = filter_input(INPUT_POST, 'filial_parceira_id', FILTER_VALIDATE_INT);
    $observacoes_gerais = trim($_POST['observacoes_gerais'] ?? '');

    if (empty($produtos_enviar_skus) && empty($produtos_receber_skus)) {
        $_SESSION['error_message_loja'] = 'Você deve selecionar produtos para enviar ou para receber.';
    } elseif (empty($filial_parceira_id)) {
        $_SESSION['error_message_loja'] = 'Você deve selecionar uma filial parceira para a troca.';
    } elseif ($filial_parceira_id == $loggedInUserId) {
         $_SESSION['error_message_loja'] = 'A filial parceira não pode ser a sua própria filial.';
    } else {
        try {
            $conn->begin_transaction();

            // MODIFICADO: Status inicial para propostas de troca
            $status_inicial_troca = 'novo_troca_pendente_aceite_parceiro';

            $sql_pedido = "INSERT INTO pedidos (tipo_pedido, status, filial_usuario_id, filial_destino_id, usuario_id, observacoes) 
                           VALUES ('troca', ?, ?, ?, ?, ?)"; // Status agora é um placeholder
            $stmt_pedido = $conn->prepare($sql_pedido);
            if (!$stmt_pedido) throw new Exception("Erro ao preparar pedido: " . $conn->error);
            
            $stmt_pedido->bind_param("siiis", $status_inicial_troca, $loggedInUserId, $filial_parceira_id, $loggedInUserId, $observacoes_gerais);
            $stmt_pedido->execute();
            $novo_pedido_id = $conn->insert_id;

            if (!$novo_pedido_id) {
                throw new Exception("Falha ao criar o registro do pedido de troca: " . $stmt_pedido->error);
            }
            $stmt_pedido->close(); 

            $sql_item = "INSERT INTO pedido_itens (pedido_id, sku, quantidade, observacao, tipo_item_troca) VALUES (?, ?, ?, ?, ?)";
            $stmt_item = $conn->prepare($sql_item);
            if (!$stmt_item) throw new Exception("Erro ao preparar item do pedido: " . $conn->error);

            // Inserir itens a ENVIAR
            for ($i = 0; $i < count($produtos_enviar_skus); $i++) {
                $sku = filter_var($produtos_enviar_skus[$i], FILTER_VALIDATE_INT);
                $qty = filter_var($produtos_enviar_quantidades[$i], FILTER_VALIDATE_FLOAT);
                if ($sku && $qty && $qty > 0) {
                    $obs_item = !empty($produtos_enviar_obs[$i]) ? trim($produtos_enviar_obs[$i]) : NULL;
                    $tipo_troca = 'enviado';
                    $stmt_item->bind_param("iidss", $novo_pedido_id, $sku, $qty, $obs_item, $tipo_troca);
                    $stmt_item->execute();
                     if ($stmt_item->affected_rows <= 0) {
                        throw new Exception("Falha ao adicionar item (SKU: {$sku}) ao pedido de troca (enviado). Erro: " . $stmt_item->error);
                    }
                }
            }

            // Inserir itens a RECEBER
            for ($i = 0; $i < count($produtos_receber_skus); $i++) {
                $sku = filter_var($produtos_receber_skus[$i], FILTER_VALIDATE_INT);
                $qty = filter_var($produtos_receber_quantidades[$i], FILTER_VALIDATE_FLOAT);
                if ($sku && $qty && $qty > 0) {
                    $obs_item = !empty($produtos_receber_obs[$i]) ? trim($produtos_receber_obs[$i]) : NULL;
                    $tipo_troca = 'recebido';
                    $stmt_item->bind_param("iidss", $novo_pedido_id, $sku, $qty, $obs_item, $tipo_troca);
                    $stmt_item->execute();
                     if ($stmt_item->affected_rows <= 0) {
                        throw new Exception("Falha ao adicionar item (SKU: {$sku}) ao pedido de troca (recebido). Erro: " . $stmt_item->error);
                    }
                }
            }
            
            $stmt_item->close();
            $conn->commit();
            
            $_SESSION['success_message_loja'] = "Solicitação de troca nº {$novo_pedido_id} enviada com sucesso! Aguardando aceite da filial parceira.";
            header('Location: historico.php'); 
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Erro ao processar troca: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            $_SESSION['error_message_loja'] = "Erro ao processar a solicitação de troca: " . $e->getMessage();
        }
    }
}
$nome_sistema_atual = "SouthRock - Troca de Produtos";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Troca de Produtos - <?= htmlspecialchars($nome_sistema_atual) ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { 
            background-color: #f4f6f9; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            padding-bottom: 70px;
        }
        .top-bar-loja { 
            background-color: #343a40; 
            color: white; 
            padding: 0.75rem 1.25rem; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem; 
        }
        .top-bar-loja h1 { 
            font-size: 1.5rem; 
            margin-bottom: 0;
            font-weight: 500;
        }
        .top-bar-loja .btn-outline-light {
            border-width: 2px;
        }
        .product-section { 
            background-color: #fff; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 25px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.08); 
        }
        .product-section h3 { 
            margin-bottom: 18px; 
            color: #0069d9; 
            border-bottom: 2px solid #dee2e6; 
            padding-bottom: 12px; 
            font-size: 1.4rem;
            font-weight: 500;
        }
        .product-section h3 .fas, .product-section h3 .bi {
            margin-right: 8px;
        }
        .form-control-sm {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            height: calc(1.5em + 0.5rem + 2px);
        }
        .search-results-container { 
            max-height: 180px; 
            overflow-y: auto; 
            border: 1px solid #ced4da; 
            border-top: none; 
            border-radius: 0 0 0.25rem 0.25rem; 
            position: absolute; 
            background-color: white; 
            z-index: 1050; 
            width: 100%; 
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
            display: none; 
        }
        .search-result-item { 
            padding: 0.5rem 0.75rem; 
            cursor: pointer; 
            border-bottom: 1px solid #f1f1f1; 
            font-size: 0.9rem;
        }
        .search-result-item:last-child { border-bottom: none; }
        .search-result-item:hover { background-color: #e9ecef; }
        
        .selected-products-cart .empty-cart-text {
            color: #6c757d;
            font-style: italic;
            padding: 15px 5px;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px dashed #ced4da;
        }

        .cart-item-row { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            margin-bottom: 12px; 
            padding: 12px; 
            background-color: #f8f9fa; 
            border: 1px solid #dee2e6;
            border-radius: 5px; 
        }
        .cart-item-row .item-name {
            flex-grow: 1;
            font-weight: 500;
        }
        .cart-item-row input[type="number"] { 
            width: 80px; 
            text-align: center;
        }
        .cart-item-row input[type="text"] { 
            flex-basis: 200px; 
            flex-grow: 1;
        }
        .btn-remove-item { 
            color: #dc3545; 
            background: none;
            border: none;
            font-size: 1.2em; 
            line-height: 1;
            padding: 0.25rem 0.5rem;
        }
        .btn-remove-item:hover { color: #a71d2a; }

        .submit-section { 
            margin-top: 30px; 
            padding-top: 25px; 
            border-top: 1px solid #ced4da; 
        }
        .btn-primary.btn-lg {
            padding: .6rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 0.3rem;
        }
        .input-group .form-control { 
            position: relative;
            flex: 1 1 auto;
            width: 1%;
            min-width: 0;
        }
        .position-relative { 
            position: relative;
        }
        .alert-dismissible .close { /* Para Bootstrap 4 */
            position: absolute;
            top: 0;
            right: 0;
            padding: 0.75rem 1.25rem;
            color: inherit;
        }
    </style>
</head>
<body>

    <div class="top-bar-loja">
        <h1>Solicitar Troca de Produtos</h1>
        <a href="fazer_pedidos.php" class="btn btn-outline-light btn-sm">
            <i class="fas fa-arrow-left"></i> Voltar para Opções
        </a>
    </div>

    <div class="container mt-4">
        <?php if(isset($_SESSION['error_message_loja'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message_loja']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <?php unset($_SESSION['error_message_loja']); ?>
        <?php endif; ?>
        
        <form id="formProporTroca" action="trocar_produtos.php" method="POST">
            <input type="hidden" name="propor_troca" value="1">

            <div class="row">
                <div class="col-md-6">
                    <div class="product-section">
                        <h3><i class="fas fa-arrow-circle-up text-warning"></i> Produtos que Você Vai Enviar</h3>
                        <div class="form-group position-relative">
                            <label for="search_enviar">Buscar Produto para Enviar:</label>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm search-produtos" id="search_enviar" placeholder="Digite nome ou SKU..." data-target-cart="cart_enviar">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                            </div>
                            <div class="search-results-container" id="results_enviar"></div>
                        </div>
                        <div id="cart_enviar" class="selected-products-cart mt-3">
                            <p class="text-muted empty-cart-text">Nenhum produto adicionado para envio.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="product-section">
                        <h3><i class="fas fa-arrow-circle-down text-success"></i> Produtos que Você Quer Receber</h3>
                        <div class="form-group position-relative">
                            <label for="search_receber">Buscar Produto para Receber:</label>
                             <div class="input-group">
                                <input type="text" class="form-control form-control-sm search-produtos" id="search_receber" placeholder="Digite nome ou SKU..." data-target-cart="cart_receber">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                            </div>
                            <div class="search-results-container" id="results_receber"></div>
                        </div>
                        <div id="cart_receber" class="selected-products-cart mt-3">
                             <p class="text-muted empty-cart-text">Nenhum produto adicionado para recebimento.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-section">
                <h3><i class="fas fa-handshake text-info"></i> Detalhes da Troca</h3>
                <div class="form-group">
                    <label for="filial_parceira_id">Trocar com a Filial:</label>
                    <select class="form-control" id="filial_parceira_id" name="filial_parceira_id" required>
                        <option value="">-- Selecione uma filial parceira --</option>
                        <?php foreach ($outras_filiais as $filial): ?>
                            <option value="<?= htmlspecialchars($filial['id']) ?>">
                                <?= htmlspecialchars($filial['nome_filial']) ?> (<?= htmlspecialchars($filial['cnpj']) ?>) - <?= htmlspecialchars($filial['cidade']) ?>/<?= htmlspecialchars($filial['uf']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="observacoes_gerais">Observações Gerais para esta Troca (opcional):</label>
                    <textarea class="form-control" id="observacoes_gerais" name="observacoes_gerais" rows="3" placeholder="Ex: Urgência, motivo específico da troca, etc."></textarea>
                </div>
            </div>

            <div class="submit-section text-center mb-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> Enviar Proposta de Troca
                </button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const carts = {
            enviar: [],
            receber: []
        };
        let searchDebounceTimer;

        function renderCart(cartType) {
            const cartContainer = document.getElementById(`cart_${cartType}`);
            const emptyCartText = cartContainer.querySelector('.empty-cart-text');
            
            cartContainer.querySelectorAll('.cart-item-row').forEach(row => row.remove());

            if (carts[cartType].length === 0) {
                if (emptyCartText) emptyCartText.style.display = 'block';
            } else {
                if (emptyCartText) emptyCartText.style.display = 'none';
                carts[cartType].forEach((item, index) => {
                    const itemRow = document.createElement('div');
                    itemRow.className = 'cart-item-row'; 
                    itemRow.innerHTML = `
                        <input type="hidden" name="produtos_${cartType}_sku[]" value="${item.sku}">
                        <span class="item-name">${item.produto} (SKU: ${item.sku})</span>
                        <input type="number" class="form-control form-control-sm" name="produtos_${cartType}_quantidade[]" value="${item.quantidade}" min="1" step="any" required title="Quantidade">
                        <input type="text" class="form-control form-control-sm" name="produtos_${cartType}_observacao[]" placeholder="Obs. (opcional)" value="${item.observacao || ''}" title="Observação">
                        <button type="button" class="btn btn-sm btn-remove-item" data-index="${index}" data-cart="${cartType}" title="Remover Item">&times;</button>
                    `;
                    cartContainer.appendChild(itemRow);
                });
            }
        }

        function addToCart(cartType, product) {
            const existingItemIndex = carts[cartType].findIndex(item => item.sku === product.sku);
            if (existingItemIndex > -1) {
                carts[cartType][existingItemIndex].quantidade = parseFloat(carts[cartType][existingItemIndex].quantidade) + 1;
            } else {
                carts[cartType].push({ 
                    sku: product.sku, 
                    produto: product.produto, 
                    unidade_medida: product.unidade_medida,
                    quantidade: 1,
                    observacao: ''
                });
            }
            renderCart(cartType);
        }

        document.querySelectorAll('.search-produtos').forEach(input => {
            const resultsContainerId = input.dataset.targetCart === 'cart_enviar' ? 'results_enviar' : 'results_receber';
            const resultsContainer = document.getElementById(resultsContainerId);
            
            input.addEventListener('keyup', function() {
                clearTimeout(searchDebounceTimer);
                const searchTerm = this.value.trim();
                const cartType = this.dataset.targetCart === 'cart_enviar' ? 'enviar' : 'receber';

                if (searchTerm.length < 2) {
                    resultsContainer.innerHTML = '';
                    resultsContainer.style.display = 'none';
                    return;
                }

                searchDebounceTimer = setTimeout(() => {
                    fetch(`trocar_produtos.php?ajax_search_produtos=1&term=${encodeURIComponent(searchTerm)}`)
                        .then(response => response.json())
                        .then(data => {
                            resultsContainer.innerHTML = '';
                            if (data.success && data.products.length > 0) {
                                resultsContainer.style.display = 'block';
                                data.products.forEach(product => {
                                    const div = document.createElement('div');
                                    div.className = 'search-result-item';
                                    div.textContent = `${product.produto} (SKU: ${product.sku}) - ${product.unidade_medida}`;
                                    div.onclick = () => {
                                        addToCart(cartType, product);
                                        resultsContainer.innerHTML = '';
                                        resultsContainer.style.display = 'none';
                                        input.value = ''; 
                                    };
                                    resultsContainer.appendChild(div);
                                });
                            } else {
                                resultsContainer.innerHTML = '<div class="search-result-item text-muted">Nenhum produto encontrado.</div>';
                                resultsContainer.style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('Erro na busca AJAX:', error);
                            resultsContainer.innerHTML = '<div class="search-result-item text-danger">Erro ao buscar.</div>';
                            resultsContainer.style.display = 'block';
                        });
                }, 300);
            });
            
            document.addEventListener('click', function(event) {
                if (!input.contains(event.target) && !resultsContainer.contains(event.target)) {
                    resultsContainer.style.display = 'none';
                }
            });
        });

        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('btn-remove-item') || event.target.closest('.btn-remove-item')) {
                const button = event.target.classList.contains('btn-remove-item') ? event.target : event.target.closest('.btn-remove-item');
                const cartType = button.dataset.cart;
                const index = parseInt(button.dataset.index);
                carts[cartType].splice(index, 1);
                renderCart(cartType);
            }
        });
        
        document.addEventListener('change', function(event) {
            const target = event.target;
            if (target.name && (target.name.endsWith('_quantidade[]') || target.name.endsWith('_observacao[]'))) {
                const itemRow = target.closest('.cart-item-row');
                if (!itemRow) return;
                
                const cartItemElements = Array.from(itemRow.parentElement.querySelectorAll('.cart-item-row'));
                const itemIndex = cartItemElements.indexOf(itemRow);
                
                const cartType = itemRow.parentElement.id.split('_')[1]; // cart_enviar -> enviar

                if (carts[cartType] && carts[cartType][itemIndex]) {
                     if (target.name.includes('_quantidade[]')) {
                        let newQty = parseFloat(target.value);
                        if (isNaN(newQty) || newQty < 0.01) newQty = 1; 
                        carts[cartType][itemIndex].quantidade = newQty;
                        target.value = newQty; 
                    } else if (target.name.includes('_observacao[]')) {
                        carts[cartType][itemIndex].observacao = target.value;
                    }
                }
            }
        });

        renderCart('enviar');
        renderCart('receber');

        const formProporTroca = document.getElementById('formProporTroca');
        if (formProporTroca) {
            formProporTroca.addEventListener('submit', function(e) {
                const filialParceira = document.getElementById('filial_parceira_id').value;
                if (!filialParceira) {
                    alert('Por favor, selecione uma filial parceira para a troca.');
                    e.preventDefault();
                    return;
                }
                if (carts.enviar.length === 0 && carts.receber.length === 0) {
                    alert('Você deve adicionar produtos para enviar ou para receber na troca.');
                    e.preventDefault();
                    return;
                }
                let validForm = true;
                ['enviar', 'receber'].forEach(cartType => {
                    carts[cartType].forEach(item => {
                        if(item.quantidade <= 0){
                            alert(`A quantidade para o produto ${item.produto} (SKU: ${item.sku}) na lista de "${cartType}" deve ser maior que zero.`);
                            validForm = false;
                        }
                    });
                });
                if(!validForm) {
                    e.preventDefault();
                    return;
                }
                
                const submitButton = formProporTroca.querySelector('button[type="submit"]');
                if(submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                }
            });
        }
        
        var alertList = document.querySelectorAll('.alert-dismissible');
        alertList.forEach(function (alert) {
            // Para Bootstrap 4, o data-dismiss="alert" no botão já deve funcionar com o JS do Bootstrap.
            // Para Bootstrap 5, o JS do BS5 manipula data-bs-dismiss="alert".
            // Este timeout é um extra.
            setTimeout(function() {
                if (alert && typeof $ !== 'undefined' && $.fn.alert) { // Verifica se jQuery e plugin alert existem (BS4)
                    $(alert).alert('close');
                } else if (alert && typeof bootstrap !== 'undefined' && bootstrap.Alert && bootstrap.Alert.getInstance(alert)) { // BS5
                     bootstrap.Alert.getInstance(alert).close();
                }
            }, 7000);
        });
    });
    </script>
</body>
</html>
<?php
if(isset($conn)) $conn->close();
?>