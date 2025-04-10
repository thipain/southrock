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
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            display: flex;
            font-family: 'Arial', sans-serif;
            margin: 0;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: 60px;
            background-color: #6c757d;
            transition: width 0.3s;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        .sidebar:hover {
            width: 200px;
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
            background-color: #5a6268;
        }
        .sidebar-header {
            color: white;
            text-align: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .icon {
            color: white;
            font-size: 20px;
            width: 30px;
            text-align: center;
            margin-right: 10px;
        }
        .text {
            display: none;
        }
        .sidebar a:hover .text {
            display: inline;
        }
        .sidebar a .text {
            display: inline;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .sidebar:hover a .text {
            opacity: 1;
        }
        .content {
            flex: 1;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .header {
            background-color: #6c757d;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .button {
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            padding: 8px 16px;
            border: none;
            transition: background-color 0.3s;
            cursor: pointer;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .search-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .search-input {
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 10px 15px;
            width: 100%;
            transition: border-color 0.3s;
        }
        .search-input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .filters-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .filter-tag {
            background-color: #e9ecef;
            color: #495057;
            border-radius: 25px;
            padding: 8px 15px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            display: flex;
            align-items: center;
            border: 1px solid transparent;
        }
        .filter-tag.active {
            background-color: #007bff;
            color: white;
        }
        .filter-tag:hover {
            background-color: #dee2e6;
            transform: translateY(-2px);
        }
        .filter-tag.active:hover {
            background-color: #0069d9;
        }
        .filter-tag i {
            margin-right: 8px;
        }
    </style>
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
            <h1>Dashboard da Matriz</h1>
            <p>Bem-vindo, <?php echo $_SESSION['username']; ?>!</p>
        </div>
        
        <!-- Barra de pesquisa -->
        <div class="search-container">
            <div class="input-group">
                <input type="text" class="search-input" placeholder="Pesquisar pedidos..." aria-label="Pesquisar">
                <div class="input-group-append">
                    <button class="button" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <!-- Tags de filtro -->
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
        
        <!-- Área para exibição dos resultados filtrados -->
        <div id="resultados-pedidos">
            <!-- Aqui serão exibidos os resultados dos pedidos filtrados -->
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox" style="font-size: 50px; margin-bottom: 20px; color: #adb5bd;"></i>
                <h4>Nenhum pedido encontrado</h4>
                <p>Utilize os filtros acima para encontrar pedidos específicos</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Função para alternar o estado ativo dos filtros
        function toggleActive(element) {
            const filters = document.querySelectorAll('.filter-tag');
            filters.forEach(filter => {
                filter.classList.remove('active');
            });
            element.classList.add('active');
            
            // Aqui você pode adicionar lógica para filtrar os resultados
            // baseado no filtro selecionado
        }
    </script>
</body>
</html>