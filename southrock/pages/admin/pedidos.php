<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['tipo_usuario'] != 1) { // Apenas Admin
    header("Location: ../../index.php");
    exit();
}

require_once '../../includes/db.php';
require_once '../../includes/status_helper.php'; // Incluindo o helper

// Definições de caminhos e links para o header_com_menu.php
$path_to_css_folder_from_page = '../../css/';
$logo_image_path_from_page = '../../images/zamp.png';
$logout_script_path_from_page = '../../logout/logout.php';

$link_dashboard = 'dashboard.php';
$link_pedidos_admin = 'pedidos.php'; // Página ativa
$link_produtos_admin = 'produtos.php';
$link_usuarios_admin = 'usuarios.php';
$link_cadastro_usuario_admin = 'cadastro_usuario.php';

$nome_sistema_atual = "ZAMP Admin - Pedidos";
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Pedidos - <?= htmlspecialchars($nome_sistema_atual) ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php
    if (file_exists(__DIR__ . '/../../includes/header_com_menu.php')) {
        include __DIR__ . '/../../includes/header_com_menu.php';
    }
    ?>
    <link rel="stylesheet" href="../../css/pedidos.css">
    <style>
        .search-wrapper {
            position: relative;
        }

        .clear-search-button {
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
            display: none;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
            line-height: 1;
            z-index: 5;
        }

        .alert-dismissible .btn-close {
            padding: 0.75rem 1rem;
            box-sizing: content-box;
        }
    </style>
</head>

<body class="hcm-body-fixed-header">
    <div class="hcm-main-content">
        <div class="container py-4">
            <div class="header">
                <h1>Pedidos Registrados no Sistema</h1>
                <hr class="barrinha">
            </div>

            <?php if (isset($_SESSION['error_message_pedidos'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error_message_pedidos']);
                    unset($_SESSION['error_message_pedidos']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="main-content">
                <div class="search-container mb-4">
                    <div class="search-wrapper">
                        <input type="text" id="searchInput" class="search-input form-control"
                            placeholder="Pesquisar por Nº Pedido, CNPJ Filial Origem/Destino ou Nome...">
                        <i class="fas fa-search search-icon"></i>
                        <button type="button" id="clearSearch" class="btn btn-sm btn-light clear-search-button">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="filters-area mt-3">
                        <div class="filters-container">
                            <div class="filter-tag active" data-status="todos" onclick="filterByStatus(this)">
                                <i class="fas fa-list"></i> Todos os Status
                            </div>
                            <div class="filter-tag" data-status="novo,aprovado,novo_troca_pendente_aceite_parceiro,troca_aceita_parceiro_pendente_matriz" onclick="filterByStatus(this)">
                                <i class="fas fa-hourglass-start"></i> Pendentes/Novos
                            </div>
                            <div class="filter-tag" data-status="processo" onclick="filterByStatus(this)">
                                <i class="fas fa-spinner"></i> Em Processo
                            </div>
                            <div class="filter-tag" data-status="finalizado" onclick="filterByStatus(this)">
                                <i class="fas fa-check-circle"></i> Finalizados
                            </div>
                            <div class="filter-tag" data-status="rejeitado,cancelado" onclick="filterByStatus(this)">
                                <i class="fas fa-ban"></i> Rejeitados/Cancelados
                            </div>
                        </div>
                        <div class="filters-container mt-2">
                            <div class="filter-tag2 active" data-tipo="todos" onclick="filterByType(this)">
                                <i class="fas fa-th-list"></i> Todos os Tipos
                            </div>
                            <div class="filter-tag2" data-tipo="requisicao" onclick="filterByType(this)">
                                <i class="fas fa-file-invoice"></i> Requisição
                            </div>
                            <div class="filter-tag2" data-tipo="troca" onclick="filterByType(this)">
                                <i class="fas fa-exchange-alt"></i> Troca
                            </div>
                            <div class="filter-tag2" data-tipo="doacao" onclick="filterByType(this)">
                                <i class="fas fa-gift"></i> Doação
                            </div>
                            <div class="filter-tag2" data-tipo="devolucao" onclick="filterByType(this)">
                                <i class="fas fa-undo-alt"></i> Devolução
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pedidos-list-container table-responsive">
                    <table class="table table-hover pedidos-table">
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
                        <tbody id="pedidosList">
                            <?php
                            // ***** SQL QUERY MODIFICADA AQUI para incluir p.filial_destino_id *****
                            $query_pedidos_admin = "SELECT p.id, p.data, p.tipo_pedido, p.status, p.filial_destino_id,
                                                    COALESCE(u_origem.nome_filial, u_origem.nome) as nome_origem, u_origem.cnpj as cnpj_origem,
                                                    COALESCE(u_destino.nome_filial, u_destino.nome) as nome_destino, u_destino.cnpj as cnpj_destino
                                               FROM pedidos p 
                                               LEFT JOIN usuarios u_origem ON p.filial_usuario_id = u_origem.id
                                               LEFT JOIN usuarios u_destino ON p.filial_destino_id = u_destino.id
                                               ORDER BY p.data DESC";
                            $pedidos_result = $conn->query($query_pedidos_admin);

                            $tipoIconMap = [
                                'requisicao' => '<i class="fas fa-file-invoice text-primary"></i>',
                                'troca' => '<i class="fas fa-exchange-alt text-info"></i>',
                                'doacao' => '<i class="fas fa-gift text-success"></i>',
                                'devolucao' => '<i class="fas fa-undo-alt text-warning"></i>'
                            ];

                            if ($pedidos_result && $pedidos_result->num_rows > 0):
                                while ($pedido = $pedidos_result->fetch_assoc()):
                                    $tipoIcon = $tipoIconMap[$pedido['tipo_pedido']] ?? '<i class="fas fa-question-circle"></i>';

                                    $displayStatusLabel = getStatusLabel($pedido['status']);
                                    $statusBadgeClass = getStatusBadgeClass($pedido['status']);

                                    $nome_origem_list = htmlspecialchars($pedido['nome_origem'] ?: 'N/A');

                              
                                    $nome_destino_list = 'N/A'; // Padrão
                                    if (isset($pedido['nome_destino']) && $pedido['nome_destino'] !== null) {
                                        $nome_destino_list = htmlspecialchars($pedido['nome_destino']);
                                    } elseif ((isset($pedido['filial_destino_id']) && $pedido['filial_destino_id'] === null) &&
                                        in_array($pedido['tipo_pedido'], ['requisicao', 'devolucao'])
                                    ) {
                                
                                        $nome_destino_list = "Matriz";
                                    } elseif (
                                        !isset($pedido['filial_destino_id']) &&
                                        in_array($pedido['tipo_pedido'], ['requisicao', 'devolucao'])
                                    ) {
                                        $nome_destino_list = "Matriz";
                                    }

                                    $search_terms_row = strtolower(
                                        $pedido['id'] . ' ' .
                                            ($pedido['nome_origem'] ?? '') . ' ' . ($pedido['cnpj_origem'] ?? '') . ' ' .
                                            ($pedido['nome_destino'] ?? '') . ' ' . ($pedido['cnpj_destino'] ?? '')
                                    );
                            ?>
                                    <tr class="pedido-row searchable-item"
                                        data-status="<?= htmlspecialchars($pedido['status']) ?>"
                                        data-tipo="<?= htmlspecialchars($pedido['tipo_pedido']) ?>"
                                        data-search="<?= htmlspecialchars($search_terms_row) ?>">
                                        <td>#<?= htmlspecialchars($pedido['id']) ?></td>
                                        <td>
                                            <span class="tipo-pedido" title="<?= ucfirst(htmlspecialchars($pedido['tipo_pedido'])) ?>">
                                                <?= $tipoIcon ?>&nbsp;<?= ucfirst(htmlspecialchars($pedido['tipo_pedido'])) ?>
                                            </span>
                                        </td>
                                        <td title="CNPJ: <?= htmlspecialchars($pedido['cnpj_origem'] ?: 'N/A') ?>"><?= $nome_origem_list ?></td>
                                        <td title="CNPJ: <?= htmlspecialchars($pedido['cnpj_destino'] ?: 'N/A') ?>"><?= $nome_destino_list ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($pedido['data'])) ?></td>
                                        <td>
                                            <span class="badge <?= htmlspecialchars($statusBadgeClass) ?>">
                                                <?= htmlspecialchars($displayStatusLabel) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="detalhes_pedido.php?id=<?= htmlspecialchars($pedido['id']) ?>" class="btn btn-sm btn-info" title="Ver Detalhes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (!in_array($pedido['status'], ['finalizado', 'cancelado', 'rejeitado'])): ?>
                                                <a href="editar_pedido.php?id=<?= htmlspecialchars($pedido['id']) ?>" class="btn btn-sm btn-outline-primary" title="Editar Pedido">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <tr id="no-orders-row">
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 15px; color: #adb5bd;"></i>
                                        <h4>Nenhum pedido encontrado</h4>
                                        <p>Utilize os filtros acima ou a barra de pesquisa.</p>
                                    </td>
                                </tr>
                            <?php
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="sistema-info text-center mt-4">
                <?= htmlspecialchars($nome_sistema_atual) ?> © <?php echo date('Y'); ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterByStatus(element) {
            document.querySelectorAll('.filters-container:nth-child(1) .filter-tag').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            applyFilters();
        }

        function filterByType(element) {
            document.querySelectorAll('.filters-container:nth-child(2) .filter-tag2').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            applyFilters();
        }

        function applyFilters() {
            const selectedStatusFilters = document.querySelector('.filters-container:nth-child(1) .filter-tag.active').getAttribute('data-status');
            const selectedTypeFilter = document.querySelector('.filters-container:nth-child(2) .filter-tag2.active').getAttribute('data-tipo');
            const searchText = document.getElementById('searchInput').value.toLowerCase().trim();
            let visibleCount = 0;

            document.querySelectorAll('#pedidosList tr.pedido-row').forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                const rowType = row.getAttribute('data-tipo');
                const rowSearchText = row.getAttribute('data-search');

                const statusMatch = selectedStatusFilters === 'todos' || selectedStatusFilters.split(',').includes(rowStatus);
                const typeMatch = selectedTypeFilter === 'todos' || rowType === selectedTypeFilter;
                const searchMatch = searchText === '' || (rowSearchText && rowSearchText.includes(searchText));

                if (statusMatch && typeMatch && searchMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const noOrdersRow = document.getElementById('no-orders-row');
            if (noOrdersRow) {
                // Mostra a mensagem "nenhum pedido" apenas se, após filtrar, não houver itens visíveis E EXISTEM PEDIDOS NA TABELA
                noOrdersRow.style.display = (visibleCount === 0 && document.querySelectorAll('#pedidosList tr.pedido-row').length > 0) ? '' : 'none';
                // Se não há pedidos na tabela de forma alguma (antes de qualquer filtro), a mensagem já é exibida pelo PHP.
                // Esta lógica JS é para quando os filtros escondem todos os pedidos que existem.
            }
        }

        const searchInput = document.getElementById('searchInput');
        const clearButton = document.getElementById('clearSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                applyFilters();
                if (clearButton) clearButton.style.display = searchInput.value.length > 0 ? 'inline-block' : 'none';
            });
        }
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                clearButton.style.display = 'none';
                applyFilters();
                searchInput.focus();
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            applyFilters();

            var alertListBS5 = document.querySelectorAll('.alert-dismissible.fade.show');
            alertListBS5.forEach(function(alert) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                    setTimeout(function() {
                        const bsAlert = bootstrap.Alert.getInstance(alert);
                        if (bsAlert) bsAlert.close();
                    }, 7000);
                }
            });
        });
    </script>
</body>

</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>