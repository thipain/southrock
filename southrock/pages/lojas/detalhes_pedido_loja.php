<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit();
}

require_once '../../includes/db.php';

$loggedInUserId = null;
$loggedInUserType = null;

if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $loggedInUserType = $_SESSION['tipo_usuario'] ?? null;
} else {
    $stmtUserCheck = $conn->prepare("SELECT id, tipo_usuario FROM usuarios WHERE username = ?");
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
}

if ($loggedInUserId === null || $loggedInUserType != 2) {
    $_SESSION['error_message_loja'] = "Acesso não autorizado para esta página.";
    header("Location: ../../index.php");
    exit();
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message_loja'] = "ID do pedido inválido.";
    header("Location: historico.php");
    exit();
}
$pedido_id_param = (int)$_GET['id'];

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

$sqlItems = "SELECT pi.id as item_id, pi.sku, pi.quantidade, pr.produto AS nome_produto, pr.unidade_medida, pi.tipo_item_troca, pi.observacao
             FROM pedido_itens pi
             JOIN produtos pr ON pi.sku = pr.sku
             WHERE pi.pedido_id = ?
             ORDER BY pi.tipo_item_troca DESC, pr.produto ASC";
$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param("i", $pedido_id_param);
$stmtItems->execute();
$resultItems = $stmtItems->get_result();
$items_enviados_troca = [];
$items_recebidos_troca = [];
$items_normais = [];

while ($item_row = $resultItems->fetch_assoc()) {
    if ($pedido['tipo_pedido'] === 'troca') {
        if ($item_row['tipo_item_troca'] === 'enviado') {
            $items_enviados_troca[] = $item_row;
        } elseif ($item_row['tipo_item_troca'] === 'recebido') {
            $items_recebidos_troca[] = $item_row;
        }
    } else {
        $items_normais[] = $item_row;
    }
}
$stmtItems->close();

$nome_origem_display_formatted = htmlspecialchars($pedido['nome_origem_display'] ?? 'N/A');
if ($pedido['cnpj_origem']) $nome_origem_display_formatted .= ' (CNPJ: ' . htmlspecialchars($pedido['cnpj_origem']) . ')';

$nome_destino_display_formatted = htmlspecialchars($pedido['nome_destino_display'] ?? 'N/A');
if ($pedido['filial_destino_id'] === null && in_array($pedido['tipo_pedido'], ['requisicao', 'devolucao'])) {
    $nome_destino_display_formatted = "Matriz";
} elseif ($pedido['cnpj_destino']) {
    $nome_destino_display_formatted .= ' (CNPJ: ' . htmlspecialchars($pedido['cnpj_destino']) . ')';
} elseif ($pedido['filial_destino_id'] === null) {
     $nome_destino_display_formatted = "Matriz/Admin";
}

$podeResponderPropostaTroca = (
    $pedido['tipo_pedido'] === 'troca' &&
    $pedido['filial_destino_id'] == $loggedInUserId &&
    $pedido['status'] === 'novo_troca_pendente_aceite_parceiro'
);

$podeDevolverItens = ( /* Sua lógica existente para devolução aqui */
    $loggedInUserId == $pedido['filial_destino_id'] &&
    $pedido['status'] === 'finalizado' &&
    ($pedido['tipo_pedido'] === 'requisicao' || $pedido['tipo_pedido'] === 'doacao') &&
    (!empty($items_normais) || !empty($items_enviados_troca))
);
$return_destination_id_for_form = $pedido['filial_usuario_id'];

$statusDisplayClasses = [
    'novo' => 'badge-primary', 'processo' => 'badge-warning', 'finalizado' => 'badge-success',
    'aprovado' => 'badge-info', 'rejeitado' => 'badge-danger', 'cancelado' => 'badge-secondary',
    'novo_troca_pendente_aceite_parceiro' => 'badge-warning',
    'troca_aceita_parceiro_pendente_matriz' => 'badge-info'
];
$statusClass = $statusDisplayClasses[$pedido['status']] ?? 'badge-light';
$statusLabel = str_replace(['_', 'parceiro', 'matriz'], [' ', '(Parceiro)', '(Matriz)'], ucfirst($pedido['status']));
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
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
        .top-bar-loja { background-color: #343a40; color: white; padding: 0.75rem 1.25rem; margin-bottom: 1.5rem; display:flex; justify-content: space-between; align-items: center;}
        .top-bar-loja h1 { font-size: 1.5rem; margin-bottom: 0; font-weight: 500; }
        .top-bar-loja a { color: white; }
        .card { border-radius: 0.5rem; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
        .card-header { background-color: #e9ecef; font-weight: 500; }
        .details-grid { display: grid; grid-template-columns: auto 1fr; gap: 0.25rem 1rem; line-height: 1.8; }
        .details-grid strong { font-weight: 600; color: #495057; }
        .badge { font-size: 0.9em; padding: 0.45em 0.65em; }
        .action-buttons form { display: inline-block; margin-right: 10px; }
        .table-items th { background-color: #f8f9fa; font-weight: 500; }
        .table-items td, .table-items th { padding: 0.6rem; vertical-align: middle;}
        .section-title { margin-top: 1.75rem; margin-bottom: 0.85rem; font-size: 1.3rem; color: #0056b3; border-bottom: 1px solid #dee2e6; padding-bottom: 0.5rem;}
        .alert-dismissible .close { /* Para Bootstrap 4 */
            position: absolute; top: 0; right: 0; padding: 0.75rem 1.25rem; color: inherit;
        }
    </style>
</head>
<body>
    <div class="top-bar-loja">
        <h1>Detalhes do Pedido #<?= htmlspecialchars($pedido['id']) ?></h1>
         <div>
            <a href="fazer_pedidos.php" class="btn btn-sm btn-outline-light mr-2"><i class="fas fa-plus-circle"></i> Novo Pedido/Ação</a>
            <a href="historico.php" class="btn btn-sm btn-outline-light mr-2"><i class="fas fa-history"></i> Histórico</a>
            <a href="../../logout/logout.php" class="btn btn-sm btn-danger"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </div>

    <div class="container mt-3 mb-5">
        <?php if(isset($_SESSION['success_message_loja'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success_message_loja']); unset($_SESSION['success_message_loja']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error_message_loja'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error_message_loja']); unset($_SESSION['error_message_loja']); ?>
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
                    <strong>Status:</strong> <span><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></span>
                    <strong>De (Origem):</strong> <span><?= $nome_origem_display_formatted ?></span>
                    <strong>Para (Destino):</strong> <span><?= $nome_destino_display_formatted ?></span>
                    <?php if(!empty($pedido['observacoes'])): ?>
                        <strong>Observações:</strong> <span style="white-space: pre-wrap;"><?= htmlspecialchars($pedido['observacoes']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($podeResponderPropostaTroca): ?>
                    <div class="action-buttons mt-3 pt-3 border-top">
                        <h5 class="mb-2">Responder à Proposta de Troca:</h5>
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
                                <thead><tr><th>Produto (SKU)</th><th>Qtd</th><th>Obs.</th></tr></thead>
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
                                <thead><tr><th>Produto (SKU)</th><th>Qtd</th><th>Obs.</th></tr></thead>
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
                        <thead><tr><th>Produto (SKU)</th><th>Quantidade</th><th>Observação</th></tr></thead>
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
             <div class="alert alert-info mt-3">Não há itens para este tipo de pedido.</div>
        <?php endif; ?>

        <?php
        // Lógica para o botão de devolução (simplificada, a devolução real tem seu próprio fluxo)
        if ($loggedInUserId == $pedido['filial_destino_id'] && $pedido['status'] === 'finalizado' && 
            ($pedido['tipo_pedido'] === 'requisicao' || $pedido['tipo_pedido'] === 'doacao' || ($pedido['tipo_pedido'] === 'troca' && !empty($items_recebidos_troca))) ) {
                // Para 'troca', a devolução seria sobre os itens que esta filial RECEBEU.
                // A lógica completa de devolução foi movida para uma página dedicada se o processo for complexo.
                // Aqui, um link simples para iniciar o processo.
                // O formulário real de devolução estava em uma versão anterior do detalhes_pedido_loja.php
                // e foi removido para simplificar este fluxo e focar na aceitação de troca.
                // Se você precisar da devolução aqui, precisaria re-integrar aquele formulário.
        }
        ?>
         <div class="mt-4 text-center">
            <a href="historico.php" class="btn btn-secondary"><i class="fas fa-list-ul"></i> Voltar para Histórico</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
     <script>
        var alertList = document.querySelectorAll('.alert-dismissible');
        alertList.forEach(function (alert) {
            setTimeout(function() {
                if (alert && typeof $ !== 'undefined' && $.fn.alert) {
                    $(alert).alert('close');
                } else if (alert && typeof bootstrap !== 'undefined' && bootstrap.Alert && bootstrap.Alert.getInstance(alert)) {
                     bootstrap.Alert.getInstance(alert).close();
                }
            }, 7000);
        });
    </script>
</body>
</html>
<?php
if(isset($conn)) $conn->close();
?>