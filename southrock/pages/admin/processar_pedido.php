<?php
session_start();
// Habilita a exibição de erros para depuração (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verifica se o usuário está logado e se é um administrador (tipo_usuario = 1)
if (!isset($_SESSION['username']) || $_SESSION['tipo_usuario'] != 1) {
    header("Location: ../index.php"); // Redireciona para a página de login se não for admin
    exit();
}

require_once '../../includes/db.php'; // Inclui o arquivo de conexão com o banco de dados

// Verificar se o ID do pedido foi fornecido e é um número válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID do pedido inválido.";
    header("Location: pedidos.php"); // Redireciona de volta para a lista de pedidos
    exit();
}

$pedido_id = intval($_GET['id']);

// 1. Buscar o status atual do pedido
$query = "SELECT status FROM pedidos WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Pedido não encontrado
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
$update_columns = []; // Array para construir a parte SET da query

// Lógica de transição de status
switch ($current_status) {
    case 'novo':
    case 'aprovado': // Se um pedido foi aprovado, mas não processado, pode ir para processo
        $new_status = 'processo';
        $update_columns[] = "status = ?";
        $update_columns[] = "data_processamento = ?";
        $bind_types = "ss"; // status, data_processamento
        $bind_params = [&$new_status, date('Y-m-d H:i:s')];
        break;
    case 'processo':
        $new_status = 'finalizado';
        $update_columns[] = "status = ?";
        $update_columns[] = "data_finalizacao = ?"; // CORRIGIDO: de data_entrega para data_finalizacao
        $bind_types = "ss"; // status, data_finalizacao
        $bind_params = [&$new_status, date('Y-m-d H:i:s')];
        break;
    case 'rejeitado':
    case 'cancelado':
    case 'finalizado':
        // Não permite processar pedidos que já estão nestes estados finais
        $_SESSION['error_message'] = "O pedido não pode ser processado a partir do status '" . $current_status . "'.";
        header("Location: detalhes_pedido.php?id=" . $pedido_id);
        exit();
    default:
        // Caso de status desconhecido ou não previsto
        $_SESSION['error_message'] = "Status do pedido desconhecido ou inválido: '" . $current_status . "'.";
        header("Location: detalhes_pedido.php?id=" . $pedido_id);
        exit();
}

// Construir a query de atualização dinamicamente
if (!empty($update_columns)) {
    $update_sql = "UPDATE pedidos SET " . implode(", ", $update_columns) . " WHERE id = ?";
    $bind_types .= "i"; // Adiciona o tipo para o pedido_id
    $bind_params[] = &$pedido_id; // Adiciona o pedido_id aos parâmetros
} else {
    // Isso não deve acontecer com a lógica atual, mas é uma segurança
    $_SESSION['error_message'] = "Nenhuma ação de atualização definida para o status atual.";
    header("Location: detalhes_pedido.php?id=" . $pedido_id);
    exit();
}


// 2. Atualizar o status do pedido e datas
$stmt_update = $conn->prepare($update_sql);

// O método bind_param espera referências para os parâmetros.
// Usamos call_user_func_array para lidar com um array de referências.
call_user_func_array([$stmt_update, 'bind_param'], array_merge([$bind_types], $bind_params));


if ($stmt_update->execute()) {
    // NENHUM REGISTRO EM PEDIDO_LOGS: A seção de log de pedidos foi removida conforme sua solicitação.
    $_SESSION['success_message'] = "Status do pedido atualizado para '" . $new_status . "'.";
} else {
    $_SESSION['error_message'] = "Erro ao atualizar o status do pedido: " . $conn->error;
}

// Fechar statements e conexão
$stmt->close();
if (isset($stmt_update)) $stmt_update->close();

$conn->close();

// Redirecionar de volta para a página de detalhes do pedido
header("Location: detalhes_pedido.php?id=" . $pedido_id);
exit();
?>