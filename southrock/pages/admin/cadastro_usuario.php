<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

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
    $tipo_usuario_id = $_POST['tipo_usuario']; // Agora recebemos o ID do tipo_usuario selecionado no form

    // Verificar se o nome de usuário já existe
    $check_sql = "SELECT id FROM usuarios WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error_message = "Nome de usuário já existe. Escolha outro.";
    } else {
        // Inserir novo usuário
        $sql = "INSERT INTO usuarios (username, password, cnpj, responsavel, endereco, cep, bairro, cidade, uf, tipo_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", $username, $password, $cnpj, $responsavel, $endereco, $cep, $bairro, $cidade, $uf, $tipo_usuario_id);
        
        if ($stmt->execute()) {
            $success_message = "Usuário cadastrado com sucesso!";
        } else {
            $error_message = "Erro ao cadastrar usuário: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário - Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/usuarios.css">
    <style>
        /* Estilos herdados do pedidos.css */
        body {
            display: flex;
            font-family: 'Arial', sans-serif;
            margin: 0;
            height: 100vh;
            background-color: #fff4e8;
        }

        .sidebar {
            width: 60px;
            background-image: linear-gradient(to left, rgb(124, 187, 235) 0%, rgb(60, 111, 177) 50%, rgb(0, 37, 78) 100%);
            transition: width 0.3s;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            height: 100vh;
            position: fixed;
            z-index: 1000;
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
            background-color: #480ca8;
        }

        .sidebar-header {
            color: white;
            text-align: center;
            padding: 13px 0;
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
            padding: 0;
            background-color: #E2EDFA;
            display: flex;
            flex-direction: column;
            margin-left: 60px;
            transition: margin-left 0.3s;
            width: 100%;
        }

        .header {
            background-image: linear-gradient(to right, rgb(124, 187, 235) 0%, rgb(60, 111, 177) 50%, rgb(0, 37, 78) 100%);
            color: white;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            margin-bottom: 0;
            border-radius: 0;
        }

        .header h1 {
            margin: 0;
            font-size: 1.5rem;
        }

        .main-content {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }

        .card-custom {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            background-color: white;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
        }

        .form-control:focus {
            border-color: #0077B6;
            box-shadow: 0 0 0 0.2rem rgba(0, 119, 182, 0.25);
        }

        .btn-primary {
            background-color: #0077B6;
            border-color: #0077B6;
        }

        .btn-primary:hover {
            background-color: #023E8A;
            border-color: #023E8A;
        }

        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
        }

        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
        }

        .form-floating > .form-control {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            opacity: 0.65;
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <div class="sidebar-header">
                <i class="bi bi-shield-lock icon"></i>
            </div>
            <a href="dashboard.php">
                <i class="bi bi-speedometer2 icon"></i>
                <span class="text">Dashboard</span>
            </a>
            <a href="usuarios.php" class="active">
                <i class="bi bi-people-fill icon"></i>
                <span class="text">Usuários</span>
            </a>
            <a href="pedidos.php">
                <i class="bi bi-cart-fill icon"></i>
                <span class="text">Pedidos</span>
            </a>
            <a href="produtos.php">
                <i class="bi bi-box-seam icon"></i>
                <span class="text">Produtos</span>
            </a>
            <a href="relatorios.php">
                <i class="bi bi-file-earmark-bar-graph icon"></i>
                <span class="text">Relatórios</span>
            </a>
        </div>
        <div>
            <a href="configuracoes.php">
                <i class="bi bi-gear-fill icon"></i>
                <span class="text">Configurações</span>
            </a>
            <a href="logout.php">
                <i class="bi bi-box-arrow-right icon"></i>
                <span class="text">Sair</span>
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="header">
            <h1><i class="bi bi-person-plus-fill me-2"></i>Cadastro de Usuário</h1>
        </div>
        
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary h3">Novo Usuário</h2>
                <a href="usuarios.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Voltar
                </a>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-body p-4">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <!-- Informações de Acesso -->
                            <div class="col-12">
                                <h4 class="mb-3 text-primary border-bottom pb-2">Informações de Acesso</h4>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="username" name="username" placeholder=" " required>
                                    <label for="username">Nome de usuário</label>
                                    <div class="invalid-feedback">
                                        Por favor, insira um nome de usuário.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control" id="password" name="password" placeholder=" " required>
                                    <label for="password">Senha</label>
                                    <div class="invalid-feedback">
                                        Por favor, insira uma senha.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informações do Usuário -->
                            <div class="col-12">
                                <h4 class="mb-3 mt-2 text-primary border-bottom pb-2">Dados do Usuário</h4>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="cnpj" name="cnpj" placeholder=" " required>
                                    <label for="cnpj">CNPJ</label>
                                    <div class="invalid-feedback">
                                        Por favor, insira um CNPJ válido.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="responsavel" name="responsavel" placeholder=" " required>
                                    <label for="responsavel">Responsável</label>
                                    <div class="invalid-feedback">
                                        Por favor, insira o nome do responsável.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Endereço -->
                            <div class="col-12">
                                <h4 class="mb-3 mt-2 text-primary border-bottom pb-2">Endereço</h4>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="endereco" name="endereco" placeholder=" " required>
                                    <label for="endereco">Endereço</label>
                                    <div class="invalid-feedback">
                                        Por favor, insira o endereço.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="cep" name="cep" placeholder=" " required>
                                    <label for="cep">CEP</label>
                                    <div class="invalid-feedback">
                                        Por favor, insira o CEP.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="bairro" name="bairro" placeholder=" " required>
                                    <label for="bairro">Bairro</label>
                                    <div class="invalid-feedback">
                                        Por favor, insira o bairro.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="cidade" name="cidade" placeholder=" " required>
                                    <label for="cidade">Cidade</label>
                                    <div class="invalid-feedback">
                                        Por favor, insira a cidade.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="uf" name="uf" required>
                                        <option value="" selected disabled>Selecione</option>
                                        <option value="AC">AC</option>
                                        <option value="AL">AL</option>
                                        <option value="AP">AP</option>
                                        <option value="AM">AM</option>
                                        <option value="BA">BA</option>
                                        <option value="CE">CE</option>
                                        <option value="DF">DF</option>
                                        <option value="ES">ES</option>
                                        <option value="GO">GO</option>
                                        <option value="MA">MA</option>
                                        <option value="MT">MT</option>
                                        <option value="MS">MS</option>
                                        <option value="MG">MG</option>
                                        <option value="PA">PA</option>
                                        <option value="PB">PB</option>
                                        <option value="PR">PR</option>
                                        <option value="PE">PE</option>
                                        <option value="PI">PI</option>
                                        <option value="RJ">RJ</option>
                                        <option value="RN">RN</option>
                                        <option value="RS">RS</option>
                                        <option value="RO">RO</option>
                                        <option value="RR">RR</option>
                                        <option value="SC">SC</option>
                                        <option value="SP">SP</option>
                                        <option value="SE">SE</option>
                                        <option value="TO">TO</option>
                                    </select>
                                    <label for="uf">UF</label>
                                    <div class="invalid-feedback">
                                        Por favor, selecione o estado.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tipo de Usuário -->
                            <div class="col-12">
                                <h4 class="mb-3 mt-2 text-primary border-bottom pb-2">Permissões</h4>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <div class="form-floating">
                                    <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                                        <option value="" selected disabled>Selecione o tipo de usuário</option>
                                        <?php foreach($tipos_usuario as $id => $descricao): ?>
                                            <option value="<?php echo $id; ?>"><?php echo $descricao; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="tipo_usuario">Tipo de Usuário</label>
                                    <div class="invalid-feedback">
                                        Por favor, selecione o tipo de usuário.
                                    </div>
                                </div>
                                <small class="text-muted">Selecione o nível de acesso deste usuário no sistema.</small>
                            </div>
                            
                            <div class="col-12 mt-4 d-flex justify-content-end">
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="window.location.href='usuarios.php'">Cancelar</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus-fill me-1"></i>Cadastrar Usuário
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
 
    <!-- Bootstrap JS e Dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-mask-plugin@1.14.16/dist/jquery.mask.min.js"></script>
    <script>
        // Script para validação do formulário
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
        
        // Aplicar máscaras nos campos
        $(document).ready(function() {
            $('#cnpj').mask('00.000.000/0000-00');
            $('#cep').mask('00000-000');
            
            // Função para buscar CEP via API
            $('#cep').blur(function() {
                var cep = $(this).val().replace(/\D/g, '');
                if (cep.length === 8) {
                    $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/", function(data) {
                        if (!("erro" in data)) {
                            $('#endereco').val(data.logradouro);
                            $('#bairro').val(data.bairro);
                            $('#cidade').val(data.localidade);
                            $('#uf').val(data.uf);
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>