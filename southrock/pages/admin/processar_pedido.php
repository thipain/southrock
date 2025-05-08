<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../includes/db.php';

// Verificar se o ID do pedido foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pedidos.php");
    exit();
}

$pedido_id = intval($_GET['id']);

// Verificar se o pedido existe e está no status "novo"
$query = "SELECT status FROM pedidos WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Pedido não encontrado
    $_SESSION['error'] = "Pedido não encontrado.";
    header("Location: pedidos.php");
    exit();
}

$pedido = $result->fetch_assoc();

if ($pedido['status'] !== 'novo') {
    // Pedido já está sendo processado ou finalizado
    $_SESSION['error'] = "Este pedido não pode ser processado pois não está no status 'novo'.";
    header("Location: detalhes_pedido.php?id=" . $pedido_id);
    exit();
}

// Atualizar o status do pedido para "processo" e registrar a data de processamento
$data_processamento = date('Y-m-d H:i:s');
$query_update = "UPDATE pedidos SET status = 'processo', data_processamento = ? WHERE id = ?";
$stmt_update = $conn->prepare($query_update);
$stmt_update->bind_param("si", $data_processamento, $pedido_id);

// Registrar o usuário que processou o pedido
$usuario_id = $_SESSION['user_id'] ?? null;
if ($usuario_id) {
    $query_log = "INSERT INTO pedido_logs (pedido_id, usuario_id, acao, data) VALUES (?, ?, 'processado', NOW())";
    $stmt_log = $conn->prepare($query_log);
    $stmt_log->bind_param("ii", $pedido_id, $usuario_id);
    $stmt_log->execute();
}

if ($stmt_update->execute()) {
    // Sucesso na atualização
    $_SESSION['success'] = "Pedido #" . $pedido_id . " foi colocado em processamento com sucesso!";
} else {
    // Erro na atualização
    $_SESSION['error'] = "Erro ao processar o pedido: " . $conn->error;
}

// Redirecionar para a página de detalhes do pedido
header("Location: detalhes_pedido.php?id=" . $pedido_id);
exit();
?>