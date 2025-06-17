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


$user_id_to_edit = null;
$user_data = null;
$mensagem_erro_popup = '';

if (isset($_GET['id'])) {
    $user_id_to_edit = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($user_id_to_edit === false) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'ID de usuário inválido.'];
        header("Location: usuarios.php");
        exit();
    }

    // SELECT * ainda funciona, mas não trará a coluna 'estado' que foi dropada
    $sql_get_user = "SELECT id, username, tipo_usuario, cnpj, responsavel, endereco, cep, bairro, cidade, uf, nome, eh_filial, nome_filial FROM usuarios WHERE id = ?";
    $stmt_get_user = $conn->prepare($sql_get_user);
    $stmt_get_user->bind_param("i", $user_id_to_edit);
    $stmt_get_user->execute();
    $result_get_user = $stmt_get_user->get_result();

    if ($result_get_user->num_rows == 1) {
        $user_data = $result_get_user->fetch_assoc();
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Usuário não encontrado.'];
        header("Location: usuarios.php");
        exit();
    }
    $stmt_get_user->close();
} else {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'ID de usuário não fornecido.'];
    header("Location: usuarios.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($user_id_to_edit)) {
    $username = trim($_POST['username']);
    $tipo_usuario = $_POST['tipo_usuario'];
    $cnpj = trim($_POST['cnpj']);
    $responsavel = trim($_POST['responsavel']);
    $endereco = trim($_POST['endereco']);
    $cep = trim($_POST['cep']);
    $bairro = trim($_POST['bairro']);
    $cidade = trim($_POST['cidade']);
    $uf = trim($_POST['uf']); // Única coluna de estado/UF
    $nome_completo_usuario = trim($_POST['nome']); // Assumindo que o nome também pode ser editado
    $nome_filial_post = isset($_POST['nome_filial']) ? trim($_POST['nome_filial']) : $user_data['nome_filial']; // Mantém o nome da filial se não enviado

    // Validar campos obrigatórios
    if (empty($username) || empty($tipo_usuario) || empty($nome_completo_usuario) /* adicione outras validações se necessário */) {
        $mensagem_erro_popup = "Campos como Nome de Usuário, Tipo e Nome Completo são obrigatórios.";
        // Preencher $user_data com os valores do POST para repopular o formulário
        foreach($_POST as $key => $value){ $user_data[$key] = trim($value); }
    } else {
        // Verificar se o username (email) já existe para outro usuário
        $sql_check_email = "SELECT id FROM usuarios WHERE username = ? AND id != ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("si", $username, $user_id_to_edit);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();

        if ($stmt_check_email->num_rows > 0) {
            $mensagem_erro_popup = "O email '" . htmlspecialchars($username) . "' já está cadastrado para outro usuário.";
            foreach($_POST as $key => $value){ $user_data[$key] = trim($value); }
        } else {
            $stmt_check_email->close(); // Fechar antes de preparar novo statement

            $eh_filial_value = ($tipo_usuario == 2) ? 1 : 0;
            $nome_filial_db = ($eh_filial_value == 1) ? $nome_filial_post : NULL;

            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                // SQL UPDATE MODIFICADO: Removida a coluna 'estado'
                $sql_update = "UPDATE usuarios SET username = ?, tipo_usuario = ?, cnpj = ?, responsavel = ?, endereco = ?, cep = ?, bairro = ?, cidade = ?, uf = ?, nome = ?, eh_filial = ?, nome_filial = ?, password = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                // BIND_PARAM MODIFICADO
                $stmt_update->bind_param("sissssssssissi", $username, $tipo_usuario, $cnpj, $responsavel, $endereco, $cep, $bairro, $cidade, $uf, $nome_completo_usuario, $eh_filial_value, $nome_filial_db, $password, $user_id_to_edit);
            } else {
                // SQL UPDATE MODIFICADO: Removida a coluna 'estado'
                $sql_update = "UPDATE usuarios SET username = ?, tipo_usuario = ?, cnpj = ?, responsavel = ?, endereco = ?, cep = ?, bairro = ?, cidade = ?, uf = ?, nome = ?, eh_filial = ?, nome_filial = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                // BIND_PARAM MODIFICADO
                $stmt_update->bind_param("sissssssssisi", $username, $tipo_usuario, $cnpj, $responsavel, $endereco, $cep, $bairro, $cidade, $uf, $nome_completo_usuario, $eh_filial_value, $nome_filial_db, $user_id_to_edit);
            }

            if ($stmt_update->execute()) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Usuário atualizado com sucesso!'];
                header("Location: usuarios.php");
                exit();
            } else {
                $mensagem_erro_popup = "Erro ao atualizar usuário: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
        // $stmt_check_email->close(); // Movido para dentro do else para evitar fechar duas vezes
    }
}
// A conexão é fechada no final do script HTML/PHP
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Dashboard</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <?php
        if (file_exists(__DIR__ . '/../../includes/header_com_menu.php')) {
            include __DIR__ . '/../../includes/header_com_menu.php';
        }
    ?>
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/editar_usuario.css"> </head>
<body class="hcm-body-fixed-header">

    <div class="hcm-main-content">
        <div class="container py-4">
            <div class="row mb-4">
                 <div class="col-12">
                    <div class="painel-titulo">
                        <i class="bi bi-pencil-square me-2"></i>Editar Usuário
                    </div>
                     <hr class="barrinha"></hr>
                </div>
            </div>
            
            <?php if (!empty($mensagem_erro_popup)): ?>
                <div class="row mb-3">
                    <div class="col-md-8 mx-auto">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($mensagem_erro_popup); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card estatistica-card">
                        <div class="card-body p-4">
                            <?php if ($user_data): ?>
                            <form method="POST" action="editar_usuario.php?id=<?php echo htmlspecialchars($user_id_to_edit); ?>">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="form-label" for="username">Nome de Usuário (Email)</label>
                                        <input type="email" id="username" name="username" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                                    </div>
                                     <div class="form-group col-md-6">
                                        <label class="form-label" for="nome">Nome Completo</label>
                                        <input type="text" id="nome" name="nome" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['nome']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="form-label" for="tipo_usuario">Tipo de Usuário</label>
                                        <select name="tipo_usuario" id="tipo_usuario" class="form-control" required <?php if ($user_data['id'] == 1 && $_SESSION['user_id'] != 1) echo 'disabled'; /* Impede edição do tipo do admin principal por outros admins */ ?>>
                                            <option value="1" <?php echo ($user_data['tipo_usuario'] == 1) ? 'selected' : ''; ?>>Matriz (Admin)</option>
                                            <option value="2" <?php echo ($user_data['tipo_usuario'] == 2) ? 'selected' : ''; ?>>Filial (Loja)</option>
                                            </select>
                                        <?php if ($user_data['id'] == 1 && $_SESSION['user_id'] != 1): ?>
                                            <input type="hidden" name="tipo_usuario" value="<?php echo htmlspecialchars($user_data['tipo_usuario']); ?>" />
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group col-md-6" id="nome-filial-group" style="<?php echo ($user_data['tipo_usuario'] == 2) ? '' : 'display: none;'; ?>">
                                        <label for="nome_filial" class="form-label">Nome da Filial (Loja):</label>
                                        <input type="text" class="form-control" id="nome_filial" name="nome_filial" placeholder="Ex: Loja Zamp Centro SP" value="<?php echo htmlspecialchars($user_data['nome_filial'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="form-label" for="cnpj">CNPJ</label>
                                        <input type="text" id="cnpj" name="cnpj" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['cnpj']); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="form-label" for="responsavel">Nome do Responsável (Contato)</label>
                                        <input type="text" id="responsavel" name="responsavel" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['responsavel']); ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                     <div class="form-group col-md-4">
                                        <label class="form-label" for="cep">CEP</label>
                                        <input type="text" id="cep" name="cep" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['cep']); ?>">
                                    </div>
                                    <div class="form-group col-md-8">
                                        <label class="form-label" for="endereco">Endereço</label>
                                        <input type="text" id="endereco" name="endereco" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['endereco']); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label class="form-label" for="bairro">Bairro</label>
                                        <input type="text" id="bairro" name="bairro" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['bairro']); ?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label class="form-label" for="cidade">Cidade</label>
                                        <input type="text" id="cidade" name="cidade" class="form-control" 
                                               value="<?php echo htmlspecialchars($user_data['cidade']); ?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label class="form-label" for="uf">UF</label>
                                        <input type="text" id="uf" name="uf" class="form-control" maxlength="2"
                                               value="<?php echo htmlspecialchars($user_data['uf']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="password">Nova Senha (opcional)</label>
                                    <input type="password" id="password" name="password" class="form-control" 
                                           placeholder="Deixe em branco para manter a senha atual">
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-success btn-block">
                                        <i class="bi bi-check2-circle me-1"></i>Atualizar Usuário
                                    </button>
                                     <a href="usuarios.php" class="btn btn-outline-secondary btn-block mt-2">
                                        <i class="bi bi-arrow-left me-1"></i>Voltar para Usuários
                                    </a>
                                </div>
                            </form>
                            <?php else: ?>
                                <div class="alert alert-warning">Não foi possível carregar os dados do usuário para edição.</div>
                                 <a href="usuarios.php" class="btn btn-outline-secondary btn-block mt-2">
                                    <i class="bi bi-arrow-left me-1"></i>Voltar para Usuários
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sistema-info text-center mt-3">
                Sistema de Gerenciamento SouthRock © <?php echo date('Y'); ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#cnpj').mask('00.000.000/0000-00');
            $('#cep').mask('00000-000');

             function limpa_formulário_cep() {
                $("#endereco").val("");
                $("#bairro").val("");
                $("#cidade").val("");
                // UF não é limpa aqui, pois pode ser que o usuário queira manter a UF digitada se o CEP não for encontrado ou for inválido
            }

            $('#cep').blur(function() {
                var cep = $(this).val().replace(/\D/g, '');
                if (cep.length === 8) {
                    $("#endereco").val("...");
                    $("#bairro").val("...");
                    $("#cidade").val("...");
                    $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(data) {
                        if (!("erro" in data)) {
                            $("#endereco").val(data.logradouro);
                            $("#bairro").val(data.bairro);
                            $("#cidade").val(data.localidade);
                            $("#uf").val(data.uf); // Atualiza UF com base no CEP
                        } else {
                            limpa_formulário_cep();
                            // Não mostra alerta aqui, pois pode ser um CEP de uma localidade sem consulta ou que o usuário quer preencher manualmente
                        }
                    }).fail(function() {
                        limpa_formulário_cep();
                        // Não mostra alerta aqui para não ser intrusivo
                    });
                }
            });
            
            $('#tipo_usuario').change(function() {
                if ($(this).val() == '2') { 
                    $('#nome-filial-group').slideDown(); 
                    $('#nome_filial').prop('required', true); 
                } else {
                    $('#nome-filial-group').slideUp(); 
                    $('#nome_filial').prop('required', false); 
                    // Não limpa o valor de nome_filial aqui, caso o usuário mude de ideia e volte para tipo 2
                }
            });
            // Dispara o evento change no carregamento para ajustar a visibilidade do campo nome_filial
            $('#tipo_usuario').trigger('change');


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
if (isset($conn)) {
    $conn->close();
}
?>