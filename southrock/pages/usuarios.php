<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include '../includes/db.php';

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

$sql = "SELECT * FROM usuarios";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .button-danger {
            background-color: #f44336;
        }
        .form-container {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Gerenciar Usuários</h1>

    <div class="form-container">
        <a href="cadastro_usuario.php" class="button">Novo Usuário</a>
        <a href="dashboard.php" class="button">Voltar</a>
    </div>

    <h2>Lista de Usuários</h2>
    <table>
        <tr>
            <th>Nome de Usuário</th>
            <th>CNPJ</th>
            <th>Responsável</th>
            <th>Endereço</th>
            <th>Tipo de Usuário</th>
            <th>Ações</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['username']; ?></td>
            <td><?php echo $row['cnpj']; ?></td>
            <td><?php echo $row['responsavel']; ?></td>
            <td><?php echo $row['endereco']; ?></td>
            <td><?php echo $row['tipo_usuario']; ?></td>
            <td>
                <a href="editar_usuario.php?id=<?php echo $row['id']; ?>" class="button">Editar</a>
                <a href="?delete=<?php echo $row['id']; ?>" class="button button-danger" onclick="return confirm('Tem certeza que deseja remover este usuário?');">Excluir</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php
$conn->close();
?>