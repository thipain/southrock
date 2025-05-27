<?php

include '../../includes/db.php';


$username = 'admin';
$password = '123456'; // Sua senha desejada
$tipo_usuario = 1; // Tipo de usuário administrador


$hashed_password = password_hash($password, PASSWORD_DEFAULT);


$sql = "UPDATE usuarios SET password = ? WHERE username = ? AND tipo_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $hashed_password, $username, $tipo_usuario);


if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "Senha do admin atualizada com sucesso!";
    } else {
        echo "Nenhum usuário admin encontrado para atualizar.";
    }
} else {
    echo "Erro ao atualizar a senha: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>