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
                    <input type="text" class="search-input" placeholder="Pesquisar por numero de requisição ou filial...">
                    <i class="fas fa-search search-icon"></i>
                </div>

                <!-- Filtros de pedidos -->
                <div class="filters-container">
                    <div class="filter-tag active" onclick="toggleActive(this)">
                        <i class="fas fa-plus-circle"></i>
                        Novos Pedidos
                    </div>
                    <div class="filter-tag" onclick="toggleActive(this)">
                        <i class="fas fa-spinner"></i>
                        Pedidos em Processo
                    </div>
                    <div class="filter-tag" onclick="toggleActive(this)">
                        <i class="fas fa-check-circle"></i>
                        Pedidos Finalizados
                    </div>
                </div>
            </div>

            <!-- Lista de Pedidos -->
            <div class="search-container">
                <?php
                $pedidos = $conn->query("SELECT * FROM pedidos ORDER BY data ASC");
                if ($pedidos->num_rows > 0):
                    while ($pedido = $pedidos->fetch_assoc()):
                        $pedidoId = $pedido['id'];
                        $data = $pedido['data'];
                        $itens = $conn->query("SELECT i.sku, pr.produto, i.quantidade FROM pedido_itens i
                                       JOIN produtos pr ON i.sku = pr.sku
                                       WHERE pedido_id = $pedidoId");
                ?>
                        <div class="pedido-card">
                            <div class="pedido-header">Pedido #<?= $pedidoId ?> - <?= date('d/m/Y H:i', strtotime($data)) ?></div>
                            <ul class="list-group">
                                <?php while ($item = $itens->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div><strong><?= $item['sku'] ?></strong> - <?= $item['produto'] ?></div>
                                        <span class="badge badge-primary badge-pill">Qtd: <?= $item['quantidade'] ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                <?php
                    endwhile;
                else:
                    echo "<div class='text-center py-5 text-muted'>
                        <i class='fas fa-inbox' style='font-size: 50px; margin-bottom: 20px; color: #adb5bd;'></i>
                        <h4>Nenhum pedido encontrado</h4>
                        <p>Utilize os filtros acima para encontrar pedidos específicos</p>
                    </div>";
                endif;
                $conn->close();
                ?>
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