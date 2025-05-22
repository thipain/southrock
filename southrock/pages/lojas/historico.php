<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../includes/db.php';

// Buscar ID e tipo do usuário logado
$loggedInUserId = null;
$loggedInUserType = null; // 1 para admin/matriz, 2 para loja/filial

if (isset($_SESSION['username'])) {
    $stmtUser = $conn->prepare("SELECT id, tipo_usuario FROM usuarios WHERE username = ?");
    $stmtUser->bind_param("s", $_SESSION['username']);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    if ($currentUserData = $resultUser->fetch_assoc()) {
        $loggedInUserId = $currentUserData['id'];
        $loggedInUserType = $currentUserData['tipo_usuario'];
    }
    $stmtUser->close();
}

if ($loggedInUserId === null) {
    echo "Erro: Não foi possível identificar o usuário logado ou seu tipo.";
    exit();
}

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
    <style>
        /* Estilos para ajustar o layout sem a sidebar e filtros */
        body {
            display: flex;
            flex-direction: column; /* Organiza o conteúdo verticalmente */
        }
        .content {
            margin-left: 0; /* Remove a margem que era para a sidebar */
            padding: 20px;
            width: 100%; /* Ocupa a largura total */
        }
        .top-bar { /* Nova classe para a barra superior */
            background-color: #343a40; /* Cor de exemplo, ajuste conforme seu design */
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .top-bar a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }
        .top-bar a:hover {
            color: #f8f9fa;
        }
        .top-bar .site-title { /* Estilo para o título do site/logo se desejar */
            font-size: 1.5rem;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="top-bar">
        <div class="site-title">SouthRock Pedidos</div> 
        <div>
            <?php
                // Links de navegação que estavam na sidebar podem ir aqui, se necessário
                // Exemplo: Link para Dashboard, se aplicável a todos os usuários que veem esta página.
                // Ou pode ser apenas o botão de Sair.
                // Se o usuário for admin, talvez links para Produtos, Usuários.
                // Se for filial, pode ser link para "Fazer Pedidos".

                // Exemplo simples:
                // echo '<a href="dashboard.php"><i class="fas fa-home icon"></i> Início</a>';
                // Se esta página for acessível por tipos diferentes de usuários que têm dashboards diferentes:
                if ($loggedInUserType == 1) { // Admin
                    echo '<a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard Admin</a> ';
                    echo '<a href="produtos.php"><i class="fas fa-box"></i> Produtos</a> ';
                    echo '<a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a> ';
                } else { // Filial
                    // Se filiais têm um dashboard diferente ou um menu principal:
                     echo '<a href="fazer_pedidos.php"><i class="fas fa-plus-circle"></i> Novo Pedido/Ação</a> ';
                }
            ?>
            <a href="../../logout/logout.php"><i class="fas fa-sign-out-alt icon"></i> Sair</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h1>
                <?php
                if ($loggedInUserType == 2) {
                    echo "Meus Pedidos Envolvidos";
                } else {
                    echo "Todos os Pedidos";
                }
                ?>
            </h1>
            <hr class="barrinha">
        </div>

        <div class="main-content">
            <div class="search-container mb-4">
                <div class="search-wrapper">
                    <input type="text" id="searchInput" class="search-input" placeholder="Pesquisar por Nº Pedido, CNPJ ou Nome Filial...">
                    <i class="fas fa-search search-icon"></i>
                    <button type="button" id="clearSearch" class="btn btn-sm btn-light" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="pedidos-list-container">
                <table class="table table-hover pedidos-table">
                    <thead>
                        <tr>
                            <th scope="col">Nº Pedido</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Filial Origem</th>
                            <th scope="col">Filial Destino</th>
                            <th scope="col">Data</th>
                            <th scope="col">Status</th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="pedidosList">
                        <?php
                        $sql = "SELECT p.id, p.data, p.tipo_pedido, p.status,
                                       p.filial_usuario_id, p.filial_destino_id,
                                       u_origem.cnpj AS cnpj_origem,
                                       COALESCE(u_origem.nome_filial, u_origem.nome) AS nome_origem,
                                       u_destino.cnpj AS cnpj_destino,
                                       COALESCE(u_destino.nome_filial, u_destino.nome) AS nome_destino
                                FROM pedidos p
                                LEFT JOIN usuarios u_origem ON p.filial_usuario_id = u_origem.id
                                LEFT JOIN usuarios u_destino ON p.filial_destino_id = u_destino.id";

                        $params = [];
                        $types = "";

                        if ($loggedInUserType == 2) { // Se for filial (tipo_usuario = 2)
                            $sql .= " WHERE (p.filial_usuario_id = ? OR p.filial_destino_id = ?)";
                            $params[] = $loggedInUserId;
                            $params[] = $loggedInUserId;
                            $types .= "ii";
                        }

                        $sql .= " ORDER BY p.data DESC";

                        $stmt = $conn->prepare($sql);
                        if (!empty($params)) {
                            $stmt->bind_param($types, ...$params);
                        }
                        $stmt->execute();
                        $pedidos = $stmt->get_result();

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

                                $nome_origem_display = htmlspecialchars($pedido['nome_origem'] ?? 'N/A');
                                if ($pedido['cnpj_origem']) {
                                    // Formata CNPJ apenas se não estiver formatado e tiver 14 dígitos
                                    $cnpj_origem_sem_formatacao = preg_replace('/[^0-9]/', '', $pedido['cnpj_origem']);
                                    if (strlen($cnpj_origem_sem_formatacao) == 14) {
                                        $cnpj_origem_formatado = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj_origem_sem_formatacao);
                                        $nome_origem_display = htmlspecialchars($pedido['nome_origem'] . ' (' . $cnpj_origem_formatado . ')');
                                    } else {
                                        $nome_origem_display = htmlspecialchars($pedido['nome_origem'] . ' (' . $pedido['cnpj_origem'] . ')');
                                    }
                                }

                                $nome_destino_display = htmlspecialchars($pedido['nome_destino'] ?? 'N/A');
                                if ($pedido['filial_destino_id'] === null && $pedido['tipo_pedido'] === 'requisicao'){
                                     $nome_destino_display = "Matriz";
                                } else if ($pedido['cnpj_destino']) {
                                    $cnpj_destino_sem_formatacao = preg_replace('/[^0-9]/', '', $pedido['cnpj_destino']);
                                    if (strlen($cnpj_destino_sem_formatacao) == 14) {
                                        $cnpj_destino_formatado = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj_destino_sem_formatacao);
                                        $nome_destino_display = htmlspecialchars($pedido['nome_destino'] . ' (' . $cnpj_destino_formatado . ')');
                                    } else {
                                         $nome_destino_display = htmlspecialchars($pedido['nome_destino'] . ' (' . $pedido['cnpj_destino'] . ')');
                                    }
                                } else if (!$pedido['filial_destino_id']) {
                                     $nome_destino_display = "N/A";
                                }
                        ?>
                        <tr class="pedido-row"
                            data-id-pedido="<?= strtolower($pedido['id']) ?>"
                            data-nome-origem="<?= strtolower(htmlspecialchars($pedido['nome_origem'] ?? '')) ?>"
                            data-cnpj-origem="<?= strtolower(htmlspecialchars($pedido['cnpj_origem'] ?? '')) ?>"
                            data-nome-destino="<?= strtolower(htmlspecialchars($pedido['nome_destino'] ?? '')) ?>"
                            data-cnpj-destino="<?= strtolower(htmlspecialchars($pedido['cnpj_destino'] ?? '')) ?>">
                            <td>#<?= $pedido['id'] ?></td>
                            <td>
                                <span class="tipo-pedido">
                                    <?= $tipoIcon ?>
                                    <?= ucfirst($pedido['tipo_pedido']) ?>
                                </span>
                            </td>
                            <td title="ID Filial Origem: <?= $pedido['filial_usuario_id'] ?? 'N/A' ?>"><?= $nome_origem_display ?></td>
                            <td title="ID Filial Destino: <?= $pedido['filial_destino_id'] ?? 'N/A' ?>"><?= $nome_destino_display ?></td>
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
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 15px; color: #adb5bd;"></i>
                                <h4>Nenhum pedido encontrado</h4>
                                <p>
                                    <?php
                                    if ($loggedInUserType == 2) {
                                        echo "Não há pedidos onde sua filial é a origem ou o destino.";
                                    } else {
                                        echo "Nenhum pedido foi registrado ainda.";
                                    }
                                    ?>
                                </p>
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
    
    <script>
        // Função simplificada para filtrar pedidos baseado apenas na pesquisa
        function filterPedidos() {
            const searchText = document.getElementById('searchInput').value.toLowerCase().trim();

            document.querySelectorAll('#pedidosList tr.pedido-row').forEach(row => {
                const pedidoNumero = row.getAttribute('data-id-pedido'); // Usando o atributo data-id-pedido
                const nomeOrigemText = row.getAttribute('data-nome-origem');
                const cnpjOrigemText = row.getAttribute('data-cnpj-origem');
                const nomeDestinoText = row.getAttribute('data-nome-destino');
                const cnpjDestinoText = row.getAttribute('data-cnpj-destino');

                const matchSearch = searchText === '' ||
                                   (pedidoNumero && pedidoNumero.includes(searchText)) ||
                                   (nomeOrigemText && nomeOrigemText.includes(searchText)) ||
                                   (cnpjOrigemText && cnpjOrigemText.includes(searchText)) ||
                                   (nomeDestinoText && nomeDestinoText.includes(searchText)) ||
                                   (cnpjDestinoText && cnpjDestinoText.includes(searchText));

                row.style.display = matchSearch ? '' : 'none';
            });
        }

        const searchInput = document.getElementById('searchInput');
        const clearButton = document.getElementById('clearSearch');

        searchInput.addEventListener('keyup', function() {
            filterPedidos();
            clearButton.style.display = searchInput.value.length > 0 ? 'block' : 'none';
        });

        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            clearButton.style.display = 'none';
            filterPedidos();
            searchInput.focus();
        });

        // Aplicar filtro de pesquisa (que inicialmente será vazio) ao carregar a página
        window.onload = function() {
            filterPedidos();
        };
    </script>
</body>
</html>