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

// Verificar se o pedido existe e está no status "processo"
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

if ($pedido['status'] !== 'processo') {
    // Pedido não está em processamento
    $_SESSION['error'] = "Este pedido não pode ser finalizado pois não está no status 'em processo'.";
    header("Location: detalhes_pedido.php?id=" . $pedido_id);
    exit();
}

// Atualizar o status do pedido para "finalizado" e registrar a data de finalização
$data_finalizacao = date('Y-m-d H:i:s');
$query_update = "UPDATE pedidos SET status = 'finalizado', data_finalizacao = ? WHERE id = ?";
$stmt_update = $conn->prepare($query_update);
$stmt_update->bind_param("si", $data_finalizacao, $pedido_id);

// Registrar o usuário que finalizou o pedido
$usuario_id = $_SESSION['user_id'] ?? null;
if ($usuario_id) {
    $query_log = "INSERT INTO pedido_logs (pedido_id, usuario_id, acao, data) VALUES (?, ?, 'finalizado', NOW())";
    $stmt_log = $conn->prepare($query_log);
    $stmt_log->bind_param("ii", $pedido_id, $usuario_id);
    $stmt_log->execute();
}

if ($stmt_update->execute()) {
    // Sucesso na atualização
    $_SESSION['success'] = "Pedido #" . $pedido_id . " foi finalizado com sucesso!";
    
    // Se for um pedido de requisição, atualizar o estoque (exemplo simplificado)
    $query_tipo = "SELECT tipo_pedido FROM pedidos WHERE id = ?";
    $stmt_tipo = $conn->prepare($query_tipo);
    $stmt_tipo->bind_param("i", $pedido_id);
    $stmt_tipo->execute();
    $result_tipo = $stmt_tipo->get_result();
    $pedido_info = $result_tipo->fetch_assoc();
    

    } elseif ($pedido_info['tipo_pedido'] == 'devolucao' || $pedido_info['tipo_pedido'] == 'troca') {
        // Lógica para aumentar estoque em caso de devolução ou troca
        $query_itens = "SELECT sku, quantidade FROM pedido_itens WHERE pedido_id = ?";
        $stmt_itens = $conn->prepare($query_itens);
        $stmt_itens->bind_param("i", $pedido_id);
        $stmt_itens->execute();
        $result_itens = $stmt_itens->get_result();
        
        // Atualizar estoque para cada item devolvido ou trocado
        while ($item = $result_itens->fetch_assoc()) {
            $query_estoque = "UPDATE produtos SET quantidade_estoque = quantidade_estoque + ? WHERE sku = ?";
            $stmt_estoque = $conn->prepare($query_estoque);
            $stmt_estoque->bind_param("is", $item['quantidade'], $item['sku']);
            $stmt_estoque->execute();
        }
    }
    // Pedidos do tipo doação não afetam o estoque
    
 else {
    // Erro na atualização
    $_SESSION['error'] = "Erro ao finalizar o pedido: " . $conn->error;
}

// Redirecionar para a página de detalhes do pedido
header("Location: detalhes_pedido.php?id=" . $pedido_id);
exit();
?>