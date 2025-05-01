<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../includes/db.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Pedidos</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/pedidos.css">
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
            <h1>Pedidos Realizados</h1>
        </div>

        <div class="main-content">
            <!-- Barra de pesquisa com ícone -->
            <div class="search-container">
                <div class="search-wrapper">
                    <input type="text" id="searchInput" class="search-input" placeholder="Pesquisar por número de pedido, tipo ou CNPJ...">
                    <i class="fas fa-search search-icon"></i>
                </div>

                <!-- Filtros de pedidos -->
                <div class="filters-container">
                    <div class="filter-tag active" data-status="novo" onclick="filterByStatus(this)">
                        <i class="fas fa-plus-circle"></i>
                        Novos Pedidos
                    </div>
                    <div class="filter-tag" data-status="processo" onclick="filterByStatus(this)">
                        <i class="fas fa-spinner"></i>
                        Pedidos em Processo
                    </div>
                    <div class="filter-tag" data-status="finalizado" onclick="filterByStatus(this)">
                        <i class="fas fa-check-circle"></i>
                        Pedidos Finalizados
                    </div>
                </div>

                <!-- Filtros de tipos de pedido -->
                <div class="filters-container mt-2">
                    <div class="filter-tag2" data-tipo="requisicao" onclick="filterByType(this)">
                        <i class="fas fa-file-invoice"></i>
                        Requisição
                    </div>
                    <div class="filter-tag2" data-tipo="troca" onclick="filterByType(this)">
                        <i class="fas fa-exchange-alt"></i>
                        Troca
                    </div>
                    <div class="filter-tag2" data-tipo="doacao" onclick="filterByType(this)">
                        <i class="fas fa-gift"></i>
                        Doação
                    </div>
                    <div class="filter-tag2" data-tipo="devolucao" onclick="filterByType(this)">
                        <i class="fas fa-undo-alt"></i>
                        Devolução
                    </div>
                </div>
            </div>

            <!-- Lista de Pedidos -->
            <div class="pedidos-list-container">
                <table class="table table-hover pedidos-table">
                    <thead>
                        <tr>
                            <th scope="col">Nº Pedido</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">CNPJ Filial</th>
                            <th scope="col">Data</th>
                            <th scope="col">Status</th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="pedidosList">
                        <?php
                        // Query modificada para incluir tipo de pedido e CNPJ da filial
                        $query = "SELECT p.id, p.data, p.tipo_pedido, p.status, f.cnpj, f.nome_filial 
                                  FROM pedidos p 
                                  JOIN filiais f ON p.filial_id = f.id 
                                  ORDER BY p.data DESC";
                        
                        $pedidos = $conn->query($query);
                        
                        if ($pedidos->num_rows > 0):
                            while ($pedido = $pedidos->fetch_assoc()):
                                // Mapear tipo de pedido para ícones e classes CSS
                                $tipoIconMap = [
                                    'requisicao' => '<i class="fas fa-file-invoice"></i>',
                                    'troca' => '<i class="fas fa-exchange-alt"></i>',
                                    'doacao' => '<i class="fas fa-gift"></i>',
                                    'devolucao' => '<i class="fas fa-undo-alt"></i>'
                                ];
                                
                                $statusBadgeMap = [
                                    'novo' => 'badge-primary',
                                    'processo' => 'badge-warning',
                                    'finalizado' => 'badge-success'
                                ];
                                
                                $tipoIcon = $tipoIconMap[$pedido['tipo_pedido']] ?? '<i class="fas fa-question-circle"></i>';
                                $statusBadge = $statusBadgeMap[$pedido['status']] ?? 'badge-secondary';
                                
                                // Formatar CNPJ
                                $cnpj = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $pedido['cnpj']);
                        ?>
                        <tr class="pedido-row" data-status="<?= $pedido['status'] ?>" data-tipo="<?= $pedido['tipo_pedido'] ?>">
                            <td>#<?= $pedido['id'] ?></td>
                            <td>
                                <span class="tipo-pedido">
                                    <?= $tipoIcon ?> 
                                    <?= ucfirst($pedido['tipo_pedido']) ?>
                                </span>
                            </td>
                            <td title="<?= $pedido['nome_filial'] ?>"><?= $cnpj ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($pedido['data'])) ?></td>
                            <td>
                                <span class="badge <?= $statusBadge ?>">
                                    <?= ucfirst($pedido['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="detalhes_pedido.php?id=<?= $pedido['id'] ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Detalhes
                                </a>
                            </td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 15px; color: #adb5bd;"></i>
                                <h4>Nenhum pedido encontrado</h4>
                                <p>Utilize os filtros acima para encontrar pedidos específicos</p>
                            </td>
                        </tr>
                        <?php
                        endif;
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../../js/dashboard.js"></script>
    
    <script>
        // Filtrar por status
        function filterByStatus(element) {
            // Remove classe active de todos os elementos
            document.querySelectorAll('.filter-tag').forEach(el => el.classList.remove('active'));
            // Adiciona classe active ao elemento clicado
            element.classList.add('active');
            
            const status = element.getAttribute('data-status');
            filterPedidos();
        }
        
        // Filtrar por tipo
        function filterByType(element) {
            // Toggle classe active no elemento clicado
            element.classList.toggle('active');
            filterPedidos();
        }
        
        // Função para filtrar pedidos baseado em status e tipo
        function filterPedidos() {
            const selectedStatus = document.querySelector('.filter-tag.active').getAttribute('data-status');
            const selectedTypes = Array.from(document.querySelectorAll('.filter-tag2.active')).map(el => el.getAttribute('data-tipo'));
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            
            document.querySelectorAll('#pedidosList tr.pedido-row').forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                const rowType = row.getAttribute('data-tipo');
                const rowText = row.textContent.toLowerCase();
                
                // Verificar se corresponde ao status selecionado
                const matchStatus = rowStatus === selectedStatus;
                
                // Verificar se corresponde a algum dos tipos selecionados ou se nenhum tipo está selecionado
                const matchType = selectedTypes.length === 0 || selectedTypes.includes(rowType);
                
                // Verificar se corresponde ao texto de pesquisa
                const matchSearch = searchText === '' || rowText.includes(searchText);
                
                // Mostrar ou esconder a linha baseado nos filtros
                row.style.display = (matchStatus && matchType && matchSearch) ? '' : 'none';
            });
        }
        
        // Evento de pesquisa
        document.getElementById('searchInput').addEventListener('keyup', filterPedidos);
    </script>
</body>

</html>