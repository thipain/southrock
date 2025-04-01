<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - Filiais</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .header {
            background-color: #6c757d;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .card {
            margin: 15px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .button {
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .icon {
            font-size: 50px;
            color: #007bff;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Bem-vindo ao Sistema de Pedidos</h1>
        <p>Filiais podem fazer pedidos para a matriz</p>
    </div>

    <div class="container text-center">
        <div class="row">
            <div id="fazer-pedido" class="card col">
                <div class="card-body">
                    <i class="fas fa-shopping-cart icon"></i>
                    <h5 class="card-title">Fazer Pedido</h5>
                    <p class="card-text">Clique no botão abaixo para fazer um novo pedido.</p>
                    <a href="fazer_pedidos.php" class="button btn">Fazer Pedido</a>
                </div>
            </div>

            <div id="historico-pedidos" class="card col">
                <div class="card-body">
                    <i class="fas fa-history icon"></i>
                    <h5 class="card-title">Histórico de Pedidos</h5>
                    <p class="card-text">Veja o histórico e o status dos pedidos realizados.</p>
                    <button class="button btn">Ver Histórico</button>
                </div>
            </div>

            <div id="suporte" class="card col">
                <div class="card-body">
                    <i class="fas fa-headset icon"></i>
                    <h5 class="card-title">Suporte</h5>
                    <p class="card-text">Precisa de ajuda? Entre em contato com o suporte.</p>
                    <button class="button btn">Contato</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Botão para voltar ao index.php na parte inferior -->
    <div class="footer">
        <a href="../index.php" class="button btn">Logout</a>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>