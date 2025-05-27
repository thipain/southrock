<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../includes/db.php';

$loggedInUserId = null;
$loggedInUserType = null;

$stmtUser = $conn->prepare("SELECT id, tipo_usuario FROM usuarios WHERE username = ?");
$stmtUser->bind_param("s", $_SESSION['username']);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
if ($currentUserData = $resultUser->fetch_assoc()) {
    $loggedInUserId = $currentUserData['id'];
    $loggedInUserType = $currentUserData['tipo_usuario'];
}
$stmtUser->close();

if ($loggedInUserId === null || $loggedInUserType != 2) {
    echo "Acesso não autorizado ou tipo de usuário inválido para esta visualização de detalhes de filial.";
    exit();
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo "ID do pedido inválido.";
    exit();
}
$pedido_id_param = (int)$_GET['id'];

$sqlOrder = "SELECT p.id, p.data, p.tipo_pedido, p.status,
                    p.filial_usuario_id, 
                    p.filial_destino_id,
                    COALESCE(u_origem.nome_filial, u_origem.nome) AS nome_origem_display,
                    COALESCE(u_destino.nome_filial, u_destino.nome) AS nome_destino_display,
                    u_origem.cnpj AS cnpj_origem,
                    u_destino.cnpj AS cnpj_destino
             FROM pedidos p
             LEFT JOIN usuarios u_origem ON p.filial_usuario_id = u_origem.id
             LEFT JOIN usuarios u_destino ON p.filial_destino_id = u_destino.id
             WHERE p.id = ? AND (p.filial_usuario_id = ? OR p.filial_destino_id = ?)";

$stmtOrder = $conn->prepare($sqlOrder);
$stmtOrder->bind_param("iii", $pedido_id_param, $loggedInUserId, $loggedInUserId);
$stmtOrder->execute();
$resultOrder = $stmtOrder->get_result();
$pedidoOriginal = $resultOrder->fetch_assoc();
$stmtOrder->close();

if (!$pedidoOriginal) {
    echo "Pedido não encontrado ou sua filial não está envolvida neste pedido.";
    exit();
}

$sqlItems = "SELECT pi.id as item_id, pi.sku, pi.quantidade, pr.produto AS nome_produto
             FROM pedido_itens pi
             JOIN produtos pr ON pi.sku = pr.sku
             WHERE pi.pedido_id = ?";
$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param("i", $pedido_id_param);
$stmtItems->execute();
$resultItems = $stmtItems->get_result();
$items = [];
while ($row = $resultItems->fetch_assoc()) {
    $items[] = $row;
}
$stmtItems->close();

$nome_origem_display_formatted = htmlspecialchars($pedidoOriginal['nome_origem_display'] ?? 'N/A');
if ($pedidoOriginal['cnpj_origem']) {
    $cnpj_origem_s_f = preg_replace('/[^0-9]/', '', $pedidoOriginal['cnpj_origem']);
    if (strlen($cnpj_origem_s_f) == 14) {
        $cnpj_o_f = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj_origem_s_f);
        $nome_origem_display_formatted = htmlspecialchars($pedidoOriginal['nome_origem_display'] . ' (' . $cnpj_o_f . ')');
    } else {
        $nome_origem_display_formatted = htmlspecialchars($pedidoOriginal['nome_origem_display'] . ' (' . $pedidoOriginal['cnpj_origem'] . ')');
    }
}

$nome_destino_display_formatted = htmlspecialchars($pedidoOriginal['nome_destino_display'] ?? 'N/A');
if ($pedidoOriginal['filial_destino_id'] === null && $pedidoOriginal['tipo_pedido'] === 'requisicao') {
    $nome_destino_display_formatted = "Matriz";
} else if ($pedidoOriginal['cnpj_destino']) {
    $cnpj_destino_s_f = preg_replace('/[^0-9]/', '', $pedidoOriginal['cnpj_destino']);
    if (strlen($cnpj_destino_s_f) == 14) {
        $cnpj_d_f = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj_destino_s_f);
        $nome_destino_display_formatted = htmlspecialchars($pedidoOriginal['nome_destino_display'] . ' (' . $cnpj_d_f . ')');
    } else {
        $nome_destino_display_formatted = htmlspecialchars($pedidoOriginal['nome_destino_display'] . ' (' . $pedidoOriginal['cnpj_destino'] . ')');
    }
} else if ($pedidoOriginal['filial_destino_id'] === null) {
    $nome_destino_display_formatted = "Matriz/Admin";
}

$podeDevolver = (
    $loggedInUserId == $pedidoOriginal['filial_destino_id'] &&
    $pedidoOriginal['status'] === 'finalizado' &&
    !empty($items) &&
    $loggedInUserType == 2 
);

$return_destination_id_for_form = $pedidoOriginal['filial_usuario_id'];

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido #<?= htmlspecialchars($pedidoOriginal['id']) ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../css/pedidos.css">
    <style>
        body { display: flex; flex-direction: column; }
        .content { margin-left: 0; padding: 20px; width: 100%; }
        .top-bar { background-color: #343a40; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .top-bar a { color: white; text-decoration: none; margin-left: 15px; }
        .top-bar a:hover { color: #f8f9fa; }
        .top-bar .site-title { font-size: 1.5rem; font-weight: bold; }
        .item-row .form-control { max-width: 100px; display: inline-block; margin-left: 10px;}
        .btn-container { margin-top: 20px; }
        .header p { margin-bottom: 0.5rem; }
        .details-grid { display: grid; grid-template-columns: auto 1fr; gap: 5px 15px; margin-bottom: 1rem;}
        .details-grid strong { font-weight: bold; }
        .table-items-visualizacao td, .table-items-visualizacao th { padding: 0.75rem; vertical-align: top; border-top: 1px solid #dee2e6; }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="site-title">SouthRock Pedidos</div>
        <div>
            <a href="historico.php"><i class="fas fa-history"></i> Histórico de Pedidos</a>
            <a href="../../logout/logout.php"><i class="fas fa-sign-out-alt icon"></i> Sair</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h1>Detalhes do Pedido #<?= htmlspecialchars($pedidoOriginal['id']) ?></h1>
            <div class="details-grid">
                <strong>De:</strong> <span><?= $nome_origem_display_formatted ?></span>
                <strong>Para:</strong> <span><?= $nome_destino_display_formatted ?></span>
                <strong>Data:</strong> <span><?= date('d/m/Y H:i', strtotime($pedidoOriginal['data'])) ?></span>
                <strong>Tipo:</strong> <span><?= ucfirst(htmlspecialchars($pedidoOriginal['tipo_pedido'])) ?></span>
                <strong>Status:</strong> <span><span class="badge badge-<?= ($pedidoOriginal['status'] == 'novo' ? 'primary' : ($pedidoOriginal['status'] == 'processo' ? 'warning' : 'success')) ?>"><?= ucfirst(htmlspecialchars($pedidoOriginal['status'])) ?></span></span>
            </div>
            <hr class="barrinha">
        </div>

        <?php if (empty($items)): ?>
            <div class="alert alert-info mt-3" role="alert">
                Não há itens associados a este pedido.
            </div>
        <?php else: ?>
            <?php if ($podeDevolver): ?>
                <h3 class="mt-4">Registrar Devolução de Itens</h3>
                <p>Você está devolvendo itens para: <strong><?= $nome_origem_display_formatted ?></strong></p>
                <form action="processar_devolucao.php" method="POST">
                    <input type="hidden" name="original_pedido_id" value="<?= $pedido_id_param ?>">
                    <input type="hidden" name="return_destination_id" value="<?= $return_destination_id_for_form ?>">

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllItems" title="Selecionar/Desselecionar Todos"></th>
                                    <th>Produto (SKU)</th>
                                    <th>Quantidade Recebida</th>
                                    <th>Quantidade a Devolver</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr class="item-row">
                                    <td>
                                        <input type="checkbox" name="items[<?= $item['item_id'] ?>][selected]"
                                               value="<?= $item['sku'] ?>"
                                               class="item-checkbox"
                                               data-sku="<?= $item['sku'] ?>">
                                    </td>
                                    <td><?= htmlspecialchars($item['nome_produto']) ?> (<?= htmlspecialchars($item['sku']) ?>)</td>
                                    <td><?= $item['quantidade'] ?></td>
                                    <td>
                                        <input type="number" name="items[<?= $item['item_id'] ?>][quantity_to_return]"
                                               class="form-control form-control-sm item-quantity-return"
                                               min="0" max="<?= $item['quantidade'] ?>" value="0" disabled
                                               data-max-qty="<?= $item['quantidade'] ?>">
                                        <input type="hidden" name="items[<?= $item['item_id'] ?>][original_quantity]" value="<?= $item['quantidade'] ?>">
                                        <input type="hidden" name="items[<?= $item['item_id'] ?>][sku]" value="<?= $item['sku'] ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group">
                        <label for="motivo_devolucao">Motivo da Devolução (Opcional):</label>
                        <textarea name="motivo_devolucao" id="motivo_devolucao" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="btn-container">
                        <button type="button" class="btn btn-primary" id="returnAllButton">Devolver Todos os Itens Integralmente</button>
                        <button type="submit" class="btn btn-success" id="returnSelectedButton">Criar Pedido de Devolução para Selecionados</button>
                        <a href="historico.php" class="btn btn-secondary">Cancelar Devolução</a>
                    </div>
                </form>
            <?php else: ?>
                <h3 class="mt-4">Itens do Pedido</h3>
                <?php if ($loggedInUserId == $pedidoOriginal['filial_destino_id'] && $pedidoOriginal['status'] !== 'finalizado' && !empty($items)): ?>
                    <div class="alert alert-info">
                        A devolução de itens para este pedido não está disponível no momento. <br>
                        Motivo: O status do pedido é '<?= ucfirst(htmlspecialchars($pedidoOriginal['status'])) ?>' e precisa ser 'Finalizado'.
                    </div>
                <?php elseif ($loggedInUserId != $pedidoOriginal['filial_destino_id'] && !empty($items)): ?>
                     <div class="alert alert-info">
                        Este pedido foi originado por sua filial ou sua filial não é a destinatária final. Apenas o destinatário final pode iniciar uma devolução de um pedido finalizado.
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-items-visualizacao">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>SKU</th>
                                <th>Quantidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nome_produto']) ?></td>
                                <td><?= htmlspecialchars($item['sku']) ?></td>
                                <td><?= $item['quantidade'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="btn-container">
                     <a href="historico.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Voltar para Histórico</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if ($podeDevolver && !empty($items)): ?>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.item-checkbox').change(function() {
            const $row = $(this).closest('.item-row');
            const $qtyInput = $row.find('.item-quantity-return');
            if (this.checked) {
                $qtyInput.prop('disabled', false).val(1).attr('min', 1);
            } else {
                $qtyInput.prop('disabled', true).val(0).attr('min', 0);
            }
        });

        $('#selectAllItems').change(function() {
            const isChecked = this.checked;
            $('.item-checkbox').each(function() {
                $(this).prop('checked', isChecked).trigger('change');
            });
        });

        $('#returnAllButton').click(function() {
            $('#selectAllItems').prop('checked', true);
            $('.item-checkbox').each(function() {
                $(this).prop('checked', true);
                const $row = $(this).closest('.item-row');
                const $qtyInput = $row.find('.item-quantity-return');
                const maxQty = $qtyInput.data('max-qty');
                $qtyInput.prop('disabled', false).val(maxQty).attr('min',1);
            });
        });

        $('form[action="processar_devolucao.php"]').submit(function(e) { 
            let oneItemSelected = false;
            let validQuantities = true;
            $('.item-checkbox:checked').each(function() {
                oneItemSelected = true;
                const $row = $(this).closest('.item-row');
                const $qtyInput = $row.find('.item-quantity-return');
                const quantityToReturn = parseInt($qtyInput.val());
                const maxQuantity = parseInt($qtyInput.data('max-qty'));
                const itemSKU = $(this).data('sku');

                if (isNaN(quantityToReturn) || quantityToReturn <= 0 || quantityToReturn > maxQuantity) {
                    alert('Por favor, insira uma quantidade válida (entre 1 e ' + maxQuantity + ') para devolver o item SKU: ' + itemSKU);
                    $qtyInput.focus();
                    validQuantities = false;
                    return false; 
                }
            });

            if (!oneItemSelected) {
                alert('Por favor, selecione ao menos um item para devolução.');
                e.preventDefault();
                return;
            }
            if (!validQuantities) {
                e.preventDefault();
                return;
            }
            $('#returnSelectedButton').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processando...');
        });
    });
    </script>
    <?php endif; ?>
</body>
</html>