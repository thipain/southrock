<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fazer Pedido</title>
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
        .option-card {
            margin: 15px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s;
        }
        .option-card:hover {
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
            color: #007bff;
            font-size: 30px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Escolha uma Opção</h1>
    </div>

    <div class="container text-center">
        <div class="row">
            <div class="option-card col">
                <div class="card-body">
                    <i class="fas fa-shopping-cart icon"></i>
                    <h5 class="card-title">Fazer Pedido</h5>
                    <p class="card-text">Clique para fazer um novo pedido.</p>
                    <button class="button btn">Fazer Pedido</button>
                </div>
            </div>

            <div class="option-card col">
                <div class="card-body">
                    <i class="fas fa-gift icon"></i>
                    <h5 class="card-title">Doar Produtos</h5>
                    <p class="card-text">Clique para doar produtos.</p>
                    <button class="button btn">Doar Produtos</button>
                </div>
            </div>

            <div class="option-card col">
                <div class="card-body">
                    <i class="fas fa-undo icon"></i>
                    <h5 class="card-title">Devolver Produtos</h5>
                    <p class="card-text">Clique para devolver produtos.</p>
                    <button class="button btn">Devolver Produtos</button>
                </div>
            </div>

            <div class="option-card col">
                <div class="card-body">
                    <i class="fas fa-exchange-alt icon"></i>
                    <h5 class="card-title">Trocar Produtos</h5>
                    <p class="card-text">Clique para trocar produtos.</p>
                    <button class="button btn">Trocar Produtos</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Botão para voltar ao index.php -->
    <div class="footer text-center">
        <a href="requisicao_pedidos.php" class="button btn">Voltar</a>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>