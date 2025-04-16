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
    <title>Lista de Pedidos</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .pedido-card {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .pedido-header {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-4">
        <h1 class="mb-4">Pedidos Realizados</h1>

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
            echo "<p class='text-muted'>Nenhum pedido encontrado.</p>";
        endif;
        $conn->close();
        ?>
    </div>
</body>

</html>