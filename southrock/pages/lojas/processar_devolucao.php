<?php
session_start();
error_reporting(E_ALL); // Para desenvolvimento, bom para ver todos os erros
ini_set('display_errors', 1); // Para desenvolvimento

if (!isset($_SESSION['username'])) {
    // Se não há sessão de username, user_id provavelmente também não existe.
    // Redirecionar para login é mais seguro.
    header("Location: ../../index.php"); // Ajuste para sua página de login principal
    exit();
}

require_once '../../includes/db.php';

$loggedInUserId = $_SESSION['user_id'] ?? null; // Melhor pegar direto do user_id se já definido no login
$loggedInUserType = $_SESSION['tipo_usuario'] ?? null;

// Se user_id ou tipo_usuario não estão na sessão, tenta buscar (embora o ideal seja definir no login)
if ($loggedInUserId === null || $loggedInUserType === null) {
    $stmtUser = $conn->prepare("SELECT id, tipo_usuario FROM usuarios WHERE username = ?");
    if ($stmtUser) {
        $stmtUser->bind_param("s", $_SESSION['username']);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();
        if ($currentUserData = $resultUser->fetch_assoc()) {
            $_SESSION['user_id'] = $currentUserData['id']; // Garante que está na sessão
            $_SESSION['tipo_usuario'] = $currentUserData['tipo_usuario']; // Garante que está na sessão
            $loggedInUserId = $currentUserData['id'];
            $loggedInUserType = $currentUserData['tipo_usuario'];
        }
        $stmtUser->close();
    } else {
        // Falha crítica, não foi possível preparar a statement
        $_SESSION['error_message_loja'] = "Erro crítico ao verificar dados do usuário.";
        header("Location: historico.php");
        exit();
    }
}


if ($loggedInUserId === null || $loggedInUserType != 2) {
    $_SESSION['error_message_loja'] = "Acesso não autorizado ou tipo de usuário inválido.";
    header("Location: historico.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message_loja'] = "Método de requisição inválido.";
    header("Location: historico.php");
    exit();
}

$original_pedido_id = filter_input(INPUT_POST, 'original_pedido_id', FILTER_VALIDATE_INT);
$return_destination_id = filter_input(INPUT_POST, 'return_destination_id', FILTER_VALIDATE_INT);
$selected_items_data = $_POST['items'] ?? [];
$motivo_devolucao = trim($_POST['motivo_devolucao'] ?? '');


if (!$original_pedido_id) { // $return_destination_id pode ser a Matriz (ex: ID 1), que é válido.
    $_SESSION['error_message_loja'] = "ID do pedido original ausente ou inválido.";
    $redirect_url = $original_pedido_id ? "detalhes_pedido_loja.php?id=" . $original_pedido_id : "historico.php";
    header("Location: " . $redirect_url);
    exit();
}
if (!$return_destination_id) { // Destino da devolução precisa ser válido
    $_SESSION['error_message_loja'] = "Destino da devolução ausente ou inválido.";
    header("Location: detalhes_pedido_loja.php?id=" . $original_pedido_id);
    exit();
}


$items_to_return = [];
if (empty($selected_items_data)) {
    $_SESSION['error_message_loja'] = "Nenhum item foi selecionado para devolução.";
    header("Location: detalhes_pedido_loja.php?id=" . $original_pedido_id . "&devolver=true#form_devolucao");
    exit();
}

foreach ($selected_items_data as $item_key_form => $item_data) { // item_key_form é o ID do item_pedido original
    if (isset($item_data['selected']) && $item_data['selected'] === ($item_data['sku'] ?? null)) { // Verifica se o checkbox foi marcado e o valor é o SKU
        $sku = filter_var($item_data['sku'], FILTER_VALIDATE_INT);
        $quantity_to_return = filter_var($item_data['quantity_to_return'], FILTER_VALIDATE_FLOAT); // Permite decimal
        $original_quantity = filter_var($item_data['original_quantity'], FILTER_VALIDATE_FLOAT);

        if (!$sku || $quantity_to_return === false || $quantity_to_return <= 0) {
            $_SESSION['error_message_loja'] = "Dados inválidos para o item SKU " . ($sku ?: $item_key_form) . " (quantidade zerada/inválida).";
            header("Location: detalhes_pedido_loja.php?id=" . $original_pedido_id . "&devolver=true#form_devolucao");
            exit();
        }
        if ($quantity_to_return > $original_quantity) {
            $_SESSION['error_message_loja'] = "Quantidade a devolver (" . $quantity_to_return . ") excede a quantidade recebida (" . $original_quantity . ") para o produto SKU " . $sku . ".";
            header("Location: detalhes_pedido_loja.php?id=" . $original_pedido_id . "&devolver=true#form_devolucao");
            exit();
        }
        $items_to_return[] = [
            'sku' => $sku,
            'quantity' => $quantity_to_return,
            // 'observacao' => trim($item_data['observacao_devolucao'] ?? '') // Se você adicionar um campo de observação por item devolvido
        ];
    }
}

if (empty($items_to_return)) {
    $_SESSION['error_message_loja'] = "Nenhum item válido para devolução processado. Verifique as seleções e quantidades.";
    header("Location: detalhes_pedido_loja.php?id=" . $original_pedido_id . "&devolver=true#form_devolucao");
    exit();
}

$conn->begin_transaction();

try {
    $sqlNewPedido = "INSERT INTO pedidos (filial_usuario_id, filial_destino_id, usuario_id, data, tipo_pedido, status, pedido_original_id, observacoes) 
                     VALUES (?, ?, ?, NOW(), 'devolucao', 'novo', ?, ?)";
    $stmtNewPedido = $conn->prepare($sqlNewPedido);
    if (!$stmtNewPedido) {
        throw new Exception("Erro DB (novo pedido): " . $conn->error);
    }
    $stmtNewPedido->bind_param("iiiis", $loggedInUserId, $return_destination_id, $loggedInUserId, $original_pedido_id, $motivo_devolucao);
    $stmtNewPedido->execute();

    $new_return_pedido_id = $conn->insert_id;
    if (!$new_return_pedido_id) {
        throw new Exception("Falha ao criar novo pedido de devolução (ID não gerado): " . $stmtNewPedido->error);
    }

    $sqlNewPedidoItem = "INSERT INTO pedido_itens (pedido_id, sku, quantidade) VALUES (?, ?, ?)"; // Adicione observacao se for coletada por item
    $stmtNewPedidoItem = $conn->prepare($sqlNewPedidoItem);
    if (!$stmtNewPedidoItem) {
        throw new Exception("Erro DB (itens do pedido): " . $conn->error);
    }

    foreach ($items_to_return as $item) {
        $stmtNewPedidoItem->bind_param("iid", $new_return_pedido_id, $item['sku'], $item['quantity']); // "d" para decimal (quantidade)
        $stmtNewPedidoItem->execute();
        if ($stmtNewPedidoItem->affected_rows <= 0) {
            throw new Exception("Falha ao adicionar item (SKU: {$item['sku']}) ao pedido de devolução: " . $stmtNewPedidoItem->error);
        }
    }

    $conn->commit();
    $_SESSION['success_message_loja'] = "Pedido de devolução #" . $new_return_pedido_id . " criado com sucesso!"; // CORRIGIDO
    header("Location: historico.php");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message_loja'] = "Erro ao processar devolução: " . $e->getMessage(); // CORRIGIDO
    error_log("Erro em processar_devolucao.php: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    header("Location: detalhes_pedido_loja.php?id=" . $original_pedido_id . "&devolver=true#form_devolucao");
    exit();
} finally {
    if (isset($stmtNewPedido)) $stmtNewPedido->close();
    if (isset($stmtNewPedidoItem)) $stmtNewPedidoItem->close();
    if (isset($conn)) $conn->close();
}
