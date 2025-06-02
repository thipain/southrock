<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php"); 
    exit();
}

include '../../includes/db.php'; 

// Definições de caminhos e links para o header_com_menu.php
$path_to_css_folder_from_page = '../../css/';
$logo_image_path_from_page = '../../images/zamp.png';
$logout_script_path_from_page = '../../logout/logout.php';

$link_dashboard = 'dashboard.php';
$link_pedidos_admin = 'pedidos.php';
$link_produtos_admin = 'produtos.php';
$link_usuarios_admin = 'usuarios.php';
$link_cadastro_usuario_admin = 'cadastro_usuario.php';

$success_message = "";
$error_message = "";

$tipos_usuario_query = "SELECT id, descricao FROM tipo_usuario ORDER BY descricao ASC";
$tipos_result = $conn->query($tipos_usuario_query);
$tipos_usuario_options = [];
if ($tipos_result && $tipos_result->num_rows > 0) {
    while ($row = $tipos_result->fetch_assoc()) {
        $tipos_usuario_options[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password_plain = $_POST['password'];
    $cnpj = trim($_POST['cnpj']);
    $responsavel = trim($_POST['responsavel']);
    $endereco = trim($_POST['endereco']);
    $cep = trim($_POST['cep']);
    $bairro = trim($_POST['bairro']);
    $cidade = trim($_POST['cidade']);
    $uf = trim($_POST['uf']); // Esta é a única coluna de estado/UF que precisamos
    $tipo_usuario_id = $_POST['tipo_usuario'];
    $nome_completo_usuario = trim($_POST['nome']); 
    $nome_filial_post = isset($_POST['nome_filial']) ? trim($_POST['nome_filial']) : null;

    if (empty($username) || empty($password_plain) || empty($cnpj) || empty($responsavel) || empty($endereco) || empty($cep) || empty($bairro) || empty($cidade) || empty($uf) || empty($tipo_usuario_id) || empty($nome_completo_usuario)) {
        $error_message = "Todos os campos obrigatórios devem ser preenchidos.";
    } elseif ($tipo_usuario_id == 2 && empty($nome_filial_post)) {
        $error_message = "O nome da filial é obrigatório para o tipo de usuário 'Filial (Loja)'.";
    } else {
        $check_sql = "SELECT id FROM usuarios WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_message = "Nome de usuário (email) já existe. Por favor, escolha outro.";
        } else {
            $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);
            $eh_filial = 0; 
            $nome_filial_db = NULL; 

            if ($tipo_usuario_id == 2) { 
                $eh_filial = 1;
                $nome_filial_db = !empty($nome_filial_post) ? $nome_filial_post : $nome_completo_usuario; 
            }

            // SQL MODIFICADO: Removida a coluna 'estado' e seu placeholder
            $sql = "INSERT INTO usuarios (username, password, tipo_usuario, cnpj, responsavel, endereco, cep, bairro, cidade, uf, nome, eh_filial, nome_filial) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            // BIND_PARAM MODIFICADO: Removido um 's' e a última variável referente a 'estado'
            $stmt->bind_param(
                "ssissssssssis", // 13 parâmetros agora
                $username,
                $password_hashed,
                $tipo_usuario_id,
                $cnpj,
                $responsavel,
                $endereco,
                $cep,
                $bairro,
                $cidade,
                $uf, // Este é o UF que será gravado
                $nome_completo_usuario,
                $eh_filial,
                $nome_filial_db
            );

            if ($stmt->execute()) {
                $success_message = "Usuário '".htmlspecialchars($username)."' cadastrado com sucesso!";
                $_POST = array(); 
            } else {
                $error_message = "Erro ao cadastrar usuário: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
// A conexão é fechada no final do script original, se presente
// Se $conn->close() estiver aqui, mantenha. Se estiver no final do HTML, também.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário - Painel Administrativo</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php
        if (file_exists(__DIR__ . '/../../includes/header_com_menu.php')) {
            include __DIR__ . '/../../includes/header_com_menu.php';
        }
    ?>
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/produtos.css"> <link rel="stylesheet" href="../../css/cadastro_usuario.css">

</head>
<body class="hcm-body-fixed-header">
    <div class="hcm-main-content">
        <div class="container-fluid px-4 py-4">
            <div class="row mb-4">
                <div class="col-md-8 mx-auto">
                    <div class="painel-titulo">
                        <i class="fas fa-user-plus me-2"></i>Cadastro de Novo Usuário
                    </div>
                    <hr class="barrinha">
                </div>
            </div>
           
            <?php if ($success_message): ?>
            <div class="row mb-3">
                <div class="col-md-8 mx-auto">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="row mb-3">
                <div class="col-md-8 mx-auto">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card estatistica-card">
                        <div class="card-body p-4">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="needs-validation" novalidate>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="username" class="form-label">Nome de Usuário (Email):</label>
                                        <input type="email" class="form-control" id="username" name="username" required placeholder="Ex: joao.silva@dominio.com" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="password" class="form-label">Senha:</label>
                                        <input type="password" class="form-control" id="password" name="password" required placeholder="Mínimo 6 caracteres">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="nome" class="form-label">Nome Completo (Usuário/Responsável):</label>
                                        <input type="text" class="form-control" id="nome" name="nome" required placeholder="Ex: João da Silva" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="tipo_usuario" class="form-label">Tipo de Usuário:</label>
                                        <select id="tipo_usuario" name="tipo_usuario" class="form-control" required>
                                            <option value="" selected disabled>Selecione um tipo</option>
                                            <?php foreach ($tipos_usuario_options as $tipo_opt) : ?>
                                                <option value="<?php echo htmlspecialchars($tipo_opt['id']); ?>" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == $tipo_opt['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($tipo_opt['descricao']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group" id="nome-filial-group" style="display: none;">
                                    <label for="nome_filial" class="form-label">Nome da Filial (Loja):</label>
                                    <input type="text" class="form-control" id="nome_filial" name="nome_filial" placeholder="Ex: Loja Zamp Centro SP" value="<?php echo isset($_POST['nome_filial']) ? htmlspecialchars($_POST['nome_filial']) : ''; ?>">
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="cnpj" class="form-label">CNPJ:</label>
                                        <input type="text" class="form-control" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00" value="<?php echo isset($_POST['cnpj']) ? htmlspecialchars($_POST['cnpj']) : ''; ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="responsavel" class="form-label">Nome do Contato (Responsável na Filial):</label>
                                        <input type="text" class="form-control" id="responsavel" name="responsavel" required placeholder="Ex: Maria Souza (Gerente)" value="<?php echo isset($_POST['responsavel']) ? htmlspecialchars($_POST['responsavel']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="cep" class="form-label">CEP:</label>
                                        <input type="text" class="form-control" id="cep" name="cep" required placeholder="00000-000" value="<?php echo isset($_POST['cep']) ? htmlspecialchars($_POST['cep']) : ''; ?>">
                                    </div>
                                    <div class="form-group col-md-8">
                                        <label for="endereco" class="form-label">Endereço:</label>
                                        <input type="text" class="form-control" id="endereco" name="endereco" required placeholder="Rua Exemplo, 123" value="<?php echo isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : ''; ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="bairro" class="form-label">Bairro:</label>
                                        <input type="text" class="form-control" id="bairro" name="bairro" required placeholder="Centro" value="<?php echo isset($_POST['bairro']) ? htmlspecialchars($_POST['bairro']) : ''; ?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="cidade" class="form-label">Cidade:</label>
                                        <input type="text" class="form-control" id="cidade" name="cidade" required placeholder="São Paulo" value="<?php echo isset($_POST['cidade']) ? htmlspecialchars($_POST['cidade']) : ''; ?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="uf" class="form-label">UF:</label>
                                        <input type="text" class="form-control" id="uf" name="uf" maxlength="2" required placeholder="SP" value="<?php echo isset($_POST['uf']) ? htmlspecialchars($_POST['uf']) : ''; ?>">
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-success btn-block">Cadastrar Usuário</button>
                                    <a href="dashboard.php" class="btn btn-outline-secondary btn-block">Voltar ao Dashboard</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="sistema-info text-center mt-3">
                Sistema de Gerenciamento SouthRock © <?php echo date('Y'); ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
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
            
            function limpa_formulário_cep() {
                $("#endereco").val("");
                $("#bairro").val("");
                $("#cidade").val("");
                $("#uf").val(""); // Mantém UF pois é digitado/selecionado manualmente
            }

            $('#cep').blur(function() {
                var cep = $(this).val().replace(/\D/g, '');
                if (cep.length === 8) {
                    $("#endereco").val("...");
                    $("#bairro").val("...");
                    $("#cidade").val("...");
                    // $("#uf").val("..."); // UF não será preenchida automaticamente para evitar confusão com a digitação manual
                    $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(data) {
                        if (!("erro" in data)) {
                            $("#endereco").val(data.logradouro);
                            $("#bairro").val(data.bairro);
                            $("#cidade").val(data.localidade);
                            $("#uf").val(data.uf); // ViaCEP preenche UF
                        } else {
                            limpa_formulário_cep();
                            Swal.fire('CEP Inválido', 'CEP não encontrado.', 'warning');
                        }
                    }).fail(function() {
                        limpa_formulário_cep();
                        Swal.fire('Erro de Conexão', 'Não foi possível buscar o CEP. Verifique sua conexão ou tente mais tarde.', 'error');
                    });
                } else if (cep.length > 0) { // Se o CEP não tiver 8 dígitos mas tiver algum valor
                    limpa_formulário_cep();
                     Swal.fire('CEP Incompleto', 'Por favor, digite um CEP válido com 8 dígitos.', 'info');
                } else { // Se o campo CEP estiver vazio
                     limpa_formulário_cep();
                }
            });

            $('#tipo_usuario').change(function() {
                if ($(this).val() == '2') { 
                    $('#nome-filial-group').slideDown(); 
                    $('#nome_filial').prop('required', true); 
                } else {
                    $('#nome-filial-group').slideUp(); 
                    $('#nome_filial').prop('required', false); 
                    $('#nome_filial').val(''); 
                }
            }).trigger('change'); 

            // Script para fechar alertas automaticamente
            var successAlert = document.querySelector('.alert-success.alert-dismissible');
            if (successAlert && typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                setTimeout(function() {
                    bootstrap.Alert.getOrCreateInstance(successAlert).close();
                }, 5000);
            }
            var errorAlert = document.querySelector('.alert-danger.alert-dismissible');
            if (errorAlert && typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                 setTimeout(function() {
                    bootstrap.Alert.getOrCreateInstance(errorAlert).close();
                }, 5000);
            }
        });
    </script>
</body>
</html>
<?php
if(isset($conn)) { // Garante que a conexão só é fechada se foi aberta.
    $conn->close();
}
?>