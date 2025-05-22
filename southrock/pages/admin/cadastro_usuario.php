<?php
session_start();
// Verifica se o usuário está logado. Redireciona para o login se não estiver.
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php"); // Assumindo que index.php é sua página de login
    exit();
}

// Inclui o arquivo de conexão com o banco de dados
include '../../includes/db.php'; 

$success_message = "";
$error_message = "";

// Buscar os tipos de usuário disponíveis no sistema
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
    $nome_completo_usuario = $_POST['nome']; // Nome completo do usuário/responsável

    // Verificar se o nome de usuário já existe
    $check_sql = "SELECT id FROM usuarios WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error_message = "Nome de usuário já existe. Por favor, escolha outro.";
    } else {
        // Lógica para definir eh_filial e nome_filial
        $eh_filial = 0; // Padrão: não é filial
        $nome_filial = NULL; // Padrão: sem nome de filial

        // Se o tipo de usuário selecionado for '2' (Loja), define eh_filial como TRUE (1)
        // E o nome_filial será o valor do novo campo 'nome_filial' do formulário
        if ($tipo_usuario_id == 2) {
            $eh_filial = 1;
            // Pega o nome da filial do novo campo 'nome_filial'. Se não existir, usa o nome do responsável.
            $nome_filial = $_POST['nome_filial'] ?? $nome_completo_usuario; 
        }

        // Preparar a consulta SQL para inserir o novo usuário
        $sql = "INSERT INTO usuarios (username, password, tipo_usuario, cnpj, responsavel, endereco, cep, bairro, cidade, uf, nome, eh_filial, nome_filial) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        // Bind dos parâmetros. 's' para string, 'i' para inteiro, 's' para string.
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
        /* Estes estilos complementam seu cadastro_usuario.css e dashboard.css */
        body {
            display: flex; 
            flex-direction: column; /* Organiza o body em coluna: navbar, depois o resto */
            font-family: 'Arial', sans-serif; 
            height: 100vh;
            background-color: #f0f2f5; /* Fundo principal claro */
            margin: 0; 
            padding: 0; 
        }
        
        /* NAVBAR SUPERIOR */
        .navbar {
            background-color: #ffffff; /* Fundo branco para a navbar */
            color: #2045ff; /* Cor do texto e ícones */
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: fixed; /* Fixa a navbar no topo */
            top: 0;
            width: 100%;
            z-index: 1000; /* Garante que a navbar fique acima de outros elementos */
        }

        /* Removido .navbar .logo-text, substituído por .navbar .logo-img */
        .navbar .logo-img { /* Novo estilo para a imagem da logo na navbar */
            height: 40px; /* Ajuste a altura conforme necessário */
            width: auto;
            margin-left: 10px; /* Espaço entre o hambúrguer e a logo */
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
            /* margin-right: 20px; remova se a imagem já tiver margin-left suficiente */
            color: #2045ff; /* Cor do ícone de hambúrguer */
        }
        
        /* WRAPPER PARA SIDEBAR E CONTEÚDO */
        .main-wrapper {
            display: flex;
            flex: 1; /* Ocupa o espaço restante após a navbar */
            margin-top: 60px; /* Espaço para a navbar fixa (altura da navbar) */
            width: 100%;
            background-color: #f0f2f5; /* Fundo do wrapper igual ao do conteúdo */
        }

        /* SIDEBAR */
        .sidebar {
            width: 0px; /* **INICIALMENTE ESCONDIDA** */
            background-color: #2045ff;
            transition: width 0.3s ease;
            overflow: hidden; /* IMPORTANTE: Garante que o conteúdo que excede a largura não seja visível */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 999; /* Abaixo da navbar, mas acima do conteúdo */
            position: relative; 
            flex-shrink: 0; /* Impede que a sidebar encolha quando o conteúdo é muito largo */
        }

        .sidebar.open {
            width: 250px; /* Largura expandida ao clicar */
        }

        /* REMOVIDO: Todo o estilo para .sidebar-header e .sidebar-header img */
        /* Pois o header com a logo agora está na navbar */
        .sidebar-menu {
            list-style: none;
            padding: 0;
            flex-grow: 1; /* Faz a lista ocupar o espaço central */
            padding-top: 15px; /* Adiciona um padding no topo da navegação, já que não tem mais header */
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 15px;
            color: white;
            text-decoration: none;
            white-space: nowrap; /* Impede que o texto quebre linha */
            transition: background-color 0.3s ease;
        }

        .sidebar-menu li a .fas {
            font-size: 1.2rem;
            margin-right: 20px; /* Espaço entre ícone e texto */
            width: 20px; /* Garante que os ícones fiquem alinhados */
            text-align: center;
        }
        
        .sidebar-menu li a span {
            opacity: 0; /* Esconde o texto inicialmente */
            transition: opacity 0.3s ease;
        }

        .sidebar.open .sidebar-menu li a span {
            opacity: 1; /* Mostra o texto quando a sidebar está aberta */
        }
        
        .sidebar-menu li.active > a {
            background-color: #0033cc; /* Cor de fundo para o item ativo */
            border-left: 5px solid #ffc107; /* Borda lateral amarela */
            padding-left: 10px; /* Ajusta o padding para compensar a borda */
        }

        .sidebar-menu li a:hover {
            background-color: #0033cc;
        }

        /* Conteúdo principal */
        .content { 
            flex-grow: 1; 
            padding: 20px; 
            overflow-y: auto; 
            background-color: #f0f2f5; 
            display: flex; /* Adicionado para usar flexbox para centralizar o formulário */
            justify-content: center; /* Centraliza horizontalmente o conteúdo do .content */
            align-items: flex-start; /* Alinha o conteúdo ao topo (ou center, se preferir centralizar verticalmente) */
            transition: all 0.3s ease; /* Transição para suavizar a centralização/movimento */
        }


        .form-container {
            max-width: 800px;
            width: 100%; /* Permite que o container use a largura máxima dentro do content */
            margin: 30px auto; /* O 'auto' aqui continuará a centralizar HORIZONTALMENTE o formulário dentro do .content */
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        /* Estilo para as labels */
        label.form-label { 
            display: block; 
            margin-bottom: 5px;
            font-weight: bold;
            text-align: left;
        }
        /* Estilo para o <select> para se parecer com seus inputs */
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
            border-color: #007bff; /* Borda azul ao focar, similar ao Bootstrap */
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        /* Estilo para os grupos de campos (divs que agrupam label e input/select) */
        .form-container > div { 
            margin-bottom: 15px; 
        }
        /* Para o cabeçalho do formulário */
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

        /* Grupo de botões */
        .button-group {
            margin-top: 20px;
            display: flex; 
            gap: 10px; 
            justify-content: center; 
            flex-wrap: wrap; 
        }

        /* Estilo para o botão secundário (Voltar ao Dashboard) */
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

        /* ESTILO PARA VALIDAÇÃO: Borda vermelha em campos inválidos */
        input:invalid:not(:placeholder-shown),
        select:invalid:not([value=""]):not(:focus) { 
            border-color: #dc3545; /* Cor da borda vermelha */
        }
        
        /* Opcional: Estilo para a borda verde quando o campo é válido */
        input:valid:not(:placeholder-shown),
        select:valid:not([value=""]) {
            border-color: #28a745; /* Cor da borda verde */
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

                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 1): // Administrador ?>
                    <li <?php if(basename($_SERVER['PHP_SELF']) == 'aprovar_rejeitar_pedidos.php') echo 'class="active"'; ?>>
                        <a href="aprovar_rejeitar_pedidos.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Pedidos</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 1): // Administrador ?>
                    <li <?php if(basename($_SERVER['PHP_SELF']) == 'produtos.php') echo 'class="active"'; ?>>
                        <a href="produtos.php">
                            <i class="fas fa-box-open"></i>
                            <span>Produtos</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 1): // Administrador ?>
                    <li <?php if(basename($_SERVER['PHP_SELF']) == 'usuarios.php') echo 'class="active"'; ?>>
                        <a href="usuarios.php">
                            <i class="fas fa-users"></i>
                            <span>Usuários</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 1): // Administrador ?>
                    <li <?php if(basename($_SERVER['PHP_SELF']) == 'cadastro_usuario.php') echo 'class="active"'; ?>>
                        <a href="cadastro_usuario.php">
                            <i class="fas fa-user-plus"></i>
                            <span>Cadastro Usuário</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 2): // Loja ?>
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
        // Validação HTML5 do formulário - Mantido para a borda vermelha e impedir envio
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
        
        // Aplicar máscaras nos campos e buscar CEP via API
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

            // Lógica para mostrar/esconder o campo 'nome_filial'
            $('#tipo_usuario').change(function() {
                // Supondo que '2' é o ID para o tipo de usuário 'Loja'
                if ($(this).val() == '2') { 
                    $('#nome-filial-group').show();
                    $('#nome_filial').prop('required', true); // Torna o campo obrigatório
                } else {
                    $('#nome-filial-group').hide();
                    $('#nome_filial').prop('required', false); // Remove a obrigatoriedade
                    $('#nome_filial').val(''); // Limpa o valor ao esconder
                }
            });

            // Trigger inicial para o caso de o formulário ser carregado com um tipo de usuário pré-selecionado
            $('#tipo_usuario').trigger('change');

            // Lógica do Menu Hambúrguer (Toggle Sidebar)
            $('.menu-toggle').on('click', function() {
                $('.sidebar').toggleClass('open');
            });
        });
    </script>
</body>
</html>