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

// Buscar itens do pedido
$query_itens = "SELECT i.*, pr.produto, pr.unidade_medida 
                FROM pedido_itens i
                JOIN produtos pr ON i.sku = pr.sku
                WHERE i.pedido_id = ?";

$stmt_itens = $conn->prepare($query_itens);
$stmt_itens->bind_param("i", $pedido_id);
$stmt_itens->execute();
$itens = $stmt_itens->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido #<?= $pedido_id ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/pedidos.css">
    <style>
        .details-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .status-badge-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .timeline {
            position: relative;
            margin: 20px 0;
            padding-left: 30px;
        }
        
        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 11px;
            width: 3px;
            background-color: #dee2e6;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-badge {
            position: absolute;
            left: -30px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            text-align: center;
            background-color: #0077B6;
            color: white;
            line-height: 24px;
            font-size: 12px;
            z-index: 1;
        }
        
        .timeline-item.completed .timeline-badge {
            background-color: #28a745;
        }
        
        .timeline-item.pending .timeline-badge {
            background-color: #ffc107;
        }
        
        .timeline-content {
            padding-left: 15px;
        }
        
        .timeline-date {
            font-size: 12px;
            color: #6c757d;
        }
        
        .filial-info {
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
            margin-bottom: 20px;
        }
        
        .item-row:hover {
            background-color: #f8f9fa;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .pedido-tipo-icon {
            font-size: 2rem;
            margin-right: 15px;
            color: #0077B6;
        }
        
        /* Estilos para impressão */
        @media print {
            .sidebar, .header, .action-buttons, .btn {
                display: none !important;
            }
            
            .content {
                margin: 0;
                padding: 0;
            }
            
            .details-card {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }
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
            <h1>Detalhes do Pedido</h1>
        </div>

        <div class="main-content">
            <!-- Botão de voltar -->
            <div class="mb-3">
                <a href="pedidos.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar para Lista de Pedidos
                </a>
            </div>
            
            <!-- Cabeçalho do pedido -->
            <div class="details-card">
                <div class="details-header">
                    <div class="d-flex align-items-center">
                        <?php
                        $tipoIconMap = [
                            'requisicao' => '<i class="fas fa-file-invoice pedido-tipo-icon"></i>',
                            'troca' => '<i class="fas fa-exchange-alt pedido-tipo-icon"></i>',
                            'doacao' => '<i class="fas fa-gift pedido-tipo-icon"></i>',
                            'devolucao' => '<i class="fas fa-undo-alt pedido-tipo-icon"></i>'
                        ];
                        
                        $tipoIcon = $tipoIconMap[$pedido['tipo_pedido']] ?? '<i class="fas fa-question-circle pedido-tipo-icon"></i>';
                        echo $tipoIcon;
                        ?>
                        <div>
                            <h3>Pedido #<?= $pedido_id ?></h3>
                            <p class="text-muted mb-0">
                                <?= ucfirst($pedido['tipo_pedido']) ?> criado em <?= date('d/m/Y H:i', strtotime($pedido['data'])) ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="status-badge-container">
                        <?php
                        $statusBadgeMap = [
                            'novo' => 'badge-primary',
                            'processo' => 'badge-warning',
                            'finalizado' => 'badge-success'
                        ];
                        
                        $statusBadge = $statusBadgeMap[$pedido['status']] ?? 'badge-secondary';
                        ?>
                        <span class="badge <?= $statusBadge ?> p-2">
                            <?= ucfirst($pedido['status']) ?>
                        </span>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Informações da filial -->
                    <div class="col-md-6">
                        <h5>Informações da Filial</h5>
                        <div class="filial-info">
                            <p><strong>Nome:</strong> <?= $pedido['nome_filial'] ?></p>
                            <p><strong>CNPJ:</strong> <?= preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $pedido['cnpj']) ?></p>
                            <p><strong>Endereço:</strong> <?= $pedido['endereco'] ?></p>
                            <p><strong>Cidade/Estado:</strong> <?= $pedido['cidade'] ?>/<?= $pedido['estado'] ?></p>
                        </div>
                    </div>
                    
                    <!-- Timeline do pedido -->
                    <div class="col-md-6">
                        <h5>Status do Pedido</h5>
                        <div class="timeline">
                            <div class="timeline-item completed">
                                <div class="timeline-badge">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <p class="mb-0"><strong>Pedido Criado</strong></p>
                                    <p class="timeline-date">
                                        <?= date('d/m/Y H:i', strtotime($pedido['data'])) ?> por <?= $pedido['usuario_nome'] ?>
                                    </p>
                                </div>
                            </div>
                            
                            <?php if ($pedido['status'] == 'processo' || $pedido['status'] == 'finalizado'): ?>
                            <div class="timeline-item completed">
                                <div class="timeline-badge">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <p class="mb-0"><strong>Em Processamento</strong></p>
                                    <p class="timeline-date">
                                        <?= isset($pedido['data_processamento']) ? date('d/m/Y H:i', strtotime($pedido['data_processamento'])) : 'Data não registrada' ?>
                                    </p>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="timeline-item pending">
                                <div class="timeline-badge">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="timeline-content">
                                    <p class="mb-0"><strong>Em Processamento</strong></p>
                                    <p class="timeline-date">Pendente</p>
                                </div>
                            </div>
                            <?php endif; ?>
                
                <!-- Tabela de itens do pedido -->
                <div class="mt-4">
                    <h5>Itens do Pedido</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Unidade</th>
                                    <th>Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_itens = 0;
                                if ($itens->num_rows > 0):
                                    while ($item = $itens->fetch_assoc()):
                                        $total_itens += $item['quantidade'];
                                ?>
                                <tr class="item-row">
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
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="text-right"><strong>Total de Itens:</strong></td>
                                    <td><strong><?= $total_itens ?></strong></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Botões de ação -->
                <div class="action-buttons">
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

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../../js/dashboard.js"></script>
</body>

</html>
                            
                            <?php if ($pedido['status'] == 'finalizado'): ?>
                            <div class="timeline-item completed">
                                <div class="timeline-badge">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <p class="mb-0"><strong>Finalizado</strong></p>
                                    <p class="timeline-date">
                                        <?= isset($pedido['data_finalizacao']) ? date('d/m/Y H:i', strtotime($pedido['data_finalizacao'])) : 'Data não registrada' ?>
                                    </p>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="timeline-item pending">
                                <div class="timeline-badge">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="timeline-content">
                                    <p class="mb-0"><strong>Finalizado</strong></p>
                                    <p class="timeline-date">Pendente</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                