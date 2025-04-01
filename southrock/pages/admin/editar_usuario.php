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

    // Verificar se a senha foi alterada
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET username = ?, tipo_usuario = ?, cnpj = ?, responsavel = ?, endereco = ?, password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssi", $username, $tipo_usuario, $cnpj, $responsavel, $endereco, $password, $id);
    } else {
        // Se a senha não foi alterada, mantenha a mesma
        $sql = "UPDATE usuarios SET username = ?, tipo_usuario = ?, cnpj = ?, responsavel = ?, endereco = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisssi", $username, $tipo_usuario, $cnpj, $responsavel, $endereco, $id);
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