<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Southrock</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f1f; /* Cor de fundo da página */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Altura total da tela */
            margin: 0; /* Remove margens padrão */
        }
        .container {
            background-color: rgba(100, 255, 255, 0.8); /* Fundo branco com transparência */
            border-radius: 15px; /* Bordas arredondadas */
            padding: 20px; /* Espaçamento interno */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Sombra para dar profundidade */
            width: 300px; /* Largura fixa da div */
        }
        label {
            margin-top: 10px; /* Espaçamento entre os rótulos e os campos */
        }



    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Login</h2> <!-- Adicionando a classe text-center -->
        <form action="login.php" method="POST">
            <label for="username">Usuário:</label>
            <input type="text" id="username" name="username" class="form-control" required>
            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" class="form-control" required>
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </form>
    </div>
</body>
</html>