<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../includes/db.php';

$loggedInUserId = null;
$loggedInUserType = null;

$stmtUser = $conn->prepare("SELECT id, tipo_usuario FROM usuarios WHERE username = ?");
$stmtUser->bind_param("s", $_SESSION['username']);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
if ($currentUserData = $resultUser->fetch_assoc()) {
    $loggedInUserId = $currentUserData['id'];
    $loggedInUserType = $currentUserData['tipo_usuario'];
}
$stmtUser->close();

if ($loggedInUserId === null || $loggedInUserType != 2) { 
    $_SESSION['error_message'] = "Acesso não autorizado ou tipo de usuário inválido.";
    header("Location: historico.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Método de requisição inválido.";
    header("Location: historico.php");
    exit();
}

$original_pedido_id = filter_input(INPUT_POST, 'original_pedido_id', FILTER_VALIDATE_INT);
$return_destination_id = filter_input(INPUT_POST, 'return_destination_id', FILTER_VALIDATE_INT);
$selected_items_data = $_POST['items'] ?? [];
$motivo_devolucao = trim($_POST['motivo_devolucao'] ?? '');


if (!$original_pedido_id || !$return_destination_id) {
    $_SESSION['error_message'] = "Dados do pedido original ou destino da devolução ausentes.";
    $redirect_url = $original_pedido_id ? "detalhes_pedido_loja.php?id=" . $original_pedido_id : "historico.php";
    header("Location: " . $redirect_url);
    exit();
}


$items_to_return = [];
if (empty($selected_items_data)) {
    $_SESSION['error_message'] = "Nenhum item foi selecionado para devolução.";
    header("Location: detalhes_pedido_loja.php?id=" . $original_pedido_id);
    exit();
}

foreach ($selected_items_data as $item_key => $item_data) {
    if (isset($item_data['selected'])) { 
        $sku = filter_var($item_data['sku'], FILTER_VALIDATE_INT); 
        $quantity_to_return = filter_var($item_data['quantity_to_return'], FILTER_VALIDATE_INT);
        $original_quantity = filter_var($item_data['original_quantity'], FILTER_VALIDATE_INT); 

        if (!$sku || $quantity_to_return === false || $quantity_to_return <= 0) {
            $_SESSION['error_message'] = "Dados inválidos para um dos itens selecionados (SKU do produto ou quantidade zerada/inválida).";
            header("Location: detalhes_pedido_loja.php?id=" . $original_pedido_id);
            exit();
        }
        if ($quantity_to_return > $original_quantity) {
            $_SESSION['error_message'] = "Quantidade a devolver (" . $quantity_to_return . ") excede a quantidade recebida (" . $original_quantity . ") para o produto SKU " . $sku . ".";
            header("Location: detalhes_pedido_loja.php?id=" . $original_pedido_id);
            exit();
        }
        $items_to_return[] = [
            'sku' => $sku, 
            'quantity' => $quantity_to_return
        ];
    }
}

if (empty($items_to_return)) {
    $_SESSION['error_message'] = "Nenhum item válido para devolução processado. Verifique as seleções e quantidades.";
    header("Location: detalhes_pedido_loja.php?id=" . $original_pedido_id);
    exit();
}

$conn->begin_transaction();

try {
    $sqlNewPedido = "INSERT INTO pedidos (filial_usuario_id, filial_destino_id, usuario_id, data, tipo_pedido, status, pedido_original_id, observacoes) 
                     VALUES (?, ?, ?, NOW(), 'devolucao', 'novo', ?, ?)";
    $stmtNewPedido = $conn->prepare($sqlNewPedido);
    if (!$stmtNewPedido) {
        throw new Exception("Erro ao preparar statement para novo pedido: " . $conn->error);
    }
    $stmtNewPedido->bind_param("iiiis", $loggedInUserId, $return_destination_id, $loggedInUserId, $original_pedido_id, $motivo_devolucao);
    $stmtNewPedido->execute();
    
    if ($stmtNewPedido->affected_rows <= 0) {
        throw new Exception("Falha ao criar o novo pedido de devolução: " . $stmtNewPedido->error);
    }
    $new_return_pedido_id = $conn->insert_id;
    
    $sqlNewPedidoItem = "INSERT INTO pedido_itens (pedido_id, sku, quantidade) VALUES (?, ?, ?)";
    $stmtNewPedidoItem = $conn->prepare($sqlNewPedidoItem);
    if (!$stmtNewPedidoItem) {
        throw new Exception("Erro ao preparar statement para itens do pedido: " . $conn->error);
    }

    foreach ($items_to_return as $item) {
        $stmtNewPedidoItem->bind_param("iii", $new_return_pedido_id, $item['sku'], $item['quantity']);
        $stmtNewPedidoItem->execute();
        if ($stmtNewPedidoItem->affected_rows <= 0) {
            throw new Exception("Falha ao adicionar item (SKU: {$item['sku']}) ao pedido de devolução: " . $stmtNewPedidoItem->error);
        }
    }
    
    $conn->commit();
    $_SESSION['success_message'] = "Pedido de devolução #" . $new_return_pedido_id . " criado com sucesso!";
    header("Location: historico.php"); 
    exit();

} catch (Exception $e) {
    $conn->rollback(); 
    $_SESSION['error_message'] = "Erro ao processar devolução: " . $e->getMessage();
    error_log("Erro em processar_devolucao.php: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    header("Location: detalhes_pedido_loja.php?id=" . $original_pedido_id);
    exit();
} finally {
    if (isset($stmtNewPedido)) $stmtNewPedido->close();
    if (isset($stmtNewPedidoItem)) $stmtNewPedidoItem->close();
    $conn->close();
}

?>