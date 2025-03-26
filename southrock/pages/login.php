<?php
session_start();
include '../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Busque a senha e o tipo de usuário no banco de dados
    $stmt = $conn->prepare("SELECT password, tipo_usuario FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($stored_password, $tipo_usuario);
        $stmt->fetch();

        // Verifique se a senha fornecida corresponde à senha armazenada
        if ($pass === $stored_password) {
            // Senha correta
            $_SESSION['username'] = $user;
            if ($tipo_usuario == 1) {
                header("Location: ../pages/dashboard.php");
            } else {
                header("Location: ../pages/requisicao_pedidos.php");
            }
            exit(); // Adicione exit após o redirecionamento
        } else {
            echo "Usuário ou senha inválidos.";
        }
    } else {
        echo "Usuário ou senha inválidos.";
    }

    $stmt->close();
}

$conn->close();
?>