<?php
session_start();
include '../includes/db.php'; // Certifique-se de que este caminho está correto

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
            // Senha correta, iniciar sessão
            $_SESSION['user_id'] = $user['id']; // <-- ADICIONADO: ID do usuário logado
            $_SESSION['username'] = $user['username'];
            $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
            
            // ADICIONADO: Lógica para definir o ID da filial na sessão
            if ($user['eh_filial'] == 1) { // Se o usuário é uma filial (tipo_usuario 2)
                $_SESSION['branch_id'] = $user['id']; // O ID da filial é o próprio ID do usuário
            } elseif ($user['tipo_usuario'] == 1) { // Se o usuário é administrador (tipo_usuario 1 - Matriz)
                // Assumindo que a matriz tem um ID específico, por exemplo, 1
                // Você pode ajustar isso se sua lógica de matriz for diferente
                $_SESSION['branch_id'] = 1; // ID da matriz
            } else {
                // Caso o tipo de usuário não seja uma filial nem a matriz principal,
                // você pode definir um valor padrão ou null, dependendo da sua regra.
                // Para doação, é crucial que haja uma filial de origem.
                $_SESSION['branch_id'] = null; // Ou um valor padrão se aplicável
            }

            // Redirecionar baseado no tipo de usuário
            if ($user['tipo_usuario'] == 1) {
                // Administrador
                header("Location: admin/dashboard.php");
                exit();
            } elseif ($user['tipo_usuario'] == 2) {
                // Loja
                header("Location: lojas/requisicao_pedidos.php"); // Ou para 'doar_pedidos.php' se a loja deve ir direto para lá
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