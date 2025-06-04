<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php"); // Ajuste para sua página de login
    exit();
}

require_once '../../includes/db.php';
require_once '../../includes/status_helper.php'; // Helper para status

$loggedInUserId = null;
$loggedInUserType = null;

// Assegura que temos o ID do usuário logado
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $loggedInUserType = $_SESSION['tipo_usuario'] ?? null;
} else {
    // Fallback para buscar user_id se não estiver na sessão (idealmente já deveria estar)
    $stmtUserCheck = $conn->prepare("SELECT id, tipo_usuario FROM usuarios WHERE username = ?");
    if ($stmtUserCheck) {
        $stmtUserCheck->bind_param("s", $_SESSION['username']);
        $stmtUserCheck->execute();
        $resultUserCheck = $stmtUserCheck->get_result();
        if ($currentUserData = $resultUserCheck->fetch_assoc()) {
            $_SESSION['user_id'] = $currentUserData['id'];
            $_SESSION['tipo_usuario'] = $currentUserData['tipo_usuario'];
            $loggedInUserId = $currentUserData['id'];
            $loggedInUserType = $currentUserData['tipo_usuario'];
        }
        $stmtUserCheck->close();
    } else {
        // Logar erro ou tratar falha na preparação da query
        error_log("Falha ao preparar stmtUserCheck em detalhes_pedido_loja.php: " . $conn->error);
        // Considerar uma mensagem de erro genérica ou redirecionamento
    }
}

// Verifica se o usuário é do tipo 'loja'
if ($loggedInUserId === null || $loggedInUserType != 2) {
    $_SESSION['error_message_loja'] = "Acesso não autorizado para esta página.";
    header("Location: ../../index.php");
    exit();
}

// Validação do ID do pedido via GET
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message_loja'] = "ID do pedido inválido."; // Esta é a área da sua linha 29 original
    header("Location: historico.php");
    exit();
}
$pedido_id_param = (int)$_GET['id'];

// Query principal para buscar dados do pedido
$sqlOrder = "SELECT p.*,
                    COALESCE(u_origem.nome_filial, u_origem.nome) AS nome_origem_display,
                    u_origem.cnpj AS cnpj_origem,
                    COALESCE(u_destino.nome_filial, u_destino.nome) AS nome_destino_display,
                    u_destino.cnpj AS cnpj_destino
             FROM pedidos p
             LEFT JOIN usuarios u_origem ON p.filial_usuario_id = u_origem.id
             LEFT JOIN usuarios u_destino ON p.filial_destino_id = u_destino.id
             WHERE p.id = ? AND (p.filial_usuario_id = ? OR p.filial_destino_id = ?)";

$stmtOrder = $conn->prepare($sqlOrder);
if (!$stmtOrder) {
    error_log("Erro ao preparar query sqlOrder em detalhes_pedido_loja.php: " . $conn->error);
    $_SESSION['error_message_loja'] = "Erro ao carregar dados do pedido.";
    header("Location: historico.php");
    exit();
}
$stmtOrder->bind_param("iii", $pedido_id_param, $loggedInUserId, $loggedInUserId);
$stmtOrder->execute();
$resultOrder = $stmtOrder->get_result();
$pedido = $resultOrder->fetch_assoc();
$stmtOrder->close();

if (!$pedido) {
    $_SESSION['error_message_loja'] = "Pedido não encontrado ou sua filial não está envolvida neste pedido.";
    header("Location: historico.php");
    exit();
}

// Buscar itens do pedido
$sqlItems = "SELECT pi.id as item_id, pi.sku, pi.quantidade, pr.produto AS nome_produto, pr.unidade_medida, pi.tipo_item_troca, pi.observacao
             FROM pedido_itens pi
             JOIN produtos pr ON pi.sku = pr.sku
             WHERE pi.pedido_id = ?
             ORDER BY pi.tipo_item_troca DESC, pr.produto ASC";
$stmtItems = $conn->prepare($sqlItems);
$items_enviados_troca = [];
$items_recebidos_troca = [];
$items_normais = [];
if ($stmtItems) {
    $stmtItems->bind_param("i", $pedido_id_param);
    $stmtItems->execute();
    $resultItems = $stmtItems->get_result();
    if ($resultItems) {
        while ($item_row = $resultItems->fetch_assoc()) {
            if ($pedido['tipo_pedido'] === 'troca') {
                if ($item_row['tipo_item_troca'] === 'enviado') $items_enviados_troca[] = $item_row;
                elseif ($item_row['tipo_item_troca'] === 'recebido') $items_recebidos_troca[] = $item_row;
            } else {
                $items_normais[] = $item_row;
            }
        }
    }
    $stmtItems->close();
} else {
    error_log("Erro ao preparar query sqlItems em detalhes_pedido_loja.php: " . $conn->error);
}

// Lógica para nomes de origem e destino
$nome_origem_display_formatted = htmlspecialchars($pedido['nome_origem_display'] ?? 'N/A');
if ($pedido['cnpj_origem']) $nome_origem_display_formatted .= ' (CNPJ: ' . htmlspecialchars($pedido['cnpj_origem']) . ')';

$nome_destino_display_formatted = htmlspecialchars($pedido['nome_destino_display'] ?? 'N/A');
if ($pedido['filial_destino_id'] === null && in_array($pedido['tipo_pedido'], ['requisicao', 'devolucao'])) {
    $nome_destino_display_formatted = "Matriz";
} elseif ($pedido['cnpj_destino']) {
    $nome_destino_display_formatted .= ' (CNPJ: ' . htmlspecialchars($pedido['cnpj_destino']) . ')';
} elseif ($pedido['filial_destino_id'] === null && $pedido['tipo_pedido'] !== 'troca') {
    $nome_destino_display_formatted = "Matriz/Admin";
}

// Permissões para ações
$podeResponderPropostaTroca = (
    $pedido['tipo_pedido'] === 'troca' &&
    $pedido['filial_destino_id'] == $loggedInUserId &&
    $pedido['status'] === 'novo_troca_pendente_aceite_parceiro'
);
$podeDevolverItens = (
    $loggedInUserId == $pedido['filial_destino_id'] &&
    $pedido['status'] === 'finalizado' &&
    ($pedido['tipo_pedido'] === 'requisicao' || $pedido['tipo_pedido'] === 'doacao' || ($pedido['tipo_pedido'] === 'troca' && !empty($items_recebidos_troca))) &&
    (!empty($items_normais) || !empty($items_recebidos_troca) || !empty($items_enviados_troca))
);
$return_destination_id_for_form = $pedido['filial_usuario_id'];

// Usando o status_helper.php
$statusLabel = getStatusLabel($pedido['status']);
$statusClass = getStatusBadgeClass($pedido['status']);

$nome_sistema_atual = "SouthRock Detalhes";
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido #<?= htmlspecialchars($pedido['id']) ?> - <?= htmlspecialchars($nome_sistema_atual) ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 70px;
        }

        /* Espaço para navbar fixa */
        .top-bar-loja {
            background-color: #343a40;
            color: white;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }

        .top-bar-loja h1 {
            font-size: 1.5rem;
            margin-bottom: 0;
            font-weight: 500;
        }

        .top-bar-loja a {
            color: white;
            text-decoration: none;
        }

        .top-bar-loja .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .card {
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #e9ecef;
            font-weight: 500;
        }

        .details-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.25rem 1rem;
            line-height: 1.8;
        }

        .details-grid strong {
            font-weight: 600;
            color: #495057;
        }

        .badge {
            font-size: 0.9em;
            padding: 0.45em 0.65em;
        }

        .action-buttons form {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 5px;
        }

        .table-items th {
            background-color: #f8f9fa;
            font-weight: 500;
        }

        .table-items td,
        .table-items th {
            padding: 0.6rem;
            vertical-align: middle;
        }

        .section-title {
            margin-top: 1.75rem;
            margin-bottom: 0.85rem;
            font-size: 1.3rem;
            color: #0056b3;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.5rem;
        }

        .alert-dismissible .close {
            position: absolute;
            top: 0;
            right: 0;
            padding: 0.75rem 1.25rem;
            color: inherit;
        }
    </style>
</head>

<body>
    <nav class="top-bar-loja navbar navbar-expand-sm navbar-dark">
        <div class="container-fluid">
            <h1 class="navbar-brand mb-0">Detalhes Pedido #<?= htmlspecialchars($pedido['id']) ?></h1>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#lojaPageNav" aria-controls="lojaPageNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="lojaPageNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item"><a href="fazer_pedidos.php" class="nav-link"><i class="fas fa-plus-circle"></i> Novo Pedido/Ação</a></li>
                    <li class="nav-item"><a href="historico.php" class="nav-link"><i class="fas fa-history"></i> Histórico</a></li>
                    <li class="nav-item"><a href="../../logout/logout.php" class="btn btn-sm btn-danger ml-2"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-3 mb-5">
        <?php if (isset($_SESSION['success_message_loja'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message_loja']);
                unset($_SESSION['success_message_loja']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message_loja'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message_loja']);
                unset($_SESSION['error_message_loja']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Informações Gerais do Pedido
            </div>
            <div class="card-body">
                <div class="details-grid">
                    <strong>Nº Pedido:</strong> <span>#<?= htmlspecialchars($pedido['id']) ?></span>
                    <strong>Data:</strong> <span><?= date('d/m/Y H:i', strtotime($pedido['data'])) ?></span>
                    <strong>Tipo:</strong> <span><?= ucfirst(htmlspecialchars($pedido['tipo_pedido'])) ?></span>
                    <strong>Status:</strong> <span><span class="<?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($statusLabel) ?></span></span>
                    <strong>De (Origem):</strong> <span><?= $nome_origem_display_formatted ?></span>
                    <strong>Para (Destino):</strong> <span><?= $nome_destino_display_formatted ?></span>
                    <?php if (!empty($pedido['observacoes'])): ?>
                        <strong>Observações:</strong> <span style="white-space: pre-wrap;"><?= htmlspecialchars($pedido['observacoes']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($podeResponderPropostaTroca): ?>
                    <div class="action-buttons mt-3 pt-3 border-top">
                        <h5 class="mb-2">Responder à Proposta de Troca Recebida:</h5>
                        <form action="responder_proposta_troca.php" method="POST">
                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                            <button type="submit" name="acao_troca" value="aceitar" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Aceitar Proposta
                            </button>
                        </form>
                        <form action="responder_proposta_troca.php" method="POST">
                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                            <button type="submit" name="acao_troca" value="rejeitar" class="btn btn-danger btn-sm">
                                <i class="fas fa-times"></i> Rejeitar Proposta
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($pedido['tipo_pedido'] === 'troca'): ?>
            <h4 class="section-title">Itens da Troca</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mt-2">
                        <div class="card-header">
                            <i class="fas fa-arrow-up text-warning"></i> Itens Enviados por: <strong><?= htmlspecialchars($pedido['nome_origem_display']) ?></strong>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($items_enviados_troca)): ?>
                                <table class="table table-sm table-striped table-items mb-0">
                                    <thead>
                                        <tr>
                                            <th>Produto (SKU)</th>
                                            <th>Qtd</th>
                                            <th>Obs.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items_enviados_troca as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['nome_produto']) ?> (<?= htmlspecialchars($item['sku']) ?>)</td>
                                                <td><?= rtrim(rtrim(number_format($item['quantidade'], 2, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($item['unidade_medida']) ?></td>
                                                <td><?= htmlspecialchars($item['observacao'] ?: '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?> <p class="text-muted p-3 mb-0">Nenhum item a ser enviado nesta proposta.</p> <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mt-2">
                        <div class="card-header">
                            <i class="fas fa-arrow-down text-success"></i> Itens Solicitados (a serem enviados por você, <?= htmlspecialchars($pedido['nome_destino_display']) ?>):
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($items_recebidos_troca)): ?>
                                <table class="table table-sm table-striped table-items mb-0">
                                    <thead>
                                        <tr>
                                            <th>Produto (SKU)</th>
                                            <th>Qtd</th>
                                            <th>Obs.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items_recebidos_troca as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['nome_produto']) ?> (<?= htmlspecialchars($item['sku']) ?>)</td>
                                                <td><?= rtrim(rtrim(number_format($item['quantidade'], 2, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($item['unidade_medida']) ?></td>
                                                <td><?= htmlspecialchars($item['observacao'] ?: '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?> <p class="text-muted p-3 mb-0">Nenhum item solicitado para recebimento nesta proposta.</p> <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif (!empty($items_normais)): ?>
            <h4 class="section-title">Itens do Pedido</h4>
            <div class="card mt-2">
                <div class="card-body p-0">
                    <table class="table table-striped table-items table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Produto (SKU)</th>
                                <th>Quantidade</th>
                                <th>Observação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items_normais as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nome_produto']) ?> (<?= htmlspecialchars($item['sku']) ?>)</td>
                                    <td><?= rtrim(rtrim(number_format($item['quantidade'], 2, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($item['unidade_medida']) ?></td>
                                    <td><?= htmlspecialchars($item['observacao'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-3">Não há itens para este pedido.</div>
        <?php endif; ?>

        <?php
        if (isset($_GET['devolver']) && $_GET['devolver'] === 'true' && $podeDevolverItens) {
            echo '<div class="card mt-3" id="form_devolucao"><div class="card-body">';
            echo '<h5 class="card-title">Registrar Devolução de Itens Recebidos</h5>';
            echo '<form action="processar_devolucao.php" method="POST">';
            echo '<input type="hidden" name="original_pedido_id" value="' . htmlspecialchars($pedido_id_param) . '">';
            echo '<input type="hidden" name="return_destination_id" value="' . htmlspecialchars($return_destination_id_for_form) . '">';

            $itens_para_formulario_devolucao = ($pedido['tipo_pedido'] === 'troca') ? $items_recebidos_troca : $items_normais;

            if (!empty($itens_para_formulario_devolucao)) {
                echo '<div class="table-responsive"><table class="table table-sm">';
                echo '<thead><tr><th><input type="checkbox" id="selectAllReturnItems" title="Selecionar Todos"></th><th>Produto (SKU)</th><th>Qtd. Recebida</th><th>Qtd. a Devolver</th></tr></thead><tbody>';

                foreach ($itens_para_formulario_devolucao as $idx => $item_dev) {
                    echo '<tr>';
                    // Usar um ID único para o item, como o id do pedido_itens se disponível e único, ou sku
                    // Se item_id (de pedido_itens) é único por linha, é melhor
                    $itemIdForm = htmlspecialchars($item_dev['item_id'] ?? $item_dev['sku']); // Fallback para SKU se item_id não estiver

                    echo '<td><input type="checkbox" name="items[' . $itemIdForm . '][selected]" value="' . htmlspecialchars($item_dev['sku']) . '" class="item-checkbox-return"></td>';
                    echo '<td>' . htmlspecialchars($item_dev['nome_produto']) . ' (' . htmlspecialchars($item_dev['sku']) . ')</td>';
                    echo '<td>' . htmlspecialchars($item_dev['quantidade']) . '</td>';
                    echo '<td><input type="number" name="items[' . $itemIdForm . '][quantity_to_return]" class="form-control form-control-sm item-quantity-return-input" min="0" max="' . htmlspecialchars($item_dev['quantidade']) . '" value="0" disabled style="width:80px;"></td>';
                    echo '<input type="hidden" name="items[' . $itemIdForm . '][original_quantity]" value="' . htmlspecialchars($item_dev['quantidade']) . '">';
                    echo '<input type="hidden" name="items[' . $itemIdForm . '][sku]" value="' . htmlspecialchars($item_dev['sku']) . '">'; // Envia SKU também
                    echo '</tr>';
                }
                echo '</tbody></table></div>';

                echo '<div class="form-group mt-2">';
                echo '<label for="motivo_devolucao">Motivo da Devolução (Opcional):</label>';
                echo '<textarea name="motivo_devolucao" id="motivo_devolucao" class="form-control form-control-sm" rows="2"></textarea>';
                echo '</div>';
                echo '<button type="submit" class="btn btn-success btn-sm mt-2"><i class="fas fa-paper-plane"></i> Enviar Pedido de Devolução</button>';
            } else {
                echo '<p class="text-muted">Não há itens elegíveis para devolução neste pedido.</p>';
            }
            echo '</form>';
            echo '</div></div>';
        } elseif ($podeDevolverItens) { // Botão para mostrar o formulário de devolução
            echo '<div class="mt-4 pt-3 border-top">';
            echo '<a href="detalhes_pedido_loja.php?id=' . $pedido_id_param . '&devolver=true#form_devolucao" class="btn btn-warning btn-sm">';
            echo '<i class="fas fa-undo-alt"></i> Iniciar Devolução de Itens Recebidos';
            echo '</a>';
            echo '<p class="text-muted small mt-1">Permitido para itens que sua filial recebeu e o pedido está finalizado.</p>';
            echo '</div>';
        }
        ?>
        <div class="mt-4 text-center">
            <a href="historico.php" class="btn btn-secondary"><i class="fas fa-list-ul"></i> Voltar para Histórico</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            window.setTimeout(function() {
                // Funciona para Bootstrap 4. Para Bootstrap 5, usar o método .getInstance(alert).close() se preferir.
                $(".alert-dismissible").fadeTo(500, 0).slideUp(500, function() {
                    $(this).remove();
                });
            }, 7000); // 7 segundos

            if ($('#form_devolucao').length) {
                $('#selectAllReturnItems').change(function() {
                    const isChecked = $(this).is(':checked');
                    $('.item-checkbox-return').prop('checked', isChecked).trigger('change');
                });

                $('.item-checkbox-return').change(function() {
                    const $row = $(this).closest('tr');
                    const $qtyInput = $row.find('.item-quantity-return-input');
                    if ($(this).is(':checked')) {
                        $qtyInput.prop('disabled', false).val(1).attr('min', 1);
                    } else {
                        $qtyInput.prop('disabled', true).val(0).attr('min', 0);
                    }
                });

                $('form[action="processar_devolucao.php"]').submit(function(e) {
                    let oneItemSelected = false;
                    let validQuantities = true;
                    $('.item-checkbox-return:checked').each(function() {
                        oneItemSelected = true;
                        const $row = $(this).closest('tr');
                        const $qtyInput = $row.find('.item-quantity-return-input');
                        const qtyToReturn = parseInt($qtyInput.val());
                        const maxQty = parseInt($qtyInput.attr('max'));
                        const sku = $(this).val(); // O valor do checkbox é o SKU

                        if (isNaN(qtyToReturn) || qtyToReturn <= 0 || qtyToReturn > maxQty) {
                            alert(`Quantidade inválida para SKU ${sku}. Deve ser entre 1 e ${maxQty}.`);
                            $qtyInput.focus();
                            validQuantities = false;
                            return false;
                        }
                    });
                    if (!oneItemSelected) {
                        alert('Selecione ao menos um item para devolução.');
                        e.preventDefault();
                    }
                    if (!validQuantities) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>

</html>
<?php
if (isset($conn)) $conn->close();
?>