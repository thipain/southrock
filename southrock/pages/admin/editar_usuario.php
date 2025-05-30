<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit();
}

include '../../includes/db.php';

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
// $mensagem_sucesso_popup = ''; // Sucesso será tratado com flash message

if (isset($_GET['id'])) {
    $user_id_to_edit = $_GET['id'];

    $sql_get_user = "SELECT * FROM usuarios WHERE id = ?";
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
    $uf = trim($_POST['uf']);
    // A lógica para $eh_filial foi removida da atualização direta aqui

    if (empty($username) || empty($tipo_usuario) || empty($cnpj) || empty($responsavel) || empty($endereco) || empty($cep) || empty($bairro) || empty($cidade) || empty($uf)) {
        $mensagem_erro_popup = "Todos os campos obrigatórios devem ser preenchidos.";
        $user_data['username'] = $username;
        $user_data['tipo_usuario'] = $tipo_usuario;
        $user_data['cnpj'] = $cnpj;
        $user_data['responsavel'] = $responsavel;
        $user_data['endereco'] = $endereco;
        $user_data['cep'] = $cep;
        $user_data['bairro'] = $bairro;
        $user_data['cidade'] = $cidade;
        $user_data['uf'] = $uf;
    } else {
        $sql_check_email = "SELECT id FROM usuarios WHERE username = ? AND id != ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("si", $username, $user_id_to_edit);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();

        if ($stmt_check_email->num_rows > 0) {
            $mensagem_erro_popup = "O email '" . htmlspecialchars($username) . "' já está cadastrado para outro usuário.";
        } else {
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql_update = "UPDATE usuarios SET username = ?, tipo_usuario = ?, cnpj = ?, responsavel = ?, endereco = ?, cep = ?, bairro = ?, cidade = ?, uf = ?, password = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("sissssssssi", $username, $tipo_usuario, $cnpj, $responsavel, $endereco, $cep, $bairro, $cidade, $uf, $password, $user_id_to_edit);
            } else {
                $sql_update = "UPDATE usuarios SET username = ?, tipo_usuario = ?, cnpj = ?, responsavel = ?, endereco = ?, cep = ?, bairro = ?, cidade = ?, uf = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("sisssssssi", $username, $tipo_usuario, $cnpj, $responsavel, $endereco, $cep, $bairro, $cidade, $uf, $user_id_to_edit);
            }

            if ($stmt_update->execute()) {
                // Atualiza também o campo eh_filial baseado no tipo_usuario SE este campo existir na tabela
                // Esta é uma atualização separada e condicional para desacoplar da lógica principal de dados do formulário
                if (isset($tipo_usuario)) { // Garante que tipo_usuario foi submetido
                    $eh_filial_value = ($tipo_usuario == 2) ? 1 : 0; // 1 para filial, 0 para outros
                    // Verifica se a coluna eh_filial existe antes de tentar atualizá-la
                    $check_column_sql = "SHOW COLUMNS FROM usuarios LIKE 'eh_filial'";
                    $check_column_result = $conn->query($check_column_sql);
                    if ($check_column_result && $check_column_result->num_rows > 0) {
                        $sql_update_eh_filial = "UPDATE usuarios SET eh_filial = ? WHERE id = ?";
                        $stmt_update_eh_filial = $conn->prepare($sql_update_eh_filial);
                        $stmt_update_eh_filial->bind_param("ii", $eh_filial_value, $user_id_to_edit);
                        $stmt_update_eh_filial->execute();
                        $stmt_update_eh_filial->close();
                    }
                }
                
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Usuário atualizado com sucesso!'];
                header("Location: usuarios.php");
                exit();
            } else {
                $mensagem_erro_popup = "Erro ao atualizar usuário: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
        $stmt_check_email->close();
    }
}
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <?php
        if (file_exists(__DIR__ . '/../../includes/header_com_menu.php')) {
            include __DIR__ . '/../../includes/header_com_menu.php';
        }
    ?>
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/editar_usuario.css">
</head>
<body class="hcm-body-fixed-header">

    <div class="hcm-main-content">
        <div class="container py-4">
            <div class="header-pagina">
                <h1><i class="bi bi-pencil-square me-2"></i>Editar Usuário</h1>
            </div>
            
            <div class="main-content-form">
                <?php if (!empty($mensagem_erro_popup)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($mensagem_erro_popup); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>

                <div class="form-container-editar">
                    <h2>Editar Dados do Usuário</h2>
                    <?php if ($user_data): ?>
                    <form method="POST" action="editar_usuario.php?id=<?php echo htmlspecialchars($user_id_to_edit); ?>">
                        <div class="form-group">
                            <label class="form-label">Nome de Usuário (Email)</label>
                            <input type="email" name="username" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tipo de Usuário</label>
                            <select name="tipo_usuario" class="form-control" required <?php if ($user_data['tipo_usuario'] == 1 && isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] != 1) echo 'disabled'; ?>>
                                <option value="1" <?php echo ($user_data['tipo_usuario'] == 1) ? 'selected' : ''; ?>>Administrador Matriz</option>
                                <option value="2" <?php echo ($user_data['tipo_usuario'] == 2) ? 'selected' : ''; ?>>Filial (Loja)</option>
                                <option value="3" <?php echo ($user_data['tipo_usuario'] == 3) ? 'selected' : ''; ?>>Visualizador</option>
                            </select>
                             <?php if ($user_data['tipo_usuario'] == 1 && isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] != 1): ?>
                                <input type="hidden" name="tipo_usuario" value="<?php echo htmlspecialchars($user_data['tipo_usuario']); ?>" />
                                <small class="form-text text-muted">Apenas o administrador principal pode alterar o tipo de outro administrador principal.</small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">CNPJ</label>
                            <input type="text" name="cnpj" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_data['cnpj']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nome do Responsável</label>
                            <input type="text" name="responsavel" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_data['responsavel']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Endereço</label>
                            <input type="text" name="endereco" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_data['endereco']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">CEP</label>
                            <input type="text" name="cep" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_data['cep']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Bairro</label>
                            <input type="text" name="bairro" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_data['bairro']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Cidade</label>
                            <input type="text" name="cidade" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_data['cidade']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">UF</label>
                            <input type="text" name="uf" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_data['uf']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nova Senha (opcional)</label>
                            <input type="password" name="password" class="form-control" 
                                   placeholder="Deixe em branco para manter a senha atual">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="bi bi-check2-circle me-1"></i>Atualizar Usuário
                        </button>
                         <a href="usuarios.php" class="btn btn-outline-secondary btn-block mt-2">
                            <i class="bi bi-arrow-left me-1"></i>Voltar para Usuários
                        </a>
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
    
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para fechar alertas de erro automaticamente
        document.addEventListener('DOMContentLoaded', function() {
            var alertError = document.querySelector('.alert-danger');
            if (alertError) {
                setTimeout(function() {
                    // Para Bootstrap 4 (usando jQuery se o data-dismiss estiver lá)
                    if (typeof $ !== 'undefined' && $(alertError).data('dismiss') === 'alert') {
                        $(alertError).alert('close');
                    } 
                    // Para Bootstrap 5 (se houver botão com data-bs-dismiss)
                    else if (typeof bootstrap !== 'undefined' && bootstrap.Alert && alertError.querySelector('[data-bs-dismiss="alert"]')) {
                         var bsAlert = bootstrap.Alert.getOrCreateInstance(alertError);
                         bsAlert.close();
                    } 
                    // Fallback simples
                    else {
                        alertError.style.display = 'none';
                    }
                }, 5000); // Fecha após 5 segundos
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