<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['tipo_usuario'] != 1) { // Apenas Admin
    header("Location: ../../index.php");
    exit();
}

require_once '../../includes/db.php';
require_once '../../includes/status_helper.php'; // INCLUINDO O HELPER DE STATUS

$path_to_css_folder_from_page = '../../css/';
$logo_image_path_from_page = '../../images/zamp.png';
$logout_script_path_from_page = '../../logout/logout.php';

$link_dashboard = 'dashboard.php';
$link_pedidos_admin = 'pedidos.php';
$link_produtos_admin = 'produtos.php';
$link_usuarios_admin = 'usuarios.php';
$link_cadastro_usuario_admin = 'cadastro_usuario.php';

$nome_sistema_atual = "SouthRock Admin";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message_pedidos'] = "ID do pedido inválido.";
    header("Location: pedidos.php");
    exit();
}
$pedido_id = intval($_GET['id']);

// Busca do pedido $pedido
$query_pedido_det = "SELECT p.*, 
          u_origem.nome_filial AS nome_filial_origem, u_origem.nome AS nome_usuario_origem,
          u_origem.cnpj AS cnpj_origem, u_origem.endereco AS endereco_origem, 
          u_origem.cidade AS cidade_origem, u_origem.uf AS uf_origem,
          u_destino.nome_filial AS nome_filial_destino, u_destino.nome AS nome_usuario_destino,
          u_destino.cnpj AS cnpj_destino, u_destino.endereco AS endereco_destino,
          u_destino.cidade AS cidade_destino, u_destino.uf AS uf_destino,
          u_processador.nome as nome_usuario_processador,
          po.id as pedido_original_referencia_id
          FROM pedidos p 
          LEFT JOIN usuarios u_origem ON p.filial_usuario_id = u_origem.id
          LEFT JOIN usuarios u_destino ON p.filial_destino_id = u_destino.id
          LEFT JOIN usuarios u_processador ON p.usuario_id = u_processador.id
          LEFT JOIN pedidos po ON p.pedido_original_id = po.id
          WHERE p.id = ?";
$stmt_pedido_det = $conn->prepare($query_pedido_det);
// (Adicione verificação de erro para prepare, bind_param, execute)
$stmt_pedido_det->bind_param("i", $pedido_id);
$stmt_pedido_det->execute();
$resultado_pedido_det = $stmt_pedido_det->get_result();
$pedido = $resultado_pedido_det->fetch_assoc();
$stmt_pedido_det->close();

if (!$pedido) {
    $_SESSION['error_message_pedidos'] = "Pedido não encontrado.";
    header("Location: pedidos.php");
    exit();
}

// Busca dos itens do pedido $itens_pedido_array
$query_itens_det = "SELECT i.*, pr.produto, pr.unidade_medida, i.tipo_item_troca
                FROM pedido_itens i
                JOIN produtos pr ON i.sku = pr.sku
                WHERE i.pedido_id = ?
                ORDER BY i.tipo_item_troca DESC, pr.produto ASC";
$stmt_itens_det = $conn->prepare($query_itens_det);
$itens_pedido_array = [];
if ($stmt_itens_det) {
    $stmt_itens_det->bind_param("i", $pedido_id);
    $stmt_itens_det->execute();
    $itens_result_det = $stmt_itens_det->get_result();
    if ($itens_result_det) {
        while ($item_row_det = $itens_result_det->fetch_assoc()) {
            $itens_pedido_array[] = $item_row_det;
        }
    }
    $stmt_itens_det->close();
}

// Separação dos itens (lógica mantida)
$items_enviados_admin = [];
$items_recebidos_admin = [];
$items_normais_admin = [];
if ($pedido['tipo_pedido'] === 'troca') {
    foreach ($itens_pedido_array as $item_adm) {
        if ($item_adm['tipo_item_troca'] === 'enviado') $items_enviados_admin[] = $item_adm;
        elseif ($item_adm['tipo_item_troca'] === 'recebido') $items_recebidos_admin[] = $item_adm;
    }
} else {
    $items_normais_admin = $itens_pedido_array;
}

// Nomes de origem/destino (lógica mantida)
$nome_origem_final = htmlspecialchars($pedido['nome_filial_origem'] ?: $pedido['nome_usuario_origem'] ?: 'Origem Desconhecida');
if ($pedido['cnpj_origem']) $nome_origem_final .= " (CNPJ: " . htmlspecialchars($pedido['cnpj_origem']) . ")";
$nome_destino_final = 'N/A';
if ($pedido['filial_destino_id']) {
    $nome_destino_final = htmlspecialchars($pedido['nome_filial_destino'] ?: $pedido['nome_usuario_destino'] ?: 'Destino Desconhecido');
    if ($pedido['cnpj_destino']) $nome_destino_final .= " (CNPJ: " . htmlspecialchars($pedido['cnpj_destino']) . ")";
} else {
    if (in_array($pedido['tipo_pedido'], ['requisicao', 'devolucao'])) $nome_destino_final = "Matriz";
}

// Usando o status_helper.php
$currentStatusLabel = getStatusLabel($pedido['status']);
$currentStatusBadgeClass = getStatusBadgeClass($pedido['status']);
$currentStatusIconClass = getStatusIconClass($pedido['status']);

$tipoPedidoInfo = [
    'requisicao' => ['icon' => 'fa-file-invoice', 'label' => 'Requisição'],
    'troca' => ['icon' => 'fa-exchange-alt', 'label' => 'Troca'],
    'doacao' => ['icon' => 'fa-gift', 'label' => 'Doação'],
    'devolucao' => ['icon' => 'fa-undo-alt', 'label' => 'Devolução']
];
$tipoPedido = $tipoPedidoInfo[$pedido['tipo_pedido']] ?? ['icon' => 'fa-question-circle', 'label' => ucfirst($pedido['tipo_pedido'])];

// Cores para o type-icon-wrapper e type-icon (pode ser mantida ou simplificada)
$typeIconWrapperBg = 'rgba(var(--primary-color-rgb), 0.1)';
$typeIconColor = 'var(--primary-color)';
if ($pedido['tipo_pedido'] === 'troca') {
    $typeIconWrapperBg = 'rgba(23,162,184,0.1)';
    $typeIconColor = '#17a2b8';
} else if ($pedido['tipo_pedido'] === 'doacao') {
    $typeIconWrapperBg = 'rgba(40,167,69,0.1)';
    $typeIconColor = '#28a745';
} else if ($pedido['tipo_pedido'] === 'devolucao') {
    $typeIconWrapperBg = 'rgba(255,193,7,0.1)';
    $typeIconColor = '#ffc107';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes Pedido #<?= htmlspecialchars($pedido_id) ?> - <?= htmlspecialchars($nome_sistema_atual) ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <?php
    if (file_exists(__DIR__ . '/../../includes/header_com_menu.php')) {
        include __DIR__ . '/../../includes/header_com_menu.php';
    }
    ?>
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/pedidos.css">
    <style>
        /* SEU CSS EMBUTIDO ORIGINAL COMPLETO VAI AQUI */
        :root {
            --primary-color: #3949AB;
            --primary-color-rgb: 57, 73, 171;
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

        body.hcm-body-fixed-header {
            background-color: var(--light-bg);
            color: var(--dark-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .detalhes-header-pagina {
            margin-bottom: 30px;
        }

        .detalhes-header-pagina h1 {
            margin-top: 10px;
            background-color: transparent;
            font-size: 1.8rem;
            color: var(--dark-text);
            font-weight: 600;
        }

        .detalhes-barrinha {
            border: none;
            height: 2px;
            background-color: var(--primary-color);
            opacity: 0.6;
            margin-top: 0.5rem;
            width: 80px;
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
            padding: 25px 25px 0 25px;
        }

        .order-id-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .order-id {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .order-number {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }

        .order-date {
            color: var(--light-text);
            font-size: 0.9rem;
            margin-top: 4px;
        }

        .type-icon-wrapper {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .type-icon {
            font-size: 1.5rem;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* A cor de fundo e texto virá das classes do helper */
        .progress-container {
            padding: 0 25px 25px 25px;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 40px;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 4px;
            background-color: #E0E0E0;
            z-index: 1;
            transform: translateY(-50%);
        }

        .progress-step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 33.33%;
        }

        .step-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: white;
            border: 2px solid #E0E0E0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            color: #B0BEC5;
        }

        .step-icon i {
            font-size: 1.1rem;
        }

        .step-text {
            color: var(--light-text);
            font-size: 0.85rem;
            font-weight: 500;
            text-align: center;
        }

        .step-date {
            font-size: 0.75rem;
            color: var(--light-text);
            margin-top: 4px;
        }

        .progress-line {
            position: absolute;
            top: 50%;
            left: 0;
            height: 4px;
            z-index: 1;
            transition: width 0.5s ease;
            transform: translateY(-50%);
        }

        .progress-step.completed .step-icon,
        .progress-step.active .step-icon {
            color: white;
        }

        .progress-step.completed .step-icon {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .progress-step.completed .step-text {
            color: var(--dark-text);
            font-weight: 600;
        }

        .progress-step.active .step-icon {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }

        .progress-step.active .step-text {
            color: var(--warning-color);
            font-weight: 600;
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

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            margin-bottom: 10px;
        }

        .info-label {
            color: var(--light-text);
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 500;
            word-break: break-word;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn.custom-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn.custom-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn.custom-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }

        .btn.custom-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: white;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
        }

        .items-table th {
            background-color: #F5F7FA;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark-text);
            border-bottom: 1px solid #EEEEEE;
        }

        .items-table th:first-child,
        .items-table td:first-child {
            border-top-left-radius: var(--border-radius);
            border-bottom-left-radius: var(--border-radius);
        }

        .items-table th:last-child,
        .items-table td:last-child {
            border-top-right-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #EEEEEE;
            color: var(--dark-text);
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .items-table tbody tr:hover {
            background-color: #FDFEFE;
        }

        .address-card {
            background-color: #F9FAFC;
            border-radius: var(--border-radius);
            padding: 20px;
            border: 1px solid #E0E0E0;
        }

        .filial-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .filial-cards {
                grid-template-columns: 1fr 1fr;
            }
        }

        .filial-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
        }

        .filial-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            border-bottom: 1px solid #EEEEEE;
            padding-bottom: 10px;
        }

        .filial-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: white;
        }

        .filial-icon.origem {
            background-color: var(--primary-color);
        }

        .filial-icon.destino {
            background-color: var(--success-color);
        }

        .filial-title {
            font-weight: 600;
            font-size: 1rem;
            color: var(--dark-text);
        }

        @media print {
            body.hcm-body-fixed-header {
                background-color: white;
                padding-top: 0 !important;
            }

            .hcm-navbar,
            .hcm-sidebar {
                display: none !important;
            }

            .hcm-main-content,
            .container {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }

            .detalhes-header-pagina,
            .order-actions,
            .back-button {
                display: none !important;
            }

            .order-main-card {
                box-shadow: none;
                border: 1px solid #EEEEEE;
                margin: 0;
                border-radius: 0;
            }

            .content-section,
            .order-header,
            .progress-container {
                padding: 15px;
            }

            .items-table th,
            .items-table td {
                font-size: 0.8rem;
                padding: 8px;
            }

            .filial-cards {
                grid-template-columns: 1fr !important;
            }
        }

        .alert-dismissible .btn-close {
            padding: 0.75rem 1rem;
            box-sizing: content-box;
        }

        .badge.p-2 {
            padding: 0.5rem .75rem !important;
        }
    </style>
</head>

<body class="hcm-body-fixed-header">
    <div class="hcm-main-content">
        <div class="container py-4">
            <div class="detalhes-header-pagina">
                <h1>Detalhes do Pedido</h1>
                <hr class="detalhes-barrinha">
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message_pedidos'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error_message_pedidos']);
                    unset($_SESSION['error_message_pedidos']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error_message']);
                    unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="main-content-detalhes-pagina">
                <a href="pedidos.php" class="back-button mb-3">
                    <i class="fas fa-arrow-left"></i> Voltar para Lista de Pedidos
                </a>

                <div class="order-main-card">
                    <div class="order-header">
                        <div class="order-id-container">
                            <div class="order-id">
                                <div class="type-icon-wrapper" style="background-color: <?= htmlspecialchars($typeIconWrapperBg) ?>;">
                                    <i class="fas <?= htmlspecialchars($tipoPedido['icon']) ?> type-icon" style="color: <?= htmlspecialchars($typeIconColor) ?>;"></i>
                                </div>
                                <div>
                                    <h2 class="order-number">Pedido #<?= htmlspecialchars($pedido['id']) ?></h2>
                                    <div class="order-date">
                                        <?= htmlspecialchars(ucfirst($tipoPedido['label'])) ?> • Criado em: <?= date('d/m/Y \à\s H:i', strtotime($pedido['data'])) ?>
                                        <br>Última ação por: <?= htmlspecialchars($pedido['nome_usuario_processador'] ?? 'Sistema') ?>
                                        <?php if ($pedido['pedido_original_referencia_id']): ?>
                                            • Originado do Pedido: <a href="detalhes_pedido.php?id=<?= htmlspecialchars($pedido['pedido_original_id']) ?>">#<?= htmlspecialchars($pedido['pedido_original_id']) ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="status-container">
                                <div class="status-badge <?= htmlspecialchars($currentStatusBadgeClass) ?>">
                                    <i class="fas <?= htmlspecialchars($currentStatusIconClass) ?>"></i>
                                    <?= htmlspecialchars($currentStatusLabel) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="progress-container">
                        <?php
                        // (Lógica de progresso PHP - mantida da sua última versão)
                        $progressWidthPercent = 0;
                        $dataCriado = $pedido['data'];
                        $dataIntermediaria = $pedido['data_processamento'] ?? null;
                        $dataFinalizacao = $pedido['data_finalizacao'] ?? null;
                        $stepCriadoLabel = "Criado";
                        $stepMeioLabel = "Processamento";
                        $stepCriadoClass = 'completed';
                        $stepMeioClass = 'pending';
                        $stepFinalizadoClass = 'pending';
                        $iconMeio = 'fa-hourglass-half';
                        $iconFinalizado = 'fa-flag-checkered';
                        switch ($pedido['status']) {
                            case 'novo':
                                $progressWidthPercent = 5;
                                break;
                            case 'novo_troca_pendente_aceite_parceiro':
                                $stepCriadoLabel = "Proposta Enviada";
                                $stepMeioLabel = getStatusLabel('novo_troca_pendente_aceite_parceiro');
                                $progressWidthPercent = 25;
                                $iconMeio = getStatusIconClass('novo_troca_pendente_aceite_parceiro');
                                break;
                            case 'aprovado':
                                $stepCriadoLabel = "Aprovado Matriz";
                                $progressWidthPercent = 33;
                                break;
                            case 'troca_aceita_parceiro_pendente_matriz':
                                $stepCriadoLabel = "Proposta Enviada";
                                $stepMeioLabel = getStatusLabel('troca_aceita_parceiro_pendente_matriz');
                                $dataIntermediaria = $pedido['data_atualizacao'];
                                $stepMeioClass = 'completed';
                                $iconMeio = getStatusIconClass('troca_aceita_parceiro_pendente_matriz');
                                $progressWidthPercent = 66;
                                $stepFinalizadoClass = 'pending';
                                $iconFinalizado = 'fa-cogs';
                                break;
                            case 'processo':
                                $progressWidthPercent = ($pedido['tipo_pedido'] === 'troca') ? 80 : 50;
                                $stepMeioClass = 'active';
                                $iconMeio = getStatusIconClass('processo');
                                break;
                            case 'finalizado':
                                $progressWidthPercent = 100;
                                $stepMeioClass = 'completed';
                                $iconMeio = getStatusIconClass('processo');
                                $stepFinalizadoClass = 'completed';
                                $iconFinalizado = getStatusIconClass('finalizado');
                                break;
                            case 'rejeitado':
                            case 'cancelado':
                                $iconMeio = getStatusIconClass($pedido['status']);
                                $iconFinalizado = getStatusIconClass($pedido['status']);
                                if ($dataFinalizacao) $progressWidthPercent = 100;
                                elseif ($dataIntermediaria) $progressWidthPercent = ($pedido['tipo_pedido'] === 'troca' && $pedido['status'] !== 'troca_aceita_parceiro_pendente_matriz') ? 25 : 50;
                                else $progressWidthPercent = 5;
                                break;
                        }
                        ?>
                        <div class="progress-steps">
                            <div class="progress-line" style="width: <?= $progressWidthPercent ?>%; background-color: <?= ($progressWidthPercent == 100 && $pedido['status'] == 'finalizado') ? 'var(--success-color)' : (($pedido['status'] == 'rejeitado' || $pedido['status'] == 'cancelado') ? 'var(--danger-color)' : ($progressWidthPercent > 5 ? 'var(--warning-color)' : '#E0E0E0')) ?> ;"></div>
                            <div class="progress-step <?= $stepCriadoClass ?>">
                                <div class="step-icon"><i class="fas fa-check"></i></div>
                                <div class="step-text"><?= $stepCriadoLabel ?></div>
                                <div class="step-date"><?= date('d/m/Y H:i', strtotime($dataCriado)) ?></div>
                            </div>
                            <div class="progress-step <?= $stepMeioClass ?>">
                                <div class="step-icon"><i class="fas <?= $iconMeio ?>"></i></div>
                                <div class="step-text"><?= $stepMeioLabel ?></div>
                                <div class="step-date"><?= $dataIntermediaria ? date('d/m/Y H:i', strtotime($dataIntermediaria)) : 'Pendente' ?></div>
                            </div>
                            <div class="progress-step <?= $stepFinalizadoClass ?>">
                                <div class="step-icon"><i class="fas <?= $iconFinalizado ?>"></i></div>
                                <div class="step-text">Finalizado/Concluído</div>
                                <div class="step-date"><?= $dataFinalizacao ? date('d/m/Y H:i', strtotime($dataFinalizacao)) : 'Pendente' ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="content-section">
                        <h3 class="section-title">Detalhes da Movimentação</h3>
                        <div class="filial-cards">
                            <div class="filial-card">
                                <div class="filial-header">
                                    <div class="filial-icon origem"><i class="fas <?= $pedido['tipo_pedido'] == 'devolucao' ? 'fa-undo-alt' : 'fa-dolly-flatbed' ?>"></i></div>
                                    <div class="filial-title"><?= $pedido['tipo_pedido'] == 'devolucao' ? 'Devolvido Por (Origem)' : 'Origem do Pedido' ?></div>
                                </div>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="info-label">Nome</div>
                                        <div class="info-value"><?= $nome_origem_final ?></div>
                                    </div>
                                    <?php if ($pedido['cnpj_origem']): ?>
                                        <div class="info-item">
                                            <div class="info-label">CNPJ</div>
                                            <div class="info-value"><?= htmlspecialchars(preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $pedido['cnpj_origem'])) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="info-item">
                                        <div class="info-label">Endereço</div>
                                        <div class="info-value"><?= htmlspecialchars($pedido['endereco_origem'] ?: 'N/A') ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Cidade/UF</div>
                                        <div class="info-value"><?= htmlspecialchars($pedido['cidade_origem'] ?: 'N/A') ?>/<?= htmlspecialchars($pedido['uf_origem'] ?: 'N/A') ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($pedido['filial_destino_id'] || ($pedido['filial_usuario_id'] != 1 && in_array($pedido['tipo_pedido'], ['requisicao', 'devolucao'])) || ($pedido['tipo_pedido'] == 'troca')): ?>
                                <div class="filial-card">
                                    <div class="filial-header">
                                        <div class="filial-icon destino"><i class="fas <?= $pedido['tipo_pedido'] == 'devolucao' ? 'fa-industry' : ($pedido['tipo_pedido'] == 'troca' ? 'fa-people-arrows' : 'fa-map-marked-alt') ?>"></i></div>
                                        <div class="filial-title"><?= $pedido['tipo_pedido'] == 'devolucao' ? 'Devolvido Para (Destino)' : ($pedido['tipo_pedido'] == 'troca' ? 'Filial Parceira da Troca' : 'Destino do Pedido') ?></div>
                                    </div>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Nome</div>
                                            <div class="info-value"><?= $nome_destino_final ?></div>
                                        </div>
                                        <?php if ($pedido['cnpj_destino']): ?>
                                            <div class="info-item">
                                                <div class="info-label">CNPJ</div>
                                                <div class="info-value"><?= htmlspecialchars(preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $pedido['cnpj_destino'])) ?></div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="info-item">
                                            <div class="info-label">Endereço</div>
                                            <div class="info-value"><?= htmlspecialchars($pedido['endereco_destino'] ?: 'N/A') ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Cidade/UF</div>
                                            <div class="info-value"><?= htmlspecialchars($pedido['cidade_destino'] ?: 'N/A') ?>/<?= htmlspecialchars($pedido['uf_destino'] ?: 'N/A') ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($pedido['observacoes'])): ?>
                            <div class="mt-3">
                                <h4 class="info-label" style="font-size: 1rem; font-weight:bold;">Observações Gerais do Pedido:</h4>
                                <p class="info-value" style="white-space: pre-wrap; background-color: #f8f9fa; padding:10px; border-radius: 5px;"><?= htmlspecialchars($pedido['observacoes']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="content-section">
                        <?php if ($pedido['tipo_pedido'] === 'troca'): ?>
                            <h3 class="section-title">Itens da Proposta de Troca</h3>
                            <div class="row">
                                <div class="col-lg-6 mb-4 mb-lg-0">
                                    <h5><i class="fas fa-arrow-up text-warning"></i> Itens ENVIADOS por <?= htmlspecialchars($nome_origem_final) ?>:</h5>
                                    <?php if (!empty($items_enviados_admin)): ?>
                                        <div class="table-responsive">
                                            <table class="items-table">
                                                <thead>
                                                    <tr>
                                                        <th>SKU</th>
                                                        <th>Produto</th>
                                                        <th>Qtd. (Und)</th>
                                                        <th>Obs.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($items_enviados_admin as $item): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($item['sku']) ?></td>
                                                            <td><?= htmlspecialchars($item['produto']) ?></td>
                                                            <td><?= rtrim(rtrim(number_format($item['quantidade'], 2, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($item['unidade_medida']) ?></td>
                                                            <td><?= htmlspecialchars($item['observacao'] ?: '-') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?> <p class="text-muted">Nenhum item a ser enviado nesta troca.</p> <?php endif; ?>
                                </div>
                                <div class="col-lg-6">
                                    <h5><i class="fas fa-arrow-down text-success"></i> Itens SOLICITADOS em troca (a serem enviados por <?= htmlspecialchars($nome_destino_final) ?>):</h5>
                                    <?php if (!empty($items_recebidos_admin)): ?>
                                        <div class="table-responsive">
                                            <table class="items-table">
                                                <thead>
                                                    <tr>
                                                        <th>SKU</th>
                                                        <th>Produto</th>
                                                        <th>Qtd. (Und)</th>
                                                        <th>Obs.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($items_recebidos_admin as $item): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($item['sku']) ?></td>
                                                            <td><?= htmlspecialchars($item['produto']) ?></td>
                                                            <td><?= rtrim(rtrim(number_format($item['quantidade'], 2, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($item['unidade_medida']) ?></td>
                                                            <td><?= htmlspecialchars($item['observacao'] ?: '-') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?> <p class="text-muted">Nenhum item solicitado para recebimento nesta troca.</p> <?php endif; ?>
                                </div>
                            </div>
                        <?php else: // Para outros tipos de pedido 
                        ?>
                            <h3 class="section-title">Itens do Pedido</h3>
                            <div class="table-responsive">
                                <table class="items-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 15%">SKU</th>
                                            <th style="width: 30%">Produto</th>
                                            <th style="width: 15%">Quantidade</th>
                                            <th style="width: 15%">Unidade</th>
                                            <th style="width: 25%">Observação do Item</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($items_normais_admin)): ?>
                                            <?php foreach ($items_normais_admin as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['sku']) ?></td>
                                                    <td><?= htmlspecialchars($item['produto']) ?></td>
                                                    <td><?= rtrim(rtrim(number_format($item['quantidade'], 2, ',', '.'), '0'), ',') ?></td>
                                                    <td><?= htmlspecialchars($item['unidade_medida']) ?></td>
                                                    <td><?= !empty($item['observacao']) ? htmlspecialchars($item['observacao']) : '-' ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center" style="padding: 20px;">Nenhum item encontrado para este pedido.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="content-section">
                        <div class="order-actions">
                            <button class="btn btn-outline-secondary" onclick="window.print();">
                                <i class="fas fa-print"></i> Imprimir
                            </button>

                            <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 1): ?>

                                <?php if ($pedido['tipo_pedido'] === 'troca'): ?>
                                    <?php if ($pedido['status'] === 'novo_troca_pendente_aceite_parceiro'): ?>
                                        <button class="btn btn-info" disabled title="Aguardando aceite da filial parceira">
                                            <i class="fas fa-hourglass-half"></i> Aguardando Aceite do Parceiro
                                        </button>
                                    <?php elseif ($pedido['status'] === 'troca_aceita_parceiro_pendente_matriz'): ?>
                                        <a href="processar_pedido.php?id=<?= $pedido_id ?>&action=processar" class="btn btn-warning custom-warning">
                                            <i class="fas fa-cogs"></i> Processar Troca (Matriz)
                                        </a>
                                    <?php elseif ($pedido['status'] === 'processo'): ?>
                                        <a href="finalizar_pedido.php?id=<?= $pedido_id ?>" class="btn btn-success custom-success">
                                            <i class="fas fa-check-circle"></i> Finalizar Troca
                                        </a>
                                    <?php elseif ($pedido['status'] === 'rejeitado'): ?>
                                        <span class="badge badge-danger p-2" style="font-size: 0.9rem;"><i class="fas fa-times-circle mr-1"></i>Troca Rejeitada pelo Parceiro</span>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <?php if (in_array($pedido['status'], ['novo', 'aprovado'])): ?>
                                        <a href="processar_pedido.php?id=<?= $pedido_id ?>&action=processar" class="btn btn-warning custom-warning">
                                            <i class="fas fa-spinner"></i> Iniciar Processamento
                                        </a>
                                    <?php elseif ($pedido['status'] == 'processo'): ?>
                                        <a href="finalizar_pedido.php?id=<?= $pedido_id ?>" class="btn btn-success custom-success">
                                            <i class="fas fa-check-circle"></i> Finalizar Pedido
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (!in_array($pedido['status'], ['finalizado', 'cancelado'])): ?>
                                    <?php if ($pedido['status'] !== 'rejeitado'): ?>
                                        <a href="editar_pedido.php?id=<?= $pedido_id ?>" class="btn btn-primary custom-primary">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <a href="processar_pedido.php?id=<?= $pedido_id ?>&action=cancelar" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja CANCELAR este pedido? Esta ação não poderá ser desfeita.');">
                                            <i class="fas fa-ban"></i> Cancelar Pedido (Admin)
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    if ($pedido['tipo_pedido'] !== 'troca' && in_array($pedido['status'], ['novo', 'aprovado'])): ?>
                                        <a href="processar_pedido.php?id=<?= $pedido_id ?>&action=rejeitar" class="btn btn-outline-danger" onclick="return confirm('Tem certeza que deseja REJEITAR este pedido?');">
                                            <i class="fas fa-times-circle"></i> Rejeitar Pedido
                                        </a>
                                    <?php elseif ($pedido['tipo_pedido'] === 'troca' && $pedido['status'] === 'rejeitado'): ?>
                                        <a href="processar_pedido.php?id=<?= $pedido_id ?>&action=cancelar" class="btn btn-outline-secondary" onclick="return confirm('Isso irá arquivar a proposta de troca rejeitada como CANCELADA. Confirma?');">
                                            <i class="fas fa-archive"></i> Arquivar Proposta Rejeitada
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função hex2rgba_admin_details já definida no PHP para este arquivo, se for usar no JS, precisa redefinir ou passar do PHP
        document.addEventListener('DOMContentLoaded', function() {
            var alertList = document.querySelectorAll('.alert-dismissible');
            alertList.forEach(function(alert) {
                setTimeout(function() {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Alert && bootstrap.Alert.getInstance(alert)) {
                        bootstrap.Alert.getInstance(alert).close();
                    } else if (typeof $ !== 'undefined' && $.fn.alert) {
                        $(alert).alert('close');
                    }
                }, 7000);
            });
        });
    </script>
</body>

</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>