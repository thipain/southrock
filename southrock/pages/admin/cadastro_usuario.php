<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; 
    
    // Hash da senha usando PASSWORD_DEFAULT (bcrypt)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $tipo_usuario = 2; // Definindo tipo de usuário como loja
    $cnpj = $_POST['cnpj'];
    $responsavel = $_POST['responsavel'];
    $endereco = $_POST['endereco'];
    $cep = $_POST['cep'];
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $uf = $_POST['uf'];

    // Prepare a consulta SQL para inserir o usuário
    $sql = "INSERT INTO usuarios (username, password, tipo_usuario, cnpj, responsavel, endereco, cep, bairro, cidade, uf) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisssssss", $username, $hashed_password, $tipo_usuario, $cnpj, $responsavel, $endereco, $cep, $bairro, $cidade, $uf);


    // Execute a consulta e verifique se foi bem-sucedida
    if ($stmt->execute()) {
        echo "<script>alert('Usuário adicionado com sucesso!'); window.location.href='usuarios.php';</script>";
    } else {
        echo "<script>alert('Erro ao adicionar usuário.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Usuário - Dashboard</title>
    <link rel="stylesheet" href="../../css/cadastro_usuario.css">
</head>
<body>
    <h1>Cadastrar Novo Usuário</h1>

    <div class="form-container">
        <form method="POST">
            <input type="text" name="username" placeholder="usuario@starbucks.com.br" required>
            <input type="password" name="password" placeholder="Senha" required>
            <input type="text" name="cnpj" placeholder="CNPJ" required>
            <input type="text" name="responsavel" placeholder="Nome do Responsável" required>
            <input type="text" name="endereco" placeholder="Endereço" required>
            <input type="text" name="cep" placeholder="CEP" required>
            <input type="text" name="bairro" placeholder="Bairro" required>
            <input type="text" name="cidade" placeholder="Cidade" required>
            <input type="text" name="uf" placeholder="UF" required>
            <button type="submit" class="button">Adicionar Usuário</button>
        </form>
    </div>
</body>
</html>

<?php
$conn->close();
?>