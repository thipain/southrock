<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include '../../includes/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Recuperar os dados do usuário
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
    } else {
        echo "<script>alert('Usuário não encontrado.'); window.location.href='usuarios.php';</script>";
        exit();
    }
}

// Atualizar os dados do usuário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $tipo_usuario = $_POST['tipo_usuario'];
    $cnpj = $_POST['cnpj'];
    $responsavel = $_POST['responsavel'];
    $endereco = $_POST['endereco'];
    $cep = $_POST['cep'];
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $uf = $_POST['uf'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET username = ?, tipo_usuario = ?, cnpj = ?, responsavel = ?, endereco = ?, cep = ?, bairro = ?, cidade = ?, uf = ?, password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssssssi", $username, $tipo_usuario, $cnpj, $responsavel, $endereco, $cep, $bairro, $cidade, $uf, $password, $id);
    } else {
        $sql = "UPDATE usuarios SET username = ?, tipo_usuario = ?, cnpj = ?, responsavel = ?, endereco = ?, cep = ?, bairro = ?, cidade = ?, uf = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisssssssi", $username, $tipo_usuario, $cnpj, $responsavel, $endereco, $cep, $bairro, $cidade, $uf, $id);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Usuário atualizado com sucesso!'); window.location.href='usuarios.php';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar usuário: " . $stmt->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Dashboard</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/editar_usuario.css">
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
            <h1><i class="bi bi-pencil-square me-2"></i>Editar Usuário</h1>
        </div>
        
        <div class="main-content">
            <div class="form-container">
                <h2>Editar Dados do Usuário</h2>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Nome de Usuário (Email)</label>
                        <input type="email" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipo de Usuário</label>
                        <select name="tipo_usuario" class="form-select" required>
                            <option value="1" <?php echo $user['tipo_usuario'] == 1 ? 'selected' : ''; ?>>Matriz</option>
                            <option value="2" <?php echo $user['tipo_usuario'] == 2 ? 'selected' : ''; ?>>Loja</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">CNPJ</label>
                        <input type="text" name="cnpj" class="form-control" 
                               value="<?php echo htmlspecialchars($user['cnpj']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nome do Responsável</label>
                        <input type="text" name="responsavel" class="form-control" 
                               value="<?php echo htmlspecialchars($user['responsavel']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Endereço</label>
                        <input type="text" name="endereco" class="form-control" 
                               value="<?php echo htmlspecialchars($user['endereco']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">CEP</label>
                        <input type="text" name="cep" class="form-control" 
                               value="<?php echo htmlspecialchars($user['cep']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Bairro</label>
                        <input type="text" name="bairro" class="form-control" 
                               value="<?php echo htmlspecialchars($user['bairro']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="cidade" class="form-control" 
                               value="<?php echo htmlspecialchars($user['cidade']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">UF</label>
                        <input type="text" name="uf" class="form-control" 
                               value="<?php echo htmlspecialchars($user['uf']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nova Senha (opcional)</label>
                        <input type="password" name="password" class="form-control" 
                               placeholder="Deixe em branco para manter a senha atual">
                    </div>

                    <button type="submit" class="btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Atualizar Usuário
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Script para gerenciar estado ativo do menu
        document.addEventListener('DOMContentLoaded', function() {
            const currentLocation = window.location.pathname;
            const menuItems = document.querySelectorAll('.sidebar a');
            
            menuItems.forEach(item => {
                const itemPath = item.getAttribute('href');
                if (currentLocation.includes(itemPath) && itemPath !== 'dashboard.php') {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>