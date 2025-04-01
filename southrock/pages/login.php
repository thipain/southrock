<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Preparar a consulta para buscar o usuário
    $sql = "SELECT * FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verificar a senha usando password_verify()
        if (password_verify($password, $user['password'])) {
            // Senha correta, iniciar sessão
            $_SESSION['username'] = $username;
            $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
            
            // Redirecionar baseado no tipo de usuário
            if ($user['tipo_usuario'] == 1) {
                // Administrador
                header("Location: admin/dashboard.php");
                exit();
            } elseif ($user['tipo_usuario'] == 2) {
                // Loja
                header("Location: lojas/requisicao_pedidos.php");
                exit();
            } else {
                // Tipo de usuário não reconhecido
                echo "<script>alert('Tipo de usuário não autorizado.'); window.location.href='index.php';</script>";
            }
        } else {
            // Senha incorreta
            echo "<script>alert('Usuário ou senha incorretos!'); window.location.href='index.php';</script>";
        }
    } else {
        // Usuário não encontrado
        echo "<script>alert('Usuário não encontrado!'); window.location.href='index.php';</script>";
    }

    $stmt->close();
}
$conn->close();
?>