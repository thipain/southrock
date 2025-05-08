<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../includes/db.php';

// Verificar se o ID do pedido foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pedidos.php");
    exit();
}

$pedido_id = intval($_GET['id']);

// Buscar informações do pedido
$query = "SELECT p.*, f.nome_filial, f.cnpj, f.endereco, f.cidade, f.estado, u.nome as usuario_nome
          FROM pedidos p 
          JOIN filiais f ON p.filial_id = f.id
          JOIN usuarios u ON p.usuario_id = u.id
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    header("Location: pedidos.php");
    exit();
}

$pedido = $resultado->fetch_assoc();

// Verificar se o pedido já foi finalizado
if ($pedido['status'] === 'finalizado') {
    $_SESSION['erro'] = "Não é possível editar um pedido finalizado.";
    header("Location: detalhes_pedido.php?id=$pedido_id");
    exit();
}

// Buscar itens do pedido
$query_itens = "SELECT i.*, pr.produto, pr.unidade_medida 
                FROM pedido_itens i
                JOIN produtos pr ON i.sku = pr.sku
                WHERE i.pedido_id = ?";

$stmt_itens = $conn->prepare($query_itens);
$stmt_itens->bind_param("i", $pedido_id);
$stmt_itens->execute();
$itens_result = $stmt_itens->get_result();
$itens = [];
while ($item = $itens_result->fetch_assoc()) {
    $itens[] = $item;
}

// Buscar todos os produtos disponíveis
$query_produtos = "SELECT * FROM produtos ORDER BY produto ASC";
$resultado_produtos = $conn->query($query_produtos);
$produtos = [];
while ($prod = $resultado_produtos->fetch_assoc()) {
    $produtos[] = $prod;
}

// Buscar todas as filiais disponíveis
$query_filiais = "SELECT * FROM filiais ORDER BY nome_filial ASC";
$resultado_filiais = $conn->query($query_filiais);
$filiais = [];
while ($filial = $resultado_filiais->fetch_assoc()) {
    $filiais[] = $filial;
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Iniciar transação
        $conn->begin_transaction();
        
        // Atualizar pedido principal
        $novo_tipo_pedido = $_POST['tipo_pedido'];
        $nova_filial_id = $_POST['filial_id'];
        $novas_observacoes = $_POST['observacoes'];
        
        $query_update = "UPDATE pedidos SET 
                        tipo_pedido = ?,
                        filial_id = ?,
                        observacoes = ?
                        WHERE id = ?";
        
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bind_param("sisi", $novo_tipo_pedido, $nova_filial_id, $novas_observacoes, $pedido_id);
        $stmt_update->execute();
        
        // Remover itens antigos
        $query_delete = "DELETE FROM pedido_itens WHERE pedido_id = ?";
        $stmt_delete = $conn->prepare($query_delete);
        $stmt_delete->bind_param("i", $pedido_id);
        $stmt_delete->execute();
        
        // Inserir novos itens
        $item_skus = $_POST['item_sku'];
        $item_quantidades = $_POST['item_quantidade'];
        $item_observacoes = $_POST['item_observacao'];
        
        $query_insert_item = "INSERT INTO pedido_itens (pedido_id, sku, quantidade, observacao) VALUES (?, ?, ?, ?)";
        $stmt_insert_item = $conn->prepare($query_insert_item);
        
        for ($i = 0; $i < count($item_skus); $i++) {
            if (!empty($item_skus[$i]) && !empty($item_quantidades[$i])) {
                $stmt_insert_item->bind_param("isds", $pedido_id, $item_skus[$i], $item_quantidades[$i], $item_observacoes[$i]);
                $stmt_insert_item->execute();
            }
        }
        
        // Finalizar transação
        $conn->commit();
        
        $_SESSION['sucesso'] = "Pedido atualizado com sucesso!";
        header("Location: detalhes_pedido.php?id=$pedido_id");
        exit();
    } catch (Exception $e) {
        // Reverter alterações em caso de erro
        $conn->rollback();
        $_SESSION['erro'] = "Erro ao atualizar pedido: " . $e->getMessage();
    }
}

// Mapear tipos de pedido para exibição
$tipoPedidoInfo = [
    'requisicao' => [
        'icon' => 'fa-file-invoice',
        'label' => 'Requisição'
    ],
    'troca' => [
        'icon' => 'fa-exchange-alt',
        'label' => 'Troca'
    ],
    'doacao' => [
        'icon' => 'fa-gift',
        'label' => 'Doação'
    ],
    'devolucao' => [
        'icon' => 'fa-undo-alt',
        'label' => 'Devolução'
    ]
];

$tipoPedido = $tipoPedidoInfo[$pedido['tipo_pedido']] ?? ['icon' => 'fa-question-circle', 'label' => 'Desconhecido'];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pedido #<?= $pedido_id ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/pedidos.css">
    <style>
        :root {
            --primary-color: #3949AB;
            --secondary-color: #5C6BC0;
            --success-color: #43A047;
            --warning-color: #FFA000;
            --danger-color: #E53935;
            --light-bg: #F9FAFC;
            --dark-text: #37474F;
            --light-text: #78909C;
            --border-radius: 12px;
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
        }

        body {
            background-color: var(--light-bg);
            color: var(--dark-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            display: flex;
        }

        .content {
            margin-left: 240px;
            flex: 1;
            padding: 20px;
            min-height: 100vh;
            background-color: var(--light-bg);
            width: calc(100% - 240px);
            box-sizing: border-box;
        }

        .content.expanded {
            margin-left: 60px;
            width: calc(100% - 60px);
        }

        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: white;
            border: 1px solid #E0E0E0;
            color: var(--dark-text);
            border-radius: var(--border-radius);
            padding: 8px 16px;
            margin-bottom: 20px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .back-button:hover {
            background-color: #F5F5F5;
            border-color: #D0D0D0;
            text-decoration: none;
            color: var(--primary-color);
        }

        .order-main-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .order-header {
            padding: 25px 25px 20px 25px;
            border-bottom: 1px solid #EEEEEE;
        }

        .order-id-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 0;
        }

        .order-number {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }

        .type-icon-wrapper {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background-color: rgba(57, 73, 171, 0.1);
        }

        .type-icon {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .content-section {
            padding: 25px;
        }

        .content-section:not(:last-child) {
            border-bottom: 1px solid #EEEEEE;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark-text);
        }

        .form-section {
            margin-bottom: 25px;
        }

        .form-control {
            border-radius: var(--border-radius);
            border: 1px solid #E0E0E0;
            padding: 12px 15px;
            height: auto;
            min-height: 48px;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(57, 73, 171, 0.25);
        }
        
        select.form-control {
            height: 48px;
            padding-right: 30px;
        }
        
        textarea.form-control {
            min-height: 100px;
        }

        label {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--dark-text);
            font-size: 0.95rem;
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background-color: #FFEBEE;
            border-color: #FFCDD2;
            color: var(--danger-color);
        }

        .alert-success {
            background-color: #E8F5E9;
            border-color: #C8E6C9;
            color: var(--success-color);
        }

        .item-row {
            background-color: #F9FAFC;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            border: 1px solid #E0E0E0;
        }

        .remove-item {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: #FFEBEE;
            color: var(--danger-color);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .remove-item:hover {
            background-color: var(--danger-color);
            color: white;
        }

        .add-item-btn {
            background-color: #E8F5E9;
            color: var(--success-color);
            border: 2px dashed #C8E6C9;
            border-radius: var(--border-radius);
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .add-item-btn:hover {
            background-color: #C8E6C9;
        }

        .btn {
            border-radius: var(--border-radius);
            padding: 12px 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-danger:hover {
            opacity: 0.9;
        }

        .btn-outline-secondary {
            border-color: #E0E0E0;
            color: var(--dark-text);
        }

        .btn-outline-secondary:hover {
            background-color: #F5F5F5;
            color: var(--primary-color);
            border-color: #D0D0D0;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-bars"></i>
        </div>

        <div>
            <a href="dashboard.php">
                <i class="fas fa-home icon"></i>
                <span class="text">Início</span>
            </a>

            <a href="pedidos.php">
                <i class="fas fa-shopping-cart icon"></i>
                <span class="text">Pedidos</span>
            </a>

            <a href="usuarios.php">
                <i class="fas fa-users icon"></i>
                <span class="text">Usuários</span>
            </a>

            <a href="produtos.php">
                <i class="fas fa-box icon"></i>
                <span class="text">Produtos</span>
            </a>
        </div>

        <a href="../../logout/logout.php">
            <i class="fas fa-sign-out-alt icon"></i>
            <span class="text">Sair</span>
        </a>
    </div>

    <div class="content">
        <div class="header">
            <h1>Editar Pedido</h1>
        </div>

        <div class="main-content">
            <!-- Botão de voltar -->
            <a href="detalhes_pedido.php?id=<?= $pedido_id ?>" class="back-button">
                <i class="fas fa-arrow-left"></i> Voltar para Detalhes do Pedido
            </a>

            <?php if (isset($_SESSION['erro'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['erro'] ?>
                    <?php unset($_SESSION['erro']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['sucesso'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['sucesso'] ?>
                    <?php unset($_SESSION['sucesso']); ?>
                </div>
            <?php endif; ?>

            <!-- Formulário de edição do pedido -->
            <div class="order-main-card">
                <div class="order-header">
                    <div class="order-id-container">
                        <div class="type-icon-wrapper">
                            <i class="fas <?= $tipoPedido['icon'] ?> type-icon"></i>
                        </div>
                        <h2 class="order-number">Editar Pedido #<?= $pedido_id ?></h2>
                    </div>
                </div>

                <form method="POST" action="">
                    <div class="content-section">
                        <h3 class="section-title">Informações Básicas</h3>
                        
                        <div class="row">
                            <div class="col-md-6 form-section">
                                <label for="tipo_pedido">Tipo de Pedido</label>
                                <select class="form-control" id="tipo_pedido" name="tipo_pedido" required>
                                    <option value="requisicao" <?= $pedido['tipo_pedido'] === 'requisicao' ? 'selected' : '' ?>>Requisição</option>
                                    <option value="troca" <?= $pedido['tipo_pedido'] === 'troca' ? 'selected' : '' ?>>Troca</option>
                                    <option value="doacao" <?= $pedido['tipo_pedido'] === 'doacao' ? 'selected' : '' ?>>Doação</option>
                                    <option value="devolucao" <?= $pedido['tipo_pedido'] === 'devolucao' ? 'selected' : '' ?>>Devolução</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 form-section">
                                <label for="filial_id">Filial</label>
                                <select class="form-control" id="filial_id" name="filial_id" required>
                                    <?php foreach ($filiais as $filial): ?>
                                        <option value="<?= $filial['id'] ?>" <?= $pedido['filial_id'] == $filial['id'] ? 'selected' : '' ?>>
                                            <?= $filial['nome_filial'] ?> - <?= $filial['cidade'] ?>/<?= $filial['estado'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <label for="observacoes">Observações Gerais</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= $pedido['observacoes'] ?? '' ?></textarea>
                        </div>
                    </div>

                    <div class="content-section">
                        <h3 class="section-title">Itens do Pedido</h3>
                        
                        <div id="items-container">
                            <?php foreach ($itens as $index => $item): ?>
                            <div class="item-row">
                                <button type="button" class="remove-item" onclick="removeItem(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                                
                                <div class="row">
                                    <div class="col-md-5 form-section">
                                        <label>Produto</label>
                                        <select class="form-control custom-select" name="item_sku[]" required>
                                            <option value="">Selecione um produto</option>
                                            <?php foreach ($produtos as $produto): ?>
                                                <option value="<?= $produto['sku'] ?>" <?= $item['sku'] === $produto['sku'] ? 'selected' : '' ?>>
                                                    <?= $produto['sku'] ?> - <?= $produto['produto'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3 form-section">
                                        <label>Quantidade</label>
                                        <input type="number" class="form-control" name="item_quantidade[]" min="0.01" step="0.01" value="<?= $item['quantidade'] ?>" required>
                                    </div>
                                    
                                    <div class="col-md-4 form-section">
                                        <label>Observação</label>
                                        <input type="text" class="form-control" name="item_observacao[]" value="<?= $item['observacao'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="add-item-btn" onclick="addItem()">
                            <i class="fas fa-plus"></i> Adicionar Item
                        </div>
                    </div>

                    <div class="content-section">
                        <div class="form-actions">
                            <a href="detalhes_pedido.php?id=<?= $pedido_id ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Alterações
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../../js/dashboard.js"></script>
    <script>
        // Script para ajustar o layout e garantir que não haja espaço entre a sidebar e o conteúdo
        document.addEventListener('DOMContentLoaded', function() {
            function adjustLayout() {
                const sidebarWidth = document.querySelector('.sidebar').offsetWidth;
                document.querySelector('.content').style.marginLeft = sidebarWidth + 'px';
                document.querySelector('.content').style.width = `calc(100% - ${sidebarWidth}px)`;
            }
            
            // Ajusta o layout inicialmente
            adjustLayout();
            
            // Ajusta novamente quando a janela for redimensionada
            window.addEventListener('resize', adjustLayout);
        });

        // Função para adicionar um novo item ao pedido
        function addItem() {
            const itemsContainer = document.getElementById('items-container');
            const newItem = document.createElement('div');
            newItem.className = 'item-row';
            
            // Obter todos os produtos para o select
            const productOptions = Array.from(document.querySelector('select[name="item_sku[]"]').options).map(option => {
                return `<option value="${option.value}">${option.text}</option>`;
            }).join('');
            
            newItem.innerHTML = `
                <button type="button" class="remove-item" onclick="removeItem(this)">
                    <i class="fas fa-times"></i>
                </button>
                
                <div class="row">
                    <div class="col-md-5 form-section">
                        <label>Produto</label>
                        <select class="form-control custom-select" name="item_sku[]" required>
                            ${productOptions}
                        </select>
                    </div>
                    
                    <div class="col-md-3 form-section">
                        <label>Quantidade</label>
                        <input type="number" class="form-control" name="item_quantidade[]" min="0.01" step="0.01" value="1" required>
                    </div>
                    
                    <div class="col-md-4 form-section">
                        <label>Observação</label>
                        <input type="text" class="form-control" name="item_observacao[]">
                    </div>
                </div>
            `;
            
            itemsContainer.appendChild(newItem);
        }

        // Função para remover um item do pedido
        function removeItem(button) {
            const itemRow = button.closest('.item-row');
            itemRow.remove();
        }
    </script>
</body>

</html>