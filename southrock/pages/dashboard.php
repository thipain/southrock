<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Matriz</title>
    <style>
        body {
            display: flex;
            font-family: Arial, sans-serif;
            margin: 0;
            height: 100vh; /* Altura total da tela */
        }
        .sidebar {
            width: 60px; /* Largura inicial do menu */
            background-color: #333;
            transition: width 0.3s;
            overflow: hidden; /* Esconde o conteúdo que ultrapassa a largura */
            display: flex;
            flex-direction: column; /* Alinha os itens verticalmente */
            justify-content: space-between; /* Espaça os itens */
        }
        .sidebar:hover {
            width: 200px; /* Largura expandida ao passar o mouse */
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 15px;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }
        .sidebar a:hover {
            background-color: #575757;
        }
        .icon {
            width: 30px; /* Tamanho do ícone */
            height: 30px;
            margin-right: 10px; /* Espaço entre ícone e texto */
        }
        .text {
            display: none; /* Esconde o texto inicialmente */
        }
        .sidebar a:hover .text {
            display: inline; /* Mostra o texto ao passar o mouse */
        }
        .sidebar a .text {
            display: inline; /* Mostra o texto sempre, mas será escondido na largura inicial */
            opacity: 0; /* Inicialmente invisível */
            transition: opacity 0.3s; /* Transição suave para a opacidade */
        }
        .sidebar:hover a .text {
            opacity: 1; /* Torna o texto visível ao passar o mouse sobre a sidebar */
        }
        .content {
            flex: 1;
            padding: 20px;
            background-color: #f4f4f4; /* Cor de fundo para a área de conteúdo */
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2 style="color: white; text-align: center;">Menu</h2>

        <div>
            <a href="pedidos.php">
                <img src="../images/icon_orders.png" alt="Pedidos" class="icon">
                <span class="text">Pedidos</span>
            </a>

            <a href="usuarios.php">
                <img src="../images/icon_users.png" alt="Usuários" class="icon">
                <span class="text">Usuários</span>
            </a>

            <a href="produtos.php">
                <img src="../images/icon_products.png" alt="Produtos" class="icon">
                <span class="text">Produtos</span>
            </a>
        </div>

        <a href="../logout/logout.php">
            <img src="../images/icon_logout.png" alt="Deslogar" class="icon">
            <span class="text">Sair</span>
        </a>
    </div>
    <div class="content">
        <h1>Bem-vindo ao Dashboard da Matriz, <?php echo $_SESSION['username']; ?>!</h1>
    </div>
</body>
</html>