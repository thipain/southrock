<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 2) {
    $_SESSION['error_message_loja'] = "Acesso não autorizado.";
    header('Location: ../../index.php'); // Redirecionar para login
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pedido_id']) || !isset($_POST['acao_troca'])) {
    $_SESSION['error_message_loja'] = "Requisição inválida para responder à proposta.";
    header('Location: historico.php');
    exit();
}

$pedido_id = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
$acao = $_POST['acao_troca']; // 'aceitar' ou 'rejeitar'
$loggedInFilialId = $_SESSION['user_id'];

if (!$pedido_id) {
    $_SESSION['error_message_loja'] = "ID do pedido inválido para resposta.";
    header('Location: historico.php');
    exit();
}

$conn->begin_transaction();
try {
    // Verificar se a filial logada é a filial destino do pedido de troca e se o status está correto
    $stmt_check = $conn->prepare("SELECT filial_destino_id, status FROM pedidos WHERE id = ? AND tipo_pedido = 'troca' FOR UPDATE");
    if(!$stmt_check) throw new Exception("Erro ao preparar verificação: " . $conn->error);
    $stmt_check->bind_param("i", $pedido_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $pedido_info = $result_check->fetch_assoc();
    $stmt_check->close();

    if (!$pedido_info) {
        throw new Exception("Proposta de troca não encontrada ou não é uma troca válida.");
    }
    if ($pedido_info['filial_destino_id'] != $loggedInFilialId) {
        throw new Exception("Você não tem permissão para responder a esta proposta de troca.");
    }
    if ($pedido_info['status'] !== 'novo_troca_pendente_aceite_parceiro') {
        throw new Exception("Esta proposta de troca não está mais aguardando sua resposta (status atual: " . $pedido_info['status'] . ").");
    }

    $novo_status = '';
    if ($acao === 'aceitar') {
        $novo_status = 'troca_aceita_parceiro_pendente_matriz';
    } elseif ($acao === 'rejeitar') {
        $novo_status = 'rejeitado'; 
    } else {
        throw new Exception("Ação inválida fornecida.");
    }

    $stmt_update = $conn->prepare("UPDATE pedidos SET status = ?, usuario_id = ?, data_atualizacao = NOW() WHERE id = ?");
    if(!$stmt_update) throw new Exception("Erro ao preparar atualização: " . $conn->error);
    // usuario_id aqui é quem realizou a ação de aceite/rejeição (a filial B)
    $stmt_update->bind_param("sii", $novo_status, $loggedInFilialId, $pedido_id);
    
    if ($stmt_update->execute()) {
        if ($stmt_update->affected_rows > 0) {
            $conn->commit();
            if ($acao === 'aceitar') {
                $_SESSION['success_message_loja'] = "Proposta de troca #" . $pedido_id . " ACEITA com sucesso! Aguardando processamento da Matriz.";
            } else {
                $_SESSION['success_message_loja'] = "Proposta de troca #" . $pedido_id . " foi REJEITADA.";
            }
        } else {
            throw new Exception("Nenhuma linha afetada. O pedido pode já ter sido atualizado ou não corresponde aos critérios.");
        }
    } else {
        throw new Exception("Erro ao atualizar o status da proposta de troca: " . $stmt_update->error);
    }
    $stmt_update->close();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Erro em responder_proposta_troca.php: " . $e->getMessage());
    $_SESSION['error_message_loja'] = "Erro: " . $e->getMessage();
}

if(isset($conn)) $conn->close();

header("Location: detalhes_pedido_loja.php?id=" . $pedido_id);
exit();
?>