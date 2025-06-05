<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] != 2) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fazer Pedido</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../css/fazer_pedidos.css">
</head>

<body>

    <div class="page-wrapper">
        <div class="header">
            <h1>Escolha uma Opção</h1>
        </div>

        <div class="cards-section">
            <div class="container text-center">
                <div class="row justify-content-center">
                    <div class="col-md-auto mb-4">
                        <div class="option-card">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-shopping-cart icon"></i>
                                <h5 class="card-title">Fazer Pedido</h5>
                                <p class="card-text">Clique para fazer um novo pedido.</p>
                                <a href="pedidos_requisicao.php" class="button btn mt-auto">Fazer Pedido</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-auto mb-4">
                        <div class="option-card">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-gift icon"></i>
                                <h5 class="card-title">Doar Produtos</h5>
                                <p class="card-text">Clique para doar produtos.</p>
                                <a href="doar_pedidos.php" class="button btn mt-auto">Doar Produtos</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-auto mb-4">
                        <div class="option-card">
                            <div class="card-body d-flex flex-column">
                                <i class="fas fa-exchange-alt icon"></i>
                                <h5 class="card-title">Trocar Produtos</h5>
                                <p class="card-text">Clique para trocar produtos.</p>
                                <a href="trocar_produtos.php" class="button btn mt-auto">Trocar Produtos</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer text-center">
            <a href="requisicao_pedidos.php" class="button btn">Voltar</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>