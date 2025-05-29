<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Assumindo que dashboard.php está em admin/pages/, e index.php está dois níveis acima
    header("Location: ../../index.php"); 
    exit();
}

// Conexão com o banco de dados
$servername = "localhost"; 
$username_db = "root"; // Renomeado para evitar conflito com $_SESSION['username']
$password_db = ""; 
$dbname = "southrock"; 

$conn = new mysqli($servername, $username_db, $password_db, $dbname);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Consultas SQL
$sql_pedidos = "SELECT COUNT(*) as total_pedidos FROM pedidos WHERE status = 'novo'";
$result_pedidos = $conn->query($sql_pedidos);
$total_pedidos = 0;
if ($result_pedidos && $result_pedidos->num_rows > 0) {
    $row = $result_pedidos->fetch_assoc();
    $total_pedidos = $row["total_pedidos"];
}

$sql_produtos = "SELECT COUNT(*) as total_produtos FROM produtos";
$result_produtos = $conn->query($sql_produtos);
$total_produtos = 0;
if ($result_produtos && $result_produtos->num_rows > 0) {
    $row = $result_produtos->fetch_assoc();
    $total_produtos = $row["total_produtos"];
}

$sql_usuarios = "SELECT COUNT(*) as total_usuarios FROM usuarios";
$result_usuarios = $conn->query($sql_usuarios);
$total_usuarios = 0;
if ($result_usuarios && $result_usuarios->num_rows > 0) {
    $row = $result_usuarios->fetch_assoc();
    $total_usuarios = $row["total_usuarios"];
}

$versao_sistema = "1.4.5"; 
$data_atualizacao = "27/05/2025"; 

$conn->close();


$path_to_css_folder_from_page = '../../css/';
$logo_image_path_from_page = '../../images/zamp.png';
$logout_script_path_from_page = '../../logout/logout.php';


$link_dashboard = 'dashboard.php'; 
$link_pedidos_admin = 'pedidos.php'; 
$link_produtos_admin = 'produtos.php';
$link_usuarios_admin = 'usuarios.php';
$link_cadastro_usuario_admin = 'cadastro_usuario.php';


?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Matriz</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <?php

        if (file_exists(__DIR__ . '/../../includes/header_com_menu.php')) {
            include __DIR__ . '/../../includes/header_com_menu.php';
        } else {
            echo "Erro: Arquivo de menu não encontrado. Verifique o caminho do include.";
        }
    ?>
     <link rel="stylesheet" href="../../css/dashboard.css">


</head>

<body class="hcm-body-fixed-header"> 

    
    <?php // include __DIR__ . '/../../includes/header_com_menu.php'; ?>
    


    <div class="hcm-main-content">  
        <div class="container py-4">
            <div class="dashboard-header mb-4">
                <h1 class="painel-titulo">Painel de Controle</h1>
                <div class="user-info">
                   
                   <span>Bem-vindo, <?php //echo htmlspecialchars($_SESSION['username']); ?></span> 
                    <span class="badge badge-primary ml-2">Administrador</span> 
                </div>
            </div>

            <div class="row">
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
                            <a href="<?php echo htmlspecialchars($link_pedidos_admin); ?>" class="card-link">Ver Detalhes <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

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
                            <a href="<?php echo htmlspecialchars($link_produtos_admin); ?>" class="card-link">Ver Detalhes <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

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
                            <a href="<?php echo htmlspecialchars($link_usuarios_admin); ?>" class="card-link">Ver Detalhes <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

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

            <div class="logo-container text-center mt-4 mb-2">
                <img src="<?php echo htmlspecialchars($logo_image_path_from_page); ?>" alt="Logo Zamp" class="logo-img">
                <p class="instruction-text mt-3">
                    Bem-vindo ao Sistema Matriz. Utilize o menu lateral para navegar entre as funcionalidades disponíveis.
                </p>
            </div>
        </div>
    </div>

    {/* <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script> jQuery já carregado no head */}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>