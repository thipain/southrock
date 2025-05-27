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

$query = "SELECT status FROM pedidos WHERE id = ?";
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
$new_status = '';
$update_sql = '';
$bind_types = '';
$bind_params = [];
$update_columns = []; 

switch ($current_status) {
    case 'novo':
    case 'aprovado': 
        $new_status = 'processo';
        $update_columns[] = "status = ?";
        $update_columns[] = "data_processamento = ?";
        $bind_types = "ss"; 
        $bind_params = [&$new_status, date('Y-m-d H:i:s')];
        break;
    case 'processo':
        $new_status = 'finalizado';
        $update_columns[] = "status = ?";
        $update_columns[] = "data_finalizacao = ?"; 
        $bind_types = "ss"; 
        $bind_params = [&$new_status, date('Y-m-d H:i:s')];
        break;
    case 'rejeitado':
    case 'cancelado':
    case 'finalizado':
        $_SESSION['error_message'] = "O pedido não pode ser processado a partir do status '" . $current_status . "'.";
        header("Location: detalhes_pedido.php?id=" . $pedido_id);
        exit();
    default:
        $_SESSION['error_message'] = "Status do pedido desconhecido ou inválido: '" . $current_status . "'.";
        header("Location: detalhes_pedido.php?id=" . $pedido_id);
        exit();
}

if (!empty($update_columns)) {
    $update_sql = "UPDATE pedidos SET " . implode(", ", $update_columns) . " WHERE id = ?";
    $bind_types .= "i"; 
    $bind_params[] = &$pedido_id; 
} else {
    $_SESSION['error_message'] = "Nenhuma ação de atualização definida para o status atual.";
    header("Location: detalhes_pedido.php?id=" . $pedido_id);
    exit();
}

$stmt_update = $conn->prepare($update_sql);

call_user_func_array([$stmt_update, 'bind_param'], array_merge([$bind_types], $bind_params));

if ($stmt_update->execute()) {
    $_SESSION['success_message'] = "Status do pedido atualizado para '" . $new_status . "'.";
} else {
    $_SESSION['error_message'] = "Erro ao atualizar o status do pedido: " . $conn->error;
}

$stmt->close();
if (isset($stmt_update)) $stmt_update->close();

$conn->close();

header("Location: detalhes_pedido.php?id=" . $pedido_id);
exit();
?>