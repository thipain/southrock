<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1); // Mantenha para desenvolvimento

// Verifica se o usuário é admin e está logado
if (!isset($_SESSION['username']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 1) {
    $_SESSION['error_message'] = "Acesso não autorizado.";
    header("Location: ../../index.php"); // Ajuste para sua página de login principal
    exit();
}

require_once '../../includes/db.php'; 

// Validação dos parâmetros GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID do pedido inválido.";
    header("Location: pedidos.php"); 
    exit();
}
$pedido_id = intval($_GET['id']);
$action = isset($_GET['action']) ? strtolower(trim($_GET['action'])) : null;

if (empty($action)) {
    $_SESSION['error_message'] = "Nenhuma ação especificada para o pedido.";
    header("Location: detalhes_pedido.php?id=" . $pedido_id);
    exit();
}

// Busca o pedido para verificar status e tipo
$query_check = "SELECT status, tipo_pedido FROM pedidos WHERE id = ?";
$stmt_check = $conn->prepare($query_check);
if (!$stmt_check) {
    $_SESSION['error_message'] = "Erro ao preparar consulta: " . $conn->error;
    header("Location: pedidos.php");
    exit();
}
$stmt_check->bind_param("i", $pedido_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    $_SESSION['error_message'] = "Pedido #" . $pedido_id . " não encontrado.";
    $stmt_check->close();
    header("Location: pedidos.php");
    exit();
}
$pedido = $result_check->fetch_assoc();
$current_status = $pedido['status'];
$tipo_pedido = $pedido['tipo_pedido'];
$stmt_check->close();

$new_status = '';
$update_columns_sql_parts = []; // Partes da query SQL (ex: "status = ?")
$bind_types_string = "";       // String de tipos para bind_param (ex: "ssi")
$bind_params_values = [];      // Array com os valores para bind_param

$redirect_page = "detalhes_pedido.php?id=" . $pedido_id; // Página de destino padrão

// Lógica baseada na AÇÃO e no STATUS ATUAL
switch ($action) {
    case 'processar':
        if (($tipo_pedido !== 'troca' && in_array($current_status, ['novo', 'aprovado'])) ||
            ($tipo_pedido === 'troca' && $current_status === 'troca_aceita_parceiro_pendente_matriz')) {
            $new_status = 'processo';
            $update_columns_sql_parts[] = "status = ?";
            $bind_types_string .= "s";
            $bind_params_values[] = $new_status;

            // Adiciona data de processamento se não estiver já em processo
            if ($current_status !== 'processo') { // Evita resetar data_processamento se já estiver
                $update_columns_sql_parts[] = "data_processamento = ?";
                $bind_types_string .= "s";
                $bind_params_values[] = date('Y-m-d H:i:s');
            }
        } else {
            $_SESSION['error_message'] = "O pedido não pode ser 'processado' a partir do status atual ('" . htmlspecialchars($current_status) . "') para o tipo de pedido ('" . htmlspecialchars($tipo_pedido) . "').";
        }
        break;

    case 'cancelar':
        if (!in_array($current_status, ['finalizado', 'cancelado'])) {
            $new_status = 'cancelado';
            $update_columns_sql_parts[] = "status = ?";
            $bind_types_string .= "s";
            $bind_params_values[] = $new_status;
            // Você pode querer adicionar uma coluna data_cancelamento e atualizá-la aqui
            // $update_columns_sql_parts[] = "data_cancelamento = NOW()";
        } else {
            $_SESSION['error_message'] = "O pedido já está em um estado ('" . htmlspecialchars($current_status) . "') que não permite cancelamento por esta ação.";
        }
        break;

    case 'rejeitar':
        // Admin só pode rejeitar diretamente pedidos que NÃO são trocas e estão em status iniciais.
        // Trocas são rejeitadas pela filial parceira.
        if ($tipo_pedido !== 'troca' && in_array($current_status, ['novo', 'aprovado'])) {
            $new_status = 'rejeitado';
            $update_columns_sql_parts[] = "status = ?";
            $bind_types_string .= "s";
            $bind_params_values[] = $new_status;
            // Você pode querer adicionar uma coluna data_rejeicao e atualizá-la aqui
        } else {
            $_SESSION['error_message'] = "Este pedido não pode ser 'rejeitado' diretamente pela Matriz neste estado ou tipo.";
        }
        break;

    default:
        $_SESSION['error_message'] = "Ação '" . htmlspecialchars($action) . "' desconhecida ou não permitida.";
        break;
}

// Se uma nova ação válida foi determinada e há colunas para atualizar
if (!empty($new_status) && !empty($update_columns_sql_parts)) {
    // Adiciona o usuario_id (quem realizou a ação) e o pedido_id (WHERE)
    $update_columns_sql_parts[] = "usuario_id = ?"; // Quem está fazendo a ação
    $sql_update = "UPDATE pedidos SET " . implode(", ", $update_columns_sql_parts) . " WHERE id = ?";
    
    $bind_types_string .= "ii"; // Adiciona 'i' para usuario_id e 'i' para pedido_id
    $bind_params_values[] = $_SESSION['user_id']; 
    $bind_params_values[] = $pedido_id;

    $stmt_update = $conn->prepare($sql_update);
    if (!$stmt_update) {
        $_SESSION['error_message'] = "Erro ao preparar atualização do pedido: " . $conn->error;
    } else {
        // Criação dinâmica de referências para bind_param
        $bind_params_refs = [$bind_types_string]; // Primeiro elemento é a string de tipos
        for ($i = 0; $i < count($bind_params_values); $i++) {
            $bind_params_refs[] = &$bind_params_values[$i];
        }
        
        call_user_func_array([$stmt_update, 'bind_param'], $bind_params_refs);

        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $_SESSION['success_message'] = "Pedido #" . $pedido_id . " atualizado para '" . htmlspecialchars($new_status) . "' com sucesso.";
            } else {
                $_SESSION['error_message'] = "Nenhuma alteração feita no pedido #" . $pedido_id . ". O status pode já ser o desejado ou o pedido não foi encontrado com os critérios exatos.";
            }
        } else {
            $_SESSION['error_message'] = "Erro ao executar atualização do pedido: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
} elseif (empty($_SESSION['error_message'])) { // Se não houve erro antes, mas nenhuma ação foi definida
    $_SESSION['error_message'] = "Nenhuma ação válida foi determinada para o pedido #" . $pedido_id . " com status '" . htmlspecialchars($current_status) . "'.";
}

if(isset($conn)) $conn->close();
header("Location: " . $redirect_page);
exit();
?>