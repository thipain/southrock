<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['username']) || $_SESSION['tipo_usuario'] != 1) { // Apenas administradores podem editar
    header("Location: ../index.php");
    exit();
}

require_once '../../includes/db.php';

// --- Processamento do Formulário (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pedido_id = intval($_POST['pedido_id']);
    $tipo_pedido = $_POST['tipo_pedido'];
    $filial_usuario_id = intval($_POST['filial_usuario_id']);
    // Garante que filial_destino_id seja NULL se não for doação/troca ou estiver vazio
    $filial_destino_id = (isset($_POST['filial_destino_id']) && !empty($_POST['filial_destino_id']) && ($_POST['tipo_pedido'] == 'doacao' || $_POST['tipo_pedido'] == 'troca')) ? intval($_POST['filial_destino_id']) : NULL;
    $observacoes = $_POST['observacoes'];
    $status = $_POST['status']; // O status também pode ser editado pelo admin

    // Arrays para os itens do pedido
    $item_sku = $_POST['item_sku'] ?? [];
    $item_quantidade = $_POST['item_quantidade'] ?? [];
    $item_observacao = $_POST['item_observacao'] ?? [];

    $conn->begin_transaction(); // Inicia a transação

    try {
        // 1. Atualizar informações básicas do pedido
        $query_update_pedido = "UPDATE pedidos SET tipo_pedido = ?, status = ?, filial_usuario_id = ?, filial_destino_id = ?, observacoes = ? WHERE id = ?";
        $stmt_update_pedido = $conn->prepare($query_update_pedido);

        // Ajustar bind_param para filial_destino_id que pode ser NULL
        if ($filial_destino_id === NULL) {
            // "s" para string (tipo_pedido, status, observacoes), "i" para int (filial_usuario_id, pedido_id), "i" para filial_destino_id (mesmo se NULL, mysqli o trata corretamente)
            $stmt_update_pedido->bind_param("ssiisi", $tipo_pedido, $status, $filial_usuario_id, $filial_destino_id, $observacoes, $pedido_id);
        } else {
            // Usar "i" para filial_destino_id se não for NULL
            $stmt_update_pedido->bind_param("ssiiis", $tipo_pedido, $status, $filial_usuario_id, $filial_destino_id, $observacoes, $pedido_id);
        }
        
        $stmt_update_pedido->execute();
        $stmt_update_pedido->close();

        // 2. Excluir itens existentes do pedido para reinserir os novos
        $query_delete_itens = "DELETE FROM pedido_itens WHERE pedido_id = ?";
        $stmt_delete_itens = $conn->prepare($query_delete_itens);
        $stmt_delete_itens->bind_param("i", $pedido_id);
        $stmt_delete_itens->execute();
        $stmt_delete_itens->close();

        // 3. Inserir os novos/atualizados itens do pedido
        $query_insert_item = "INSERT INTO pedido_itens (pedido_id, sku, quantidade, observacao) VALUES (?, ?, ?, ?)";
        $stmt_insert_item = $conn->prepare($query_insert_item);

        for ($i = 0; $i < count($item_sku); $i++) {
            $sku = intval($item_sku[$i]);
            $quantidade = floatval($item_quantidade[$i]); // Pode ser decimal (step="0.01")
            $observacao_item = !empty($item_observacao[$i]) ? $item_observacao[$i] : NULL;

            // Validar SKU e quantidade antes de inserir
            if ($sku > 0 && $quantidade > 0) {
                // 'd' para double/float, 's' para string (observacao que pode ser NULL)
                $stmt_insert_item->bind_param("iids", $pedido_id, $sku, $quantidade, $observacao_item);
                $stmt_insert_item->execute();
            }
        }
        $stmt_insert_item->close();

        $conn->commit(); // Confirma a transação
        $_SESSION['success_message'] = "Pedido atualizado com sucesso!";
        header("Location: detalhes_pedido.php?id=" . $pedido_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Reverte a transação em caso de erro
        $_SESSION['error_message'] = "Erro ao atualizar o pedido: " . $e->getMessage();
        header("Location: detalhes_pedido.php?id=" . $pedido_id);
        exit();
    }
}

// --- Carregamento de Dados (GET) ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pedidos.php");
    exit();
}

$pedido_id = intval($_GET['id']);

// Buscar informações do pedido
// JOIN com usuarios para filial_usuario_id e filial_destino_id
$query = "SELECT p.*,
          u_origem.nome_filial AS nome_filial_origem,
          u_destino.nome_filial AS nome_filial_destino,
          usr.nome AS usuario_nome
          FROM pedidos p
          JOIN usuarios u_origem ON p.filial_usuario_id = u_origem.id
          LEFT JOIN usuarios u_destino ON p.filial_destino_id = u_destino.id AND u_destino.eh_filial = TRUE
          JOIN usuarios usr ON p.usuario_id = usr.id
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

// Verificar se o pedido já foi finalizado, rejeitado ou cancelado para impedir edição
if ($pedido['status'] === 'finalizado' || $pedido['status'] === 'rejeitado' || $pedido['status'] === 'cancelado') {
    $_SESSION['error_message'] = "Não é possível editar um pedido com status '" . $pedido['status'] . "'.";
    header("Location: detalhes_pedido.php?id=" . $pedido_id);
    exit();
}

// Buscar itens do pedido
$query_itens = "SELECT pi.sku, pi.quantidade, pi.observacao, prod.produto, prod.unidade_medida
                FROM pedido_itens pi
                JOIN produtos prod ON pi.sku = prod.sku
                WHERE pi.pedido_id = ?";
$stmt_itens = $conn->prepare($query_itens);
$stmt_itens->bind_param("i", $pedido_id);
$stmt_itens->execute();
$itens_pedido = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar todas as filiais (usuarios com eh_filial = TRUE)
$query_filiais = "SELECT id, nome_filial, cnpj FROM usuarios WHERE eh_filial = TRUE ORDER BY nome_filial";
$result_filiais = $conn->query($query_filiais);
$filiais = $result_filiais->fetch_all(MYSQLI_ASSOC);

// Buscar todos os produtos para o dropdown de itens
$query_produtos = "SELECT sku, produto, unidade_medida FROM produtos ORDER BY produto";
$result_produtos = $conn->query($query_produtos);
$produtos = $result_produtos->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pedido #<?= $pedido_id ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        /* Estilos específicos para este formulário, se necessário */
        .card-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item-row {
            border: 1px solid #e0e0e0;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            background-color: #fcfcfc;
            position: relative;
        }

        .remove-item {
            position: absolute;
            top: 5px;
            right: 5px;
            background: none;
            border: none;
            color: #dc3545;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            line-height: 1;
            transition: color 0.2s ease-in-out;
        }

        .remove-item:hover {
            color: #c82333;
        }

        .form-section label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
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
                <i class="fas fa-clipboard-list icon"></i>
                <span class="text">Pedidos</span>
            </a>
            <a href="produtos.php">
                <i class="fas fa-boxes icon"></i>
                <span class="text">Produtos</span>
            </a>
            <a href="usuarios.php">
                <i class="fas fa-users icon"></i>
                <span class="text">Usuários</span>
            </a>
            <a href="doar_pedidos.php">
                <i class="fas fa-gift icon"></i>
                <span class="text">Doar Pedidos</span>
            </a>
        </div>
        <div class="logout">
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt icon"></i>
                <span class="text">Sair</span>
            </a>
        </div>
    </div>

    <div class="content">
        <div class="container mt-4">
            <h1 class="mb-4">Editar Pedido #<?= $pedido_id ?></h1>

            <?php
            if (isset($_SESSION['success_message'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                unset($_SESSION['success_message']);
            }
            if (isset($_SESSION['error_message'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                unset($_SESSION['error_message']);
            }
            ?>

            <form action="editar_pedido.php" method="POST">
                <input type="hidden" name="pedido_id" value="<?= $pedido_id ?>">

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Informações do Pedido</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <label for="tipo_pedido" class="col-sm-3 col-form-label">Tipo de Pedido:</label>
                            <div class="col-sm-9">
                                <select class="form-control" id="tipo_pedido" name="tipo_pedido" required>
                                    <option value="requisicao" <?= ($pedido['tipo_pedido'] == 'requisicao') ? 'selected' : '' ?>>Requisição</option>
                                    <option value="troca" <?= ($pedido['tipo_pedido'] == 'troca') ? 'selected' : '' ?>>Troca</option>
                                    <option value="doacao" <?= ($pedido['tipo_pedido'] == 'doacao') ? 'selected' : '' ?>>Doação</option>
                                    <option value="devolucao" <?= ($pedido['tipo_pedido'] == 'devolucao') ? 'selected' : '' ?>>Devolução</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="status" class="col-sm-3 col-form-label">Status:</label>
                            <div class="col-sm-9">
                                <select class="form-control" id="status" name="status" required>
                                    <option value="novo" <?= ($pedido['status'] == 'novo') ? 'selected' : '' ?>>Novo</option>
                                    <option value="aprovado" <?= ($pedido['status'] == 'aprovado') ? 'selected' : '' ?>>Aprovado</option>
                                    <option value="processo" <?= ($pedido['status'] == 'processo') ? 'selected' : '' ?>>Em Processo</option>
                                    <option value="finalizado" <?= ($pedido['status'] == 'finalizado') ? 'selected' : '' ?>>Finalizado</option>
                                    <option value="rejeitado" <?= ($pedido['status'] == 'rejeitado') ? 'selected' : '' ?>>Rejeitado</option>
                                    <option value="cancelado" <?= ($pedido['status'] == 'cancelado') ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="filial_usuario_id" class="col-sm-3 col-form-label">Filial de Origem:</label>
                            <div class="col-sm-9">
                                <select class="form-control" id="filial_usuario_id" name="filial_usuario_id" required>
                                    <?php foreach ($filiais as $filial) : ?>
                                        <option value="<?= $filial['id'] ?>" <?= ($pedido['filial_usuario_id'] == $filial['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($filial['nome_filial']) ?> (<?= htmlspecialchars($filial['cnpj']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row" id="filial_destino_group" style="<?= ($pedido['tipo_pedido'] == 'doacao' || $pedido['tipo_pedido'] == 'troca') ? '' : 'display: none;' ?>">
                            <label for="filial_destino_id" class="col-sm-3 col-form-label">Filial de Destino (Doação/Troca):</label>
                            <div class="col-sm-9">
                                <select class="form-control" id="filial_destino_id" name="filial_destino_id">
                                    <option value="">Selecione a Filial de Destino</option>
                                    <?php foreach ($filiais as $filial) : ?>
                                        <option value="<?= $filial['id'] ?>" <?= ($pedido['filial_destino_id'] == $filial['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($filial['nome_filial']) ?> (<?= htmlspecialchars($filial['cnpj']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="observacoes" class="col-sm-3 col-form-label">Observações:</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= htmlspecialchars($pedido['observacoes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-4 mb-4">
                    <div class="card-header bg-primary text-white card-header-flex">
                        <h5 class="mb-0">Itens do Pedido</h5>
                        <button type="button" class="btn btn-light btn-sm" onclick="addItem()">
                            <i class="fas fa-plus"></i> Adicionar Item
                        </button>
                    </div>
                    <div class="card-body" id="items-container">
                        <?php if (!empty($itens_pedido)) : ?>
                            <?php foreach ($itens_pedido as $item) : ?>
                                <div class="item-row border p-3 mb-2 rounded position-relative">
                                    <button type="button" class="remove-item" onclick="removeItem(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <div class="row">
                                        <div class="col-md-5 form-section">
                                            <label>Produto</label>
                                            <select class="form-control" name="item_sku[]" required>
                                                <option value="">Selecione um Produto</option>
                                                <?php foreach ($produtos as $produto_option) : ?>
                                                    <option value="<?= $produto_option['sku'] ?>" <?= ($item['sku'] == $produto_option['sku']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($produto_option['produto']) ?> (<?= htmlspecialchars($produto_option['unidade_medida']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 form-section">
                                            <label>Quantidade</label>
                                            <input type="number" class="form-control" name="item_quantidade[]" min="0.01" step="0.01" value="<?= htmlspecialchars($item['quantidade']) ?>" required>
                                        </div>
                                        <div class="col-md-4 form-section">
                                            <label>Observação</label>
                                            <input type="text" class="form-control" name="item_observacao[]" value="<?= htmlspecialchars($item['observacao'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="text-muted" id="no-items-message">Nenhum item neste pedido. Adicione um item usando o botão "Adicionar Item".</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-footer text-right mt-4">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar Alterações</button>
                    <a href="detalhes_pedido.php?id=<?= $pedido_id ?>" class="btn btn-secondary"><i class="fas fa-times-circle"></i> Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../js/dashboard.js"></script>
    <script>
        // Não é mais necessário duplicar adjustLayout aqui, pois dashboard.js já o faz.
        // A função de ajuste de layout é centralizada no dashboard.js
        // e é chamada no DOMContentLoaded e no resize.

        // Lógica para mostrar/esconder filial_destino_id com base no tipo_pedido
        $('#tipo_pedido').change(function() {
            const tipo = $(this).val();
            if (tipo === 'doacao' || tipo === 'troca') {
                $('#filial_destino_group').show();
                $('#filial_destino_id').prop('required', true);
            } else {
                $('#filial_destino_group').hide();
                $('#filial_destino_id').prop('required', false);
                $('#filial_destino_id').val(''); // Limpa a seleção ao esconder
            }
        }).trigger('change'); // Dispara no carregamento para aplicar o estado inicial

        // Array de produtos para ser usado na função addItem()
        const productsData = <?= json_encode($produtos) ?>;

        // Função para adicionar um novo item ao pedido (formulário)
        function addItem() {
            const itemsContainer = document.getElementById('items-container');
            const noItemsMessage = document.getElementById('no-items-message');

            if (noItemsMessage) {
                noItemsMessage.style.display = 'none'; // Esconde a mensagem se estiver visível
            }

            const newItem = document.createElement('div');
            newItem.className = 'item-row border p-3 mb-2 rounded position-relative';

            // Gerar opções de produtos dinamicamente
            const productOptions = productsData.map(product => {
                return `<option value="${product.sku}">${product.produto} (${product.unidade_medida})</option>`;
            }).join('');

            newItem.innerHTML = `
                <button type="button" class="remove-item" onclick="removeItem(this)">
                    <i class="fas fa-times"></i>
                </button>
                <div class="row">
                    <div class="col-md-5 form-section">
                        <label>Produto</label>
                        <select class="form-control" name="item_sku[]" required>
                            <option value="">Selecione um Produto</option>
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
            Swal.fire({
                title: 'Tem certeza?',
                text: "Você removerá este item do pedido!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, remover!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const itemRow = button.closest('.item-row');
                    itemRow.remove();

                    // Se não houver mais itens, mostra a mensagem "Nenhum item..."
                    const itemsContainer = document.getElementById('items-container');
                    const noItemsMessage = document.getElementById('no-items-message');
                    const hasRealItems = itemsContainer.querySelector('.item-row') !== null;

                    if (!hasRealItems) {
                        if (!noItemsMessage) {
                            const p = document.createElement('p');
                            p.id = 'no-items-message';
                            p.className = 'text-muted';
                            p.textContent = 'Nenhum item neste pedido. Adicione um item usando o botão "Adicionar Item".';
                            itemsContainer.appendChild(p);
                        } else {
                            noItemsMessage.style.display = 'block';
                        }
                    }
                    Swal.fire(
                        'Removido!',
                        'O item foi removido.',
                        'success'
                    );
                }
            });
        }

        // Esconder a mensagem "Nenhum item..." se houver itens no carregamento inicial
        document.addEventListener('DOMContentLoaded', function() {
            const itemsContainer = document.getElementById('items-container');
            const noItemsMessage = document.getElementById('no-items-message');
            if (itemsContainer && noItemsMessage) {
                let hasRealItems = itemsContainer.querySelector('.item-row') !== null;
                if (hasRealItems) {
                    noItemsMessage.style.display = 'none';
                }
            }
        });
    </script>
</body>

</html>