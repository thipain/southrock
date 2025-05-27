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
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/pedidos.css">
</head>

<body>
    <div class="sidebar">
        <div>
            <div class="sidebar-header">
                <i class="fas fa-bars icon"></i><span class="text">Menu</span>
            </div>
            <a href="dashboard.php"><i class="fas fa-home icon"></i><span class="text">Início</span></a>
            <a href="pedidos.php" class="active"><i class="fas fa-shopping-cart icon"></i><span
                    class="text">Pedidos</span></a>
            <a href="produtos.php"><i class="fas fa-box icon"></i><span class="text">Produtos</span></a>
            <a href="usuarios.php"><i class="fas fa-users icon"></i><span class="text">Usuários</span></a>
        </div>
        <a href="../../logout/logout.php"><i class="fas fa-sign-out-alt icon"></i><span class="text">Sair</span></a>
    </div>

    <div class="content">
        <div class="header">
            <h1>Pedidos Realizados</h1>
            <hr class="barrinha">
        </div>

        <div class="main-content">
            <div class="search-container">
                <div class="search-wrapper">
                    <input type="text" id="searchInput" class="search-input"
                        placeholder="Pesquisar por número de pedido ou CNPJ...">
                    <i class="fas fa-search search-icon"></i>
                    <button type="button" id="clearSearch" class="btn btn-sm btn-light"
                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="filters-area">
                    <div class="filters-container">
                        <div class="filter-tag active" data-status="todos" onclick="filterByStatus(this)">
                            <i class="fas fa-list"></i>
                            Todos os Pedidos
                        </div>
                        <div class="filter-tag" data-status="novo" onclick="filterByStatus(this)">
                            <i class="fas fa-plus-circle"></i>
                            Novos Pedidos
                        </div>
                        <div class="filter-tag" data-status="processo" onclick="filterByStatus(this)">
                            <i class="fas fa-spinner"></i>
                            Em Processo
                        </div>
                        <div class="filter-tag" data-status="finalizado" onclick="filterByStatus(this)">
                            <i class="fas fa-check-circle"></i>
                            Finalizados
                        </div>
                    </div>

                    <div class="filters-container">
                        <div class="filter-tag2 active" data-tipo="todos" onclick="filterByType(this)">
                            <i class="fas fa-th-list"></i>
                            Todos os Tipos
                        </div>
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
            </div>

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
                        
                        $query = "SELECT p.id, p.data, p.tipo_pedido, p.status, u.cnpj, u.nome_filial 
                                  FROM pedidos p 
                                  JOIN usuarios u ON p.filial_usuario_id = u.id 
                                  WHERE u.eh_filial = TRUE
                                  ORDER BY p.data DESC";

                        $pedidos = $conn->query($query);

                        if ($pedidos->num_rows > 0):
                            while ($pedido = $pedidos->fetch_assoc()):
                              
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

                               
                                $cnpj = $pedido['cnpj'];
                                if (strpos($cnpj, '.') === false && strlen($cnpj) == 14) { // Adicionada verificação de comprimento para formatação de CNPJ
                                    $cnpj = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
                                }
                                ?>
                                <tr class="pedido-row" data-status="<?= $pedido['status'] ?>"
                                    data-tipo="<?= $pedido['tipo_pedido'] ?>">
                                    <td>#<?= $pedido['id'] ?></td>
                                    <td>
                                        <span class="tipo-pedido">
                                            <?= $tipoIcon ?>
                                            <?= ucfirst($pedido['tipo_pedido']) ?>
                                        </span>
                                    </td>
                                    <td title="<?= htmlspecialchars($pedido['nome_filial']) ?>"><?= $cnpj ?></td>
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
                                    <i class="fas fa-inbox"
                                        style="font-size: 40px; margin-bottom: 15px; color: #adb5bd;"></i>
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

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../../js/dashboard.js"></script>

    <script>
       
        function filterByStatus(element) {
           
            document.querySelectorAll('.filter-tag').forEach(el => el.classList.remove('active'));
           
            element.classList.add('active');

            filterPedidos();
        }

     
        function filterByType(element) {
            const isTodosTiposClicked = element.getAttribute('data-tipo') === 'todos';
            const todosTiposElement = document.querySelector('.filter-tag2[data-tipo="todos"]');

            if (isTodosTiposClicked) {
                
                document.querySelectorAll('.filter-tag2').forEach(el => {
                    if (el === todosTiposElement) {
                        el.classList.add('active');
                    } else {
                        el.classList.remove('active');
                    }
                });
            } else {
              
                todosTiposElement.classList.remove('active');
          
                element.classList.toggle('active');

              
                const activeSpecificTypes = document.querySelectorAll('.filter-tag2.active:not([data-tipo="todos"])');
                if (activeSpecificTypes.length === 0) {
                  
                    todosTiposElement.classList.add('active');
                }
            }

            filterPedidos();
        }

       
        function filterPedidos() {
            const selectedStatus = document.querySelector('.filter-tag.active').getAttribute('data-status');
            const isTodosStatus = selectedStatus === 'todos';

          
            const isTodosTiposActive = document.querySelector('.filter-tag2[data-tipo="todos"]').classList.contains('active');

           
            const selectedTypes = isTodosTiposActive ? [] :
                Array.from(document.querySelectorAll('.filter-tag2.active:not([data-tipo="todos"])')).map(el => el.getAttribute('data-tipo'));

            const searchText = document.getElementById('searchInput').value.toLowerCase().trim();

            document.querySelectorAll('#pedidosList tr.pedido-row').forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                const rowType = row.getAttribute('data-tipo');

                
                const pedidoNumero = row.cells[0].textContent.toLowerCase(); // Nº Pedido
                const cnpj = row.cells[2].textContent.toLowerCase(); // CNPJ

              
                const matchStatus = isTodosStatus || rowStatus === selectedStatus;

               
                const matchType = isTodosTiposActive || selectedTypes.includes(rowType);

               
                const matchSearch = searchText === '' ||
                    pedidoNumero.includes(searchText) ||
                    cnpj.includes(searchText);

              
                row.style.display = (matchStatus && matchType && matchSearch) ? '' : 'none';
            });
        }

       
        const searchInput = document.getElementById('searchInput');
        const clearButton = document.getElementById('clearSearch');

        searchInput.addEventListener('keyup', function () {
            filterPedidos();
          
            clearButton.style.display = searchInput.value.length > 0 ? 'block' : 'none';
        });

      
        clearButton.addEventListener('click', function () {
            searchInput.value = '';
            clearButton.style.display = 'none';
            filterPedidos();
            searchInput.focus();
        });

      
        window.onload = function () {
          
            document.querySelector('.filter-tag[data-status="todos"]').classList.add('active');
            document.querySelector('.filter-tag2[data-tipo="todos"]').classList.add('active');
            filterPedidos();
        };
    </script>
</body>

</html>