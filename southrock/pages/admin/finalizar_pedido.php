<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['username']) || $_SESSION['tipo_usuario'] != 1) {
    header("Location: ../index.php"); 
    exit();
}

require_once '../../includes/db.php'; 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID do pedido inválido.";
    header("Location: pedidos.php"); 
    exit();
}

$pedido_id = intval($_GET['id']);

$query = "SELECT status, tipo_pedido FROM pedidos WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Pedido não encontrado.";
    header("Location: pedidos.php");
    exit();
}

$pedido = $result->fetch_assoc();
$current_status = $pedido['status'];
$tipo_pedido = $pedido['tipo_pedido'];
$new_status = 'finalizado'; 

if ($current_status !== 'processo') {
    $_SESSION['error_message'] = "O pedido não pode ser finalizado a partir do status '" . $current_status . "'.";
    header("Location: detalhes_pedido.php?id=" . $pedido_id);
    exit();
}

$data_finalizacao = date('Y-m-d H:i:s');
$query_update = "UPDATE pedidos SET status = ?, data_finalizacao = ? WHERE id = ?"; 
$stmt_update = $conn->prepare($query_update);
$stmt_update->bind_param("ssi", $new_status, $data_finalizacao, $pedido_id);

if ($stmt_update->execute()) {
    
    $_SESSION['success_message'] = "Pedido finalizado com sucesso!";
} else {
    $_SESSION['error_message'] = "Erro ao finalizar o pedido: " . $conn->error;
}

$stmt->close();
if (isset($stmt_update)) $stmt_update->close();

$conn->close();

header("Location: detalhes_pedido.php?id=" . $pedido_id);
exit();
?>