<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php"); 
    exit();
}

include '../../includes/db.php'; 

$success_message = "";
$error_message = "";

$tipos_usuario_query = "SELECT id, descricao FROM tipo_usuario";
$tipos_result = $conn->query($tipos_usuario_query);
$tipos_usuario = [];
if ($tipos_result && $tipos_result->num_rows > 0) {
    while ($row = $tipos_result->fetch_assoc()) {
        $tipos_usuario[$row['id']] = $row['descricao'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $cnpj = $_POST['cnpj'];
    $responsavel = $_POST['responsavel'];
    $endereco = $_POST['endereco'];
    $cep = $_POST['cep'];
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $uf = $_POST['uf'];
    $tipo_usuario_id = $_POST['tipo_usuario'];
    $nome_completo_usuario = $_POST['nome']; 

    $check_sql = "SELECT id FROM usuarios WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error_message = "Nome de usuário já existe. Por favor, escolha outro.";
    } else {
        $eh_filial = 0; 
        $nome_filial = NULL; 

        if ($tipo_usuario_id == 2) {
            $eh_filial = 1;
            $nome_filial = $_POST['nome_filial'] ?? $nome_completo_usuario; 
        }

        $sql = "INSERT INTO usuarios (username, password, tipo_usuario, cnpj, responsavel, endereco, cep, bairro, cidade, uf, nome, eh_filial, nome_filial) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssissssssssis",
            $username,
            $password,
            $tipo_usuario_id,
            $cnpj,
            $responsavel,
            $endereco,
            $cep,
            $bairro,
            $cidade,
            $uf,
            $nome_completo_usuario,
            $eh_filial,
            $nome_filial
        );

        if ($stmt->execute()) {
            $success_message = "Usuário cadastrado com sucesso!";
        } else {
            $error_message = "Erro ao cadastrar usuário: " . $stmt->error;
        }

        $stmt->close();
    }
    $check_stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/cadastro_usuario.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            display: flex; 
            flex-direction: column; 
            font-family: 'Arial', sans-serif; 
            height: 100vh;
            background-color: #f0f2f5; 
            margin: 0; 
            padding: 0; 
        }
        
        .navbar {
            background-color: #ffffff; 
            color: #2045ff; 
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: fixed; 
            top: 0;
            width: 100%;
            z-index: 1000; 
        }

        .navbar .logo-img { 
            height: 40px; 
            width: auto;
            margin-left: 10px; 
        }

        .navbar .user-info {
            display: flex;
            align-items: center;
        }

        .navbar .user-info span {
            margin-right: 10px;
            font-weight: bold;
            color: #333;
        }

        .navbar .user-info i {
            color: #2045ff;
            font-size: 1.2rem;
        }

        .menu-toggle {
            font-size: 1.8rem;
            cursor: pointer;
            color: #2045ff; 
        }
        
        .main-wrapper {
            display: flex;
            flex: 1; 
            margin-top: 60px; 
            width: 100%;
            background-color: #f0f2f5; 
        }

        .sidebar {
            width: 0px; 
            background-color: #2045ff;
            transition: width 0.3s ease;
            overflow: hidden; 
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 999; 
            position: relative; 
            flex-shrink: 0; 
        }

        .sidebar.open {
            width: 250px; 
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            flex-grow: 1; 
            padding-top: 15px; 
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 15px;
            color: white;
            text-decoration: none;
            white-space: nowrap; 
            transition: background-color 0.3s ease;
        }

        .sidebar-menu li a .fas {
            font-size: 1.2rem;
            margin-right: 20px; 
            width: 20px; 
            text-align: center;
        }
        
        .sidebar-menu li a span {
            opacity: 0; 
            transition: opacity 0.3s ease;
        }

        .sidebar.open .sidebar-menu li a span {
            opacity: 1; 
        }
        
        .sidebar-menu li.active > a {
            background-color: #0033cc; 
            border-left: 5px solid #ffc107; 
            padding-left: 10px; 
        }

        .sidebar-menu li a:hover {
            background-color: #0033cc;
        }

        .content { 
            flex-grow: 1; 
            padding: 20px; 
            overflow-y: auto; 
            background-color: #f0f2f5; 
            display: flex; 
            justify-content: center; 
            align-items: flex-start; 
            transition: all 0.3s ease; 
        }


        .form-container {
            max-width: 800px;
            width: 100%; 
            margin: 30px auto; 
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        label.form-label { 
            display: block; 
            margin-bottom: 5px;
            font-weight: bold;
            text-align: left;
        }
        select {
            width: 100%;
            padding: 10px;
            margin: 10px 0; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box;
            background-color: white; 
        }
        select:focus {
            outline: none;
            border-color: #007bff; 
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .form-container > div { 
            margin-bottom: 15px; 
        }
        .form-header {
            margin-bottom: 30px;
            text-align: center;
        }
        .form-header h2 {
            color: #007bff; 
            font-weight: bold;
        }
        .form-header h2 i {
            margin-right: 8px;
        }

        .button-group {
            margin-top: 20px;
            display: flex; 
            gap: 10px; 
            justify-content: center; 
            flex-wrap: wrap; 
        }

        .button.secondary {
            background-color: #6c757d; 
            color: white;
            border: none;
            cursor: pointer;
            padding: 10px 15px; 
            width: 100%; 
            border-radius: 4px; 
            text-decoration: none; 
            display: inline-block; 
        }
        .button.secondary:hover {
            background-color: #5a6268;
        }

        input:invalid:not(:placeholder-shown),
        select:invalid:not([value=""]):not(:focus) { 
            border-color: #dc3545; 
        }
        
        input:valid:not(:placeholder-shown),
        select:valid:not([value=""]) {
            border-color: #28a745; 
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="d-flex align-items-center">
            <i class="fas fa-bars menu-toggle"></i>
            <img src="../../images/zamp.png" alt="Logo Zamp" class="logo-img">
        </div>
        <div class="user-info">
            <span>Olá, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <i class="fas fa-user-circle"></i>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="sidebar">
            <ul class="sidebar-menu">
                <li <?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo 'class="active"'; ?>>
                    <a href="dashboard.php">
                        <i class="fas fa-home"></i>
                        <span>Início</span>
                    </a>
                </li>

                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 1): ?>
                    <li <?php if(basename($_SERVER['PHP_SELF']) == 'aprovar_rejeitar_pedidos.php') echo 'class="active"'; ?>>
                        <a href="aprovar_rejeitar_pedidos.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Pedidos</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 1): ?>
                    <li <?php if(basename($_SERVER['PHP_SELF']) == 'produtos.php') echo 'class="active"'; ?>>
                        <a href="produtos.php">
                            <i class="fas fa-box-open"></i>
                            <span>Produtos</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 1): ?>
                    <li <?php if(basename($_SERVER['PHP_SELF']) == 'usuarios.php') echo 'class="active"'; ?>>
                        <a href="usuarios.php">
                            <i class="fas fa-users"></i>
                            <span>Usuários</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 1): ?>
                    <li <?php if(basename($_SERVER['PHP_SELF']) == 'cadastro_usuario.php') echo 'class="active"'; ?>>
                        <a href="cadastro_usuario.php">
                            <i class="fas fa-user-plus"></i>
                            <span>Cadastro Usuário</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 2): ?>
                    <li <?php if(basename($_SERVER['PHP_SELF']) == 'fazer_pedidos.php') echo 'class="active"'; ?>>
                        <a href="fazer_pedidos.php">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Pedidos</span>
                        </a>
                    </li>
                <?php endif; ?>

                <li>
                    <a href="../logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Sair</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="content"> <div class="container"> 
                <div class="form-container"> 
                    <div class="form-header">
                        <h2><i class="fas fa-user-plus"></i> Cadastro de Novo Usuário</h2>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="needs-validation" novalidate>
                        <div>
                            <label for="username" class="form-label">Nome de Usuário:</label>
                            <input type="text" id="username" name="username" required placeholder="Ex: joao.silva" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>

                        <div>
                            <label for="password" class="form-label">Senha:</label>
                            <input type="password" id="password" name="password" required placeholder="******">
                        </div>

                        <div>
                            <label for="nome" class="form-label">Nome Completo (Responsável):</label>
                            <input type="text" id="nome" name="nome" required placeholder="Ex: João da Silva" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                        </div>

                        <div>
                            <label for="tipo_usuario" class="form-label">Tipo de Usuário:</label>
                            <select id="tipo_usuario" name="tipo_usuario" required>
                                <option value="" selected disabled>Selecione um tipo de usuário</option>
                                <?php foreach ($tipos_usuario as $id => $descricao) : ?>
                                    <option value="<?php echo $id; ?>" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == $id) ? 'selected' : ''; ?>>
                                        <?php echo $descricao; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="nome-filial-group" style="display: none;">
                            <label for="nome_filial" class="form-label">Nome da Filial:</label>
                            <input type="text" id="nome_filial" name="nome_filial" placeholder="Ex: Loja Centro SP" value="<?php echo isset($_POST['nome_filial']) ? htmlspecialchars($_POST['nome_filial']) : ''; ?>">
                        </div>

                        <div>
                            <label for="cnpj" class="form-label">CNPJ:</label>
                            <input type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00" value="<?php echo isset($_POST['cnpj']) ? htmlspecialchars($_POST['cnpj']) : ''; ?>">
                        </div>

                        <div>
                            <label for="responsavel" class="form-label">Responsável (Contato):</label>
                            <input type="text" id="responsavel" name="responsavel" required placeholder="Ex: Gerente da Loja" value="<?php echo isset($_POST['responsavel']) ? htmlspecialchars($_POST['responsavel']) : ''; ?>">
                        </div>

                        <div>
                            <label for="cep" class="form-label">CEP:</label>
                            <input type="text" id="cep" name="cep" required placeholder="00000-000" value="<?php echo isset($_POST['cep']) ? htmlspecialchars($_POST['cep']) : ''; ?>">
                        </div>

                        <div>
                            <label for="endereco" class="form-label">Endereço:</label>
                            <input type="text" id="endereco" name="endereco" required placeholder="Rua Exemplo, 123" value="<?php echo isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : ''; ?>">
                        </div>

                        <div>
                            <label for="bairro" class="form-label">Bairro:</label>
                            <input type="text" id="bairro" name="bairro" required placeholder="Centro" value="<?php echo isset($_POST['bairro']) ? htmlspecialchars($_POST['bairro']) : ''; ?>">
                        </div>

                        <div>
                            <label for="cidade" class="form-label">Cidade:</label>
                            <input type="text" id="cidade" name="cidade" required placeholder="São Paulo" value="<?php echo isset($_POST['cidade']) ? htmlspecialchars($_POST['cidade']) : ''; ?>">
                        </div>

                        <div>
                            <label for="uf" class="form-label">UF:</label>
                            <input type="text" id="uf" name="uf" maxlength="2" required placeholder="SP" value="<?php echo isset($_POST['uf']) ? htmlspecialchars($_POST['uf']) : ''; ?>">
                        </div>

                        <div class="button-group">
                            <button type="submit" class="button">Cadastrar Usuário</button>
                            <a href="dashboard.php" class="button secondary">Voltar ao Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        (function () {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
        })();
        
        $(document).ready(function() {
            $('#cnpj').mask('00.000.000/0000-00');
            $('#cep').mask('00000-000');
            
            $('#cep').blur(function() {
                var cep = $(this).val().replace(/\D/g, '');
                if (cep.length === 8) {
                    $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/", function(data) {
                        if (!("erro" in data)) {
                            $('#endereco').val(data.logradouro);
                            $('#bairro').val(data.bairro);
                            $('#cidade').val(data.localidade);
                            $('#uf').val(data.uf);
                        } else {
                            Swal.fire('CEP Inválido', 'CEP não encontrado.', 'warning');
                        }
                    }).fail(function() {
                        Swal.fire('Erro de Conexão', 'Não foi possível buscar o CEP. Verifique sua conexão.', 'error');
                    });
                }
            });

            $('#tipo_usuario').change(function() {
                if ($(this).val() == '2') { 
                    $('#nome-filial-group').show();
                    $('#nome_filial').prop('required', true); 
                } else {
                    $('#nome-filial-group').hide();
                    $('#nome_filial').prop('required', false); 
                    $('#nome_filial').val(''); 
                }
            });

            $('#tipo_usuario').trigger('change');

            $('.menu-toggle').on('click', function() {
                $('.sidebar').toggleClass('open');
            });
        });
    </script>
</body>
</html>