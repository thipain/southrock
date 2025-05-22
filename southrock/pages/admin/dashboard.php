<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Conexão com o banco de dados
$servername = "localhost"; // Altere para o seu servidor de banco de dados
$username = "root"; // Altere para o seu usuário do banco de dados
$password = ""; // Altere para sua senha do banco de dados
$dbname = "southrock"; // Altere para o nome do seu banco de dados

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Consulta para obter o número de pedidos pendentes
$sql_pedidos = "SELECT COUNT(*) as total_pedidos FROM pedidos WHERE status = 'novo'";
$result_pedidos = $conn->query($sql_pedidos);
$total_pedidos = 0;
if ($result_pedidos && $result_pedidos->num_rows > 0) {
    $row = $result_pedidos->fetch_assoc();
    $total_pedidos = $row["total_pedidos"];
}

// Consulta para obter o número total de produtos cadastrados
$sql_produtos = "SELECT COUNT(*) as total_produtos FROM produtos";
$result_produtos = $conn->query($sql_produtos);
$total_produtos = 0;
if ($result_produtos && $result_produtos->num_rows > 0) {
    $row = $result_produtos->fetch_assoc();
    $total_produtos = $row["total_produtos"];
}

// Consulta para obter o número total de usuários (independente do status)
$sql_usuarios = "SELECT COUNT(*) as total_usuarios FROM usuarios";
$result_usuarios = $conn->query($sql_usuarios);
$total_usuarios = 0;
if ($result_usuarios && $result_usuarios->num_rows > 0) {
    $row = $result_usuarios->fetch_assoc();
    $total_usuarios = $row["total_usuarios"];
}

// Informações do sistema
$versao_sistema = "1.3.8"; // Versão atual do sistema
$data_atualizacao = "22/05/2025"; // Data da última atualização

// Fechar conexão
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Matriz</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <div class="sidebar-header">
                <i class="fas fa-bars icon"></i><span class="text">Menu</span>
            </div>
            <a href="dashboard.php" class="active"><i class="fas fa-home icon"></i><span class="text">Início</span></a>
            <a href="pedidos.php"><i class="fas fa-shopping-cart icon"></i><span class="text">Pedidos</span></a>
            <a href="produtos.php"><i class="fas fa-box icon"></i><span class="text">Produtos</span></a>
            <a href="usuarios.php"><i class="fas fa-users icon"></i><span class="text">Usuários</span></a>
        </div>
        <a href="../../logout/logout.php"><i class="fas fa-sign-out-alt icon"></i><span class="text">Sair</span></a>
    </div>

    <!-- Conteúdo principal -->
    <div class="content">
        <div class="container py-4">
            <!-- Header com título e informação de usuário -->
            <div class="dashboard-header mb-4">
                <h1 class="painel-titulo">Painel de Controle</h1>
                <div class="user-info">
                    <span>Bem-vindo, <?php echo $_SESSION['username']; ?></span>
                    <span class="badge badge-primary ml-2">Administrador</span>
                </div>
            </div>

            <!-- Cards de estatísticas -->
            <div class="row">
                <!-- Card de Pedidos -->
                <div class="col-md-3 mb-4">
                    <div class="card estatistica-card">
                        <div class="card-body">
                            <div class="card-header-flex">
                                <h5 class="card-title">PEDIDOS</h5>
                                <div class="card-icon bg-primary">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                            </div>
                            <h2 class="card-value <?php echo ($total_pedidos > 0) ? 'text-primary' : 'text-success'; ?>"><?php echo $total_pedidos; ?></h2>
                            <p class="card-subtitle">Pedidos Pendentes</p>
                            <a href="pedidos.php" class="card-link">Ver Detalhes <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Card de Produtos -->
                <div class="col-md-3 mb-4">
                    <div class="card estatistica-card">
                        <div class="card-body">
                            <div class="card-header-flex">
                                <h5 class="card-title">PRODUTOS</h5>
                                <div class="card-icon bg-success">
                                    <i class="fas fa-box"></i>
                                </div>
                            </div>
                            <h2 class="card-value text-success"><?php echo $total_produtos; ?></h2>
                            <p class="card-subtitle">Produtos Cadastrados</p>
                            <a href="produtos.php" class="card-link">Ver Detalhes <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Card de Usuários -->
                <div class="col-md-3 mb-4">
                    <div class="card estatistica-card">
                        <div class="card-body">
                            <div class="card-header-flex">
                                <h5 class="card-title">USUÁRIOS</h5>
                                <div class="card-icon bg-info">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <h2 class="card-value text-info"><?php echo $total_usuarios; ?></h2>
                            <p class="card-subtitle">Usuários Cadastrados</p>
                            <a href="usuarios.php" class="card-link">Ver Detalhes <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Card de Sistema -->
                <div class="col-md-3 mb-4">
                    <div class="card estatistica-card">
                        <div class="card-body">
                            <div class="card-header-flex">
                                <h5 class="card-title">SISTEMA</h5>
                                <div class="card-icon bg-warning">
                                    <i class="fas fa-cog"></i>
                                </div>
                            </div>
                            <h2 class="card-value text-warning"><?php echo $versao_sistema; ?></h2>
                            <p class="card-subtitle">Versão Atual</p>
                            <p class="sistema-info">Atualizado: <?php echo $data_atualizacao; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logo e mensagem de boas-vindas -->
            <div class="logo-container text-center mt-4 mb-2">
                <img src="../../images/zamp.png" alt="Logo Zamp" class="logo-img">
                <p class="instruction-text mt-3">
                    Bem-vindo ao Sistema Matriz. Utilize o menu lateral para navegar entre as funcionalidades disponíveis.
                </p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>