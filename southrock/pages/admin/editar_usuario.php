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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/editar_usuario.css">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">Editar Usuário</h2>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nome de Usuário (Email)</label>
                    <input type="email" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo de Usuário</label>
                    <select name="tipo_usuario" class="form-select" required>
                        <option value="1" <?php echo $user['tipo_usuario'] == 1 ? 'selected' : ''; ?>>Matriz</option>
                        <option value="2" <?php echo $user['tipo_usuario'] == 2 ? 'selected' : ''; ?>>Loja</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">CNPJ</label>
                    <input type="text" name="cnpj" class="form-control" 
                           value="<?php echo htmlspecialchars($user['cnpj']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nome do Responsável</label>
                    <input type="text" name="responsavel" class="form-control" 
                           value="<?php echo htmlspecialchars($user['responsavel']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Endereço</label>
                    <input type="text" name="endereco" class="form-control" 
                           value="<?php echo htmlspecialchars($user['endereco']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">CEP</label>
                    <input type="text" name="cep" class="form-control" 
                           value="<?php echo htmlspecialchars($user['cep']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Bairro</label>
                    <input type="text" name="bairro" class="form-control" 
                           value="<?php echo htmlspecialchars($user['bairro']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cidade</label>
                    <input type="text" name="cidade" class="form-control" 
                           value="<?php echo htmlspecialchars($user['cidade']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">UF</label>
                    <input type="text" name="uf" class="form-control" 
                           value="<?php echo htmlspecialchars($user['uf']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nova Senha (opcional)</label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Deixe em branco para manter a senha atual">
                </div>

                <button type="submit" class="btn btn-primary w-100">Atualizar Usuário</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>