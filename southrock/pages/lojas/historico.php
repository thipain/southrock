<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php"); 
    exit();
}

require_once '../../includes/db.php';
require_once '../../includes/status_helper.php'; // **CONFERIR ESTE CAMINHO**

$loggedInUserId = null;
$loggedInUserType = null; 

if (isset($_SESSION['user_id'])) { 
    $loggedInUserId = $_SESSION['user_id'];
    $loggedInUserType = $_SESSION['tipo_usuario'] ?? null;
} elseif (isset($_SESSION['username'])) { 
    $stmtUser = $conn->prepare("SELECT id, tipo_usuario FROM usuarios WHERE username = ?");
    if($stmtUser){
        $stmtUser->bind_param("s", $_SESSION['username']);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();
        if ($currentUserData = $resultUser->fetch_assoc()) {
            $_SESSION['user_id'] = $currentUserData['id']; 
            $_SESSION['tipo_usuario'] = $currentUserData['tipo_usuario']; 
            $loggedInUserId = $currentUserData['id'];
            $loggedInUserType = $currentUserData['tipo_usuario'];
        }
        $stmtUser->close();
    }
}

if ($loggedInUserId === null || $loggedInUserType != 2) { 
    $_SESSION['error_message_loja'] = "Acesso não autorizado.";
    header("Location: ../../index.php"); 
    exit();
}
$nome_sistema_atual = "SouthRock - Histórico de Pedidos";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Pedidos - <?= htmlspecialchars($nome_sistema_atual) ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../css/pedidos.css"> 
    <style>
        /* CSS que você tinha no seu arquivo historico.php original ou que eu forneci e você gostou */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f8f9fa; 
            padding-top: 70px; /* Ajustado para navbar fixa */
        }
        .loja-navbar { /* Barra de navegação específica para esta página de loja */
            background-color: #343a40; 
            color: white; 
            padding: 0.5rem 1rem; 
            margin-bottom: 1.5rem; 
            display:flex; 
            justify-content: space-between; 
            align-items: center;
            position: fixed; /* Fixa a barra no topo */
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1030; /* Para ficar acima de outros conteúdos */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .loja-navbar .navbar-brand { 
            color: #fff; 
            font-weight: 500;
            font-size: 1.25rem;
        }
        .loja-navbar .nav-link {
            color: rgba(255,255,255,.75);
            padding: .5rem .75rem;
        }
        .loja-navbar .nav-link:hover {
            color: #fff;
        }
        .loja-navbar .btn-danger {
            font-size: 0.875rem;
            padding: .375rem .75rem;
        }
        .main-container-loja { /* Container principal abaixo da navbar */
            padding-top: 1.5rem; 
            padding-bottom: 1.5rem;
        }
        /* Herda .header, .barrinha etc de pedidos.css */
        .filters-container .filter-tag i {
            margin-right: 5px;
        }
        .alert-dismissible .close { 
            position: absolute; 
            top: 0; 
            right: 0; 
            padding: 0.75rem 1.25rem; 
            color: inherit;
        }
         .clear-search-button { 
            position: absolute; 
            right: 2.8rem; 
            top: 50%; 
            transform: translateY(-50%); 
            display: none; 
            padding: 0.1rem 0.4rem;
            font-size:0.8rem;
            z-index: 5;
        }
         /* Assegurando que a tabela use as classes do pedidos.css se linkado */
        .pedidos-table th { 
            background-color: #e9ecef; /* Consistente com pedidos.css e admin/pedidos.php */
        }
        .pedidos-table .badge {
            font-size: 0.85em; 
            padding: 0.4em 0.6em;
        }
    </style>
</head>
<body>
    <nav class="loja-navbar navbar navbar-expand-sm navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Minha Filial</span>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#lojaNavContent" aria-controls="lojaNavContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="lojaNavContent">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="fazer_pedidos.php"><i class="fas fa-plus-circle"></i> Novo Pedido/Ação</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-sm btn-danger ml-2 text-white" href="../../logout/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-container-loja">
        <div class="header"> 
            <h1>Histórico de Pedidos</h1>
            <hr class="barrinha"> 
        </div>

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
        
        <div class="main-content p-0">
            <div class="search-container mb-4">
                <div class="search-wrapper">
                    <input type="text" id="searchInputHistorico" class="search-input form-control" placeholder="Pesquisar por Nº Pedido, Origem/Destino...">
                    <i class="fas fa-search search-icon"></i>
                    <button type="button" id="clearSearchHistorico" class="btn btn-sm btn-light clear-search-button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                 <div class="filters-area mt-3">
                    <div class="filters-container">
                        <div class="filter-tag active" data-status="todos" onclick="applyHistoricoFilters(this)">
                            <i class="fas fa-list"></i> Todos
                        </div>
                        <div class="filter-tag" data-status="novo,aprovado,novo_troca_pendente_aceite_parceiro,troca_aceita_parceiro_pendente_matriz" onclick="applyHistoricoFilters(this)">
                            <i class="fas fa-hourglass-start"></i> Pendentes
                        </div>
                        <div class="filter-tag" data-status="processo" onclick="applyHistoricoFilters(this)">
                            <i class="fas fa-spinner"></i> Em Processo
                        </div>
                        <div class="filter-tag" data-status="finalizado" onclick="applyHistoricoFilters(this)">
                            <i class="fas fa-check-circle"></i> Finalizados
                        </div>
                        <div class="filter-tag" data-status="rejeitado,cancelado" onclick="applyHistoricoFilters(this)">
                            <i class="fas fa-ban"></i> Rejeitados/Cancelados
                        </div>
                    </div>
                </div>
            </div>

            <div class="pedidos-list-container table-responsive">
                <table class="table table-hover table-bordered table-sm pedidos-table">
                    <thead>
                        <tr>
                            <th scope="col">Nº Pedido</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Origem</th>
                            <th scope="col">Destino</th>
                            <th scope="col">Data</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="pedidosListHistorico">
                        <?php
                        $sql_historico = "SELECT p.id, p.data, p.tipo_pedido, p.status,
                                       p.filial_usuario_id, p.filial_destino_id,
                                       COALESCE(u_origem.nome_filial, u_origem.nome) AS nome_origem,
                                       COALESCE(u_destino.nome_filial, u_destino.nome) AS nome_destino
                                FROM pedidos p
                                LEFT JOIN usuarios u_origem ON p.filial_usuario_id = u_origem.id
                                LEFT JOIN usuarios u_destino ON p.filial_destino_id = u_destino.id
                                WHERE (p.filial_usuario_id = ? OR p.filial_destino_id = ?)
                                ORDER BY p.data DESC";

                        $stmt_historico = $conn->prepare($sql_historico);
                        if (!$stmt_historico) {
                            echo '<tr><td colspan="7" class="text-center text-danger">Erro ao preparar consulta: ' . htmlspecialchars($conn->error) . '</td></tr>';
                        } else {
                            $stmt_historico->bind_param("ii", $loggedInUserId, $loggedInUserId);
                            $stmt_historico->execute();
                            $pedidos = $stmt_historico->get_result();

                            $tipoIconMapLoja = [
                                'requisicao' => '<i class="fas fa-file-invoice text-primary"></i>',
                                'troca' => '<i class="fas fa-exchange-alt text-info"></i>',
                                'doacao' => '<i class="fas fa-gift text-success"></i>',
                                'devolucao' => '<i class="fas fa-undo-alt text-warning"></i>'
                            ];
                            
                            if ($pedidos && $pedidos->num_rows > 0):
                                while ($pedido_hist = $pedidos->fetch_assoc()):
                                    $tipoIcon = $tipoIconMapLoja[$pedido_hist['tipo_pedido']] ?? '<i class="fas fa-question-circle"></i>';
                                    
                                    $statusAtualDoPedido = $pedido_hist['status'];
                                    // ** USANDO O HELPER CORRETAMENTE AQUI **
                                    $displayStatusLabelHistorico = getStatusLabel($statusAtualDoPedido);
                                    $statusBadgeClass = getStatusBadgeClass($statusAtualDoPedido);
                                    
                                    $nome_origem_hist = htmlspecialchars($pedido_hist['nome_origem'] ?: ($pedido_hist['filial_usuario_id'] == 1 ? 'Matriz' : 'Desconhecida'));
                                    $nome_destino_hist = htmlspecialchars($pedido_hist['nome_destino'] ?: ($pedido_hist['filial_destino_id'] === null && in_array($pedido_hist['tipo_pedido'], ['requisicao', 'devolucao']) ? 'Matriz' : 'N/A'));
                                    if ($pedido_hist['tipo_pedido'] === 'troca' && $pedido_hist['filial_destino_id'] === null && $pedido_hist['filial_usuario_id'] != 1) {
                                         $nome_destino_hist = 'Matriz (Troca)';
                                    } else if ($pedido_hist['tipo_pedido'] === 'troca' && $pedido_hist['filial_destino_id'] === null && $pedido_hist['filial_usuario_id'] == 1) { 
                                        $nome_destino_hist = 'Destino Indefinido';
                                    }

                                    $search_terms_hist = strtolower(
                                        $pedido_hist['id'] . ' ' .
                                        ($pedido_hist['nome_origem'] ?? '') . ' ' .
                                        ($pedido_hist['nome_destino'] ?? '')
                                    );
                            ?>
                            <tr class="pedido-row-historico searchable-item-historico" 
                                data-status="<?= htmlspecialchars($statusAtualDoPedido) ?>"
                                data-search="<?= htmlspecialchars($search_terms_hist) ?>">
                                <td>#<?= htmlspecialchars($pedido_hist['id']) ?></td>
                                <td title="<?= ucfirst(htmlspecialchars($pedido_hist['tipo_pedido'])) ?>"><?= $tipoIcon ?> <?= ucfirst(htmlspecialchars($pedido_hist['tipo_pedido'])) ?></td>
                                <td><?= $nome_origem_hist ?></td>
                                <td><?= $nome_destino_hist ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pedido_hist['data'])) ?></td>
                                <td>
                                    <span class="badge <?= $statusBadgeClass ?>">
                                        <?= htmlspecialchars($displayStatusLabelHistorico) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="detalhes_pedido_loja.php?id=<?= $pedido_hist['id'] ?>" class="btn btn-sm btn-info" title="Ver Detalhes">
                                        <i class="fas fa-eye"></i> Detalhes
                                    </a>
                                </td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr id="no-orders-row-historico">
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; color: #6c757d;"></i>
                                    <h5 class="text-muted">Nenhum pedido encontrado no seu histórico.</h5>
                                </td>
                            </tr>
                            <?php
                            endif;
                            if ($stmt_historico) $stmt_historico->close();
                        } 
                        if(isset($conn)) $conn->close(); 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function applyHistoricoFilters(element) { 
            if(element) { // Se a função foi chamada por um clique no filtro
                 $('.filters-container .filter-tag').removeClass('active');
                 $(element).addClass('active');
            }

            const selectedStatusFilters = $('.filters-container .filter-tag.active').data('status').toString();
            const searchText = $('#searchInputHistorico').val().toLowerCase().trim();
            let visibleCount = 0;

            $('#pedidosListHistorico tr.pedido-row-historico').each(function() {
                const rowStatus = $(this).data('status').toString();
                const rowSearchText = $(this).data('search') ? $(this).data('search').toString() : '';

                const statusMatch = selectedStatusFilters === 'todos' || selectedStatusFilters.split(',').includes(rowStatus);
                const searchMatch = searchText === '' || (rowSearchText && rowSearchText.includes(searchText));

                if (statusMatch && searchMatch) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });
            
            const noOrdersRow = $('#no-orders-row-historico');
            if(noOrdersRow.length){
                 if (visibleCount === 0 && $('#pedidosListHistorico tr.pedido-row-historico').length > 0) { // Se há pedidos, mas filtros os esconderam
                    noOrdersRow.show();
                 } else if ($('#pedidosListHistorico tr.pedido-row-historico').length === 0) { // Se não há pedidos na tabela
                    noOrdersRow.show(); 
                 }
                 else { // Se há pedidos visíveis
                    noOrdersRow.hide();
                 }
            }
        }

        $(document).ready(function() {
            $('#searchInputHistorico').on('input', function() {
                applyHistoricoFilters(null); 
                $('#clearSearchHistorico').toggle($(this).val().length > 0);
            });
            $('#clearSearchHistorico').on('click', function() {
                $('#searchInputHistorico').val('').trigger('input');
            });

            applyHistoricoFilters(null); // Aplica filtros ao carregar a página

            // Fechar alertas
            window.setTimeout(function() {
                $(".alert-dismissible").fadeTo(500, 0).slideUp(500, function(){
                    $(this).remove(); 
                });
            }, 7000);
        });
    </script>
</body>
</html>