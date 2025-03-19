<?php
session_start();
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT tipo_usuario FROM usuarios WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($tipo_usuario);
        $stmt->fetch();

        $_SESSION['username'] = $user;
        if ($tipo_usuario == 1) {
            header("Location: pages/dashboard.php");
        } else {
            header("Location: pages/requisicao_pedidos.php");
        }
    } else {
        echo "Usuário ou senha inválidos.";
    }

    $stmt->close();
}

$conn->close();
?>