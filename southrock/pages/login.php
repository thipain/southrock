<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT id, username, password, tipo_usuario, eh_filial FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
    
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
            
    
            if ($user['eh_filial'] == 1) { 
                $_SESSION['branch_id'] = $user['id']; 
            } elseif ($user['tipo_usuario'] == 1) { 
           
                $_SESSION['branch_id'] = 1; // ID da matriz
            } else {
    
                $_SESSION['branch_id'] = null; // Ou um valor padrão se aplicável
            }

          
            if ($user['tipo_usuario'] == 1) {
                
                header("Location: admin/dashboard.php");
                exit();
            } elseif ($user['tipo_usuario'] == 2) {
            
                header("Location: lojas/requisicao_pedidos.php"); // Ou para 'doar_pedidos.php' se a loja deve ir direto para lá
                exit();
            } else {
              
                echo "<script>alert('Tipo de usuário não autorizado.'); window.location.href='index.php';</script>";
            }
        } else {
        
            echo "<script>alert('Usuário ou senha incorretos!'); window.location.href='index.php';</script>";
        }
    } else {
      
        echo "<script>alert('Usuário não encontrado!'); window.location.href='index.php';</script>";
    }

    $stmt->close();
}
$conn->close();
?>