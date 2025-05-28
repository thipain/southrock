<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pedidos.php");
    exit();
}

$pedido_id = intval($_GET['id']);

$query = "SELECT p.*, 
          u_filial.nome_filial AS nome_filial_origem, 
          u_filial.cnpj AS cnpj_origem, 
          u_filial.endereco AS endereco_origem, 
          u_filial.cidade AS cidade_origem, 
          u_filial.estado AS estado_origem, 
          u_destino.nome_filial AS nome_filial_destino,
          u_destino.cnpj AS cnpj_destino,
          u_destino.endereco AS endereco_destino,
          u_destino.cidade AS cidade_destino,
          u_destino.estado AS estado_destino,
          u.nome as usuario_nome
          FROM pedidos p 
          JOIN usuarios u_filial ON p.filial_usuario_id = u_filial.id
          LEFT JOIN usuarios u_destino ON p.filial_destino_id = u_destino.id
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

$query_itens = "SELECT i.*, pr.produto, pr.unidade_medida 
                FROM pedido_itens i
                JOIN produtos pr ON i.sku = pr.sku
                WHERE i.pedido_id = ?";

$stmt_itens = $conn->prepare($query_itens);
$stmt_itens->bind_param("i", $pedido_id);
$stmt_itens->execute();
$itens = $stmt_itens->get_result();

$statusInfo = [
    'novo' => [
        'bg' => '#E3F2FD',
        'text' => '#1565C0',
        'icon' => 'fa-file-circle-plus',
        'label' => 'Novo'
    ],
    'processo' => [
        'bg' => '#FFF8E1',
        'text' => '#F57F17',
        'icon' => 'fa-spinner',
        'label' => 'Em Processo'
    ],
    'finalizado' => [
        'bg' => '#E8F5E9',
        'text' => '#2E7D32',
        'icon' => 'fa-circle-check',
        'label' => 'Finalizado'
    ]
];

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

$currentStatus = $statusInfo[$pedido['status']] ?? $statusInfo['novo'];
$tipoPedido = $tipoPedidoInfo[$pedido['tipo_pedido']] ?? ['icon' => 'fa-question-circle', 'label' => 'Desconhecido'];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?= $pedido_id ?> - <?= ucfirst($tipoPedido['label']) ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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
            background-color: #fffff3;
        }

        .content.expanded {
            margin-left: 60px;
            width: calc(100% - 60px);
        }

        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            margin-top: 10px;
            background-color: #fffff3;
            font-size: 1.6rem;
            color: #000000;
            font-weight: bold;
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
            padding: 25px 25px 0 25px;
        }

        .order-id-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .order-id {
            display: flex;
            align-items: center;
            gap: 15px;
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
            background-color: rgba(57, 73, 171, 0.1);
        }

        .type-icon {
            color: var(--primary-color);
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
            top: 15px;
            left: 0;
            right: 0;
            height: 22px;
            background-color: #E0E0E0;
            z-index: 1;
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
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background-color: white;
            border: 2px solid #E0E0E0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            color: #E0E0E0;
        }

        .step-icon i {
            font-size: 1.5rem;
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

        .progress-step.completed .step-icon {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }

        .progress-step.completed .step-text {
            color: var(--dark-text);
            font-weight: 600;
        }

        .progress-step.active .step-icon {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: white;
        }

        .progress-step.active .step-text {
            color: var(--warning-color);
            font-weight: 600;
        }

        .progress-line {
            position: absolute;
            top: 15px;
            left: 0;
            height: 2px;
            background-color: var(--success-color);
            z-index: 1;
            transition: width 0.5s ease;
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
            gap: 25px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            color: var(--light-text);
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 500;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn {
            border-radius: var(--border-radius);
            padding: 10px 20px;
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

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
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

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .items-table th {
            background-color: #F5F7FA;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark-text);
            border-bottom: 1px solid #EEEEEE;
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #EEEEEE;
            color: var(--dark-text);
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .items-table tbody tr:hover {
            background-color: #F9FAFC;
        }

        .items-table tfoot {
            font-weight: 600;
        }

        .items-table tfoot td {
            padding: 15px;
            border-top: 2px solid #EEEEEE;
        }

        .address-card {
            background-color: #F9FAFC;
            border-radius: 8px;
            padding: 15px;
        }

        .filial-cards {
            display: flex;
            gap: 20px;
        }

        .filial-card {
            flex: 1;
            background-color: #F9FAFC;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid var(--primary-color);
        }

        .filial-card.origem {
            border-left-color: var(--primary-color);
        }

        .filial-card.destino {
            border-left-color: var(--success-color);
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

        .barrinha{
            color:rgb(83, 83, 83);
        }

        @media print {

            .sidebar,
            .header,
            .order-actions,
            .btn,
            .back-button {
                display: none !important;
            }

            .content {
                margin: 0;
                padding: 0;
            }

            .order-main-card {
                box-shadow: none;
                border: 1px solid #EEEEEE;
            }
        }
    </style>
</head>

<body>
  <div class="sidebar">
        <div>
            <div class="sidebar-header">
                <i class="fas fa-bars icon"></i><span class="text">Menu</span>
            </div>
            <a href="dashboard.php"><i class="fas fa-home icon"></i><span class="text">Início</span></a>
            <a href="pedidos.php" class="active"><i class="fas fa-shopping-cart icon"></i><span class="text">Pedidos</span></a>
            <a href="produtos.php"><i class="fas fa-box icon"></i><span class="text">Produtos</span></a>
            <a href="usuarios.php"><i class="fas fa-users icon"></i><span class="text">Usuários</span></a>
        </div>
        <a href="../../logout/logout.php"><i class="fas fa-sign-out-alt icon"></i><span class="text">Sair</span></a>
    </div>

    <div class="content">
        <div class="header">
            <h1>Detalhes do Pedido</h1>
            <hr class="barrinha">
        </div>

        <div class="main-content">
            <a href="pedidos.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Voltar para Lista de Pedidos
            </a>

            <div class="order-main-card">
                <div class="order-header">
                    <div class="order-id-container">
                        <div class="order-id">
                            <div class="type-icon-wrapper">
                                <i class="fas <?= $tipoPedido['icon'] ?> type-icon"></i>
                            </div>
                            <div>
                                <h2 class="order-number">Pedido #<?= $pedido_id ?></h2>
                                <div class="order-date">
                                    <?= ucfirst($tipoPedido['label']) ?> • <?= date('d/m/Y \à\s H:i', strtotime($pedido['data'])) ?> • <?= $pedido['usuario_nome'] ?>
                                </div>
                            </div>
                        </div>

                        <div class="status-container">
                            <div class="status-badge" style="background-color: <?= $currentStatus['bg'] ?>; color: <?= $currentStatus['text'] ?>;">
                                <i class="fas <?= $currentStatus['icon'] ?>"></i>
                                <?= $currentStatus['label'] ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="progress-container">
                    <?php
                    $progressWidth = 0;
                    if ($pedido['status'] == 'novo') {
                        $progressWidth = "17%";
                    } elseif ($pedido['status'] == 'processo') {
                        $progressWidth = "50%";
                    } elseif ($pedido['status'] == 'finalizado') {
                        $progressWidth = "100%";
                    }
                    ?>
                    <div class="progress-steps">
                        <div class="progress-line" style="width: <?= $progressWidth ?>"></div>

                        <div class="progress-step completed">
                            <div class="step-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="step-text">Criado</div>
                            <div class="step-date"><?= date('d/m/Y', strtotime($pedido['data'])) ?></div>
                        </div>

                        <div class="progress-step <?= ($pedido['status'] == 'processo' || $pedido['status'] == 'finalizado') ? 'completed' : '' ?>">
                            <div class="step-icon">
                                <?= ($pedido['status'] == 'processo' || $pedido['status'] == 'finalizado') ? '<i class="fas fa-check"></i>' : '<i class="fas fa-spinner"></i>' ?>
                            </div>
                            <div class="step-text">Em Processamento</div>
                            <div class="step-date">
                                <?= isset($pedido['data_processamento']) && $pedido['data_processamento'] ? date('d/m/Y', strtotime($pedido['data_processamento'])) : 'Pendente' ?>
                            </div>
                        </div>

                        <div class="progress-step <?= $pedido['status'] == 'finalizado' ? 'completed' : '' ?>">
                            <div class="step-icon">
                                <?= $pedido['status'] == 'finalizado' ? '<i class="fas fa-check"></i>' : '<i class="fas fa-flag-checkered"></i>' ?>
                            </div>
                            <div class="step-text">Finalizado</div>
                            <div class="step-date">
                                <?= isset($pedido['data_finalizacao']) && $pedido['data_finalizacao'] ? date('d/m/Y', strtotime($pedido['data_finalizacao'])) : 'Pendente' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($pedido['tipo_pedido'] == 'doacao' && !empty($pedido['filial_destino_id'])): ?>
                    <div class="content-section">
                        <h3 class="section-title">Informações das Filiais</h3>

                        <div class="filial-cards">
                            <div class="filial-card origem">
                                <div class="filial-header">
                                    <div class="filial-icon origem">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="filial-title">Filial de Origem</div>
                                </div>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="info-label">Nome da Filial</div>
                                        <div class="info-value"><?= $pedido['nome_filial_origem'] ?></div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-label">CNPJ</div>
                                        <div class="info-value">
                                            <?= preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $pedido['cnpj_origem']) ?>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-label">Endereço</div>
                                        <div class="info-value"><?= $pedido['endereco_origem'] ?></div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-label">Cidade/Estado</div>
                                        <div class="info-value"><?= $pedido['cidade_origem'] ?>/<?= $pedido['estado_origem'] ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="filial-card destino">
                                <div class="filial-header">
                                    <div class="filial-icon destino">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="filial-title">Filial de Destino</div>
                                </div>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="info-label">Nome da Filial</div>
                                        <div class="info-value"><?= $pedido['nome_filial_destino'] ?></div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-label">CNPJ</div>
                                        <div class="info-value">
                                            <?= preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $pedido['cnpj_destino']) ?>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-label">Endereço</div>
                                        <div class="info-value"><?= $pedido['endereco_destino'] ?></div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-label">Cidade/Estado</div>
                                        <div class="info-value"><?= $pedido['cidade_destino'] ?>/<?= $pedido['estado_destino'] ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="content-section">
                        <h3 class="section-title">Informações da Filial</h3>
                        <div class="address-card">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Nome da Filial</div>
                                    <div class="info-value"><?= $pedido['nome_filial_origem'] ?></div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">CNPJ</div>
                                    <div class="info-value">
                                        <?= preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $pedido['cnpj_origem']) ?>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Endereço</div>
                                    <div class="info-value"><?= $pedido['endereco_origem'] ?></div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">Cidade/Estado</div>
                                    <div class="info-value"><?= $pedido['cidade_origem'] ?>/<?= $pedido['estado_origem'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="content-section">
                    <h3 class="section-title">Itens do Pedido</h3>
                    <div class="table-responsive">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 15%">SKU</th>
                                    <th style="width: 30%">Produto</th>
                                    <th style="width: 15%">Quantidade</th>
                                    <th style="width: 15%">Unidade</th>
                                    <th style="width: 25%">Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_itens = 0;
                                if ($itens->num_rows > 0):
                                    while ($item = $itens->fetch_assoc()):
                                        $total_itens += $item['quantidade'];
                                ?>
                                        <tr>
                                            <td><?= $item['sku'] ?></td>
                                            <td><?= $item['produto'] ?></td>
                                            <td><?= $item['quantidade'] ?></td>
                                            <td><?= $item['unidade_medida'] ?></td>
                                            <td><?= !empty($item['observacao']) ? $item['observacao'] : '-' ?></td>
                                        </tr>
                                    <?php
                                    endwhile;
                                else:
                                    ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhum item encontrado para este pedido.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>

                        </table>
                    </div>
                </div>

                <div class="content-section">
                    <div class="order-actions">
                        <button class="btn btn-outline-secondary" onclick="window.print();">
                            <i class="fas fa-print"></i> Imprimir
                        </button>

                        <?php if ($pedido['status'] == 'novo'): ?>
                            <a href="processar_pedido.php?id=<?= $pedido_id ?>" class="btn btn-warning">
                                <i class="fas fa-spinner"></i> Processar Pedido
                            </a>
                        <?php elseif ($pedido['status'] == 'processo'): ?>
                            <a href="finalizar_pedido.php?id=<?= $pedido_id ?>" class="btn btn-success">
                                <i class="fas fa-check-circle"></i> Finalizar Pedido
                            </a>
                        <?php endif; ?>

                        <?php if ($pedido['status'] != 'finalizado'): ?>
                            <a href="editar_pedido.php?id=<?= $pedido_id ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../../js/dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function adjustLayout() {
                const sidebarWidth = document.querySelector('.sidebar').offsetWidth;
                document.querySelector('.content').style.marginLeft = sidebarWidth + 'px';
                document.querySelector('.content').style.width = `calc(100% - ${sidebarWidth}px)`;
            }

            adjustLayout();

            window.addEventListener('resize', adjustLayout);
        });
    </script>
</body>

</html>