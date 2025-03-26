<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include '../includes/db.php';

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
    $tipo_usuario = $_POST['tipo_usuario']; // Agora pegamos o tipo de usuário do formulário

    $sql = "UPDATE usuarios SET username = ?, tipo_usuario = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $username, $tipo_usuario, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Usuário atualizado com sucesso!'); window.location.href='usuarios.php';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar usuário.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .form-container {
            max-width: 400px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .form-container input, .form-container select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        .button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>Editar Usuário</h1>

    <div class="form-container">
        <form method="POST">
            <input type="text" name="username" placeholder="Nome de Usuário" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            <select name="tipo_usuario" required>
                <option value="1" <?php if ($user['tipo_usuario'] == 1) echo 'selected'; ?>>Matriz</option>
                <option value="2" <?php if ($user['tipo_usuario'] == 2) echo 'selected'; ?>>Loja</option>
            </select>
            <button type="submit" class="button">Atualizar Usuário</button>
        </form>
    </div>
</body>
</html>

<?php
$conn->close();
?>