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
$query = "SELECT status, tipo_pedido FROM pedidos WHERE id = ?";
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
$tipo_pedido = $pedido['tipo_pedido'];
$new_status = 'finalizado'; // O objetivo deste arquivo é sempre finalizar

// Lógica de transição de status
if ($current_status !== 'processo') {
    // Não permite finalizar pedidos que não estão no status 'processo'
    $_SESSION['error_message'] = "O pedido não pode ser finalizado a partir do status '" . $current_status . "'.";
    header("Location: detalhes_pedido.php?id=" . $pedido_id);
    exit();
}

// 2. Atualizar o status do pedido para "finalizado" e registrar a data de finalização
$data_finalizacao = date('Y-m-d H:i:s');
$query_update = "UPDATE pedidos SET status = ?, data_finalizacao = ? WHERE id = ?"; // Usando data_finalizacao
$stmt_update = $conn->prepare($query_update);
$stmt_update->bind_param("ssi", $new_status, $data_finalizacao, $pedido_id);

if ($stmt_update->execute()) {
    // Lógica de estoque baseada no tipo de pedido
    // Esta lógica não altera a tabela produtos no database.sql, mas pode ser relevante
    // Se você não tem 'quantidade_estoque' na tabela 'produtos', remova este bloco.
    // O database.sql fornecido não tem `quantidade_estoque` em `produtos`, apenas `sku`, `produto`, `grupo`, `unidade_medida`.
    // Se você não tem controle de estoque por aqui, remova o IF inteiro.
    /*
    if ($tipo_pedido == 'requisicao') {
        // Lógica para diminuir estoque em caso de requisição (exemplo simplificado)
        $query_itens = "SELECT sku, quantidade FROM pedido_itens WHERE pedido_id = ?";
        $stmt_itens = $conn->prepare($query_itens);
        $stmt_itens->bind_param("i", $pedido_id);
        $stmt_itens->execute();
        $result_itens = $stmt_itens->get_result();
        
        // Atualizar estoque para cada item requisitado
        while ($item = $result_itens->fetch_assoc()) {
            // CUIDADO: Seu database.sql NÃO TEM A COLUNA 'quantidade_estoque' na tabela 'produtos'.
            // Esta linha abaixo VAI DAR ERRO se você não tiver essa coluna.
            // Remova-a se não tiver controle de estoque.
            // $query_estoque = "UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE sku = ?";
            // $stmt_estoque = $conn->prepare($query_estoque);
            // $stmt_estoque->bind_param("is", $item['quantidade'], $item['sku']);
            // $stmt_estoque->execute();
        }
    } elseif ($tipo_pedido == 'devolucao' || $tipo_pedido == 'troca') {
        // Lógica para aumentar estoque em caso de devolução ou troca
        $query_itens = "SELECT sku, quantidade FROM pedido_itens WHERE pedido_id = ?";
        $stmt_itens = $conn->prepare($query_itens);
        $stmt_itens->bind_param("i", $pedido_id);
        $stmt_itens->execute();
        $result_itens = $stmt_itens->get_result();
        
        while ($item = $result_itens->fetch_assoc()) {
            // CUIDADO: Seu database.sql NÃO TEM A COLUNA 'quantidade_estoque' na tabela 'produtos'.
            // Esta linha abaixo VAI DAR ERRO se você não tiver essa coluna.
            // Remova-a se não tiver controle de estoque.
            // $query_estoque = "UPDATE produtos SET quantidade_estoque = quantidade_estoque + ? WHERE sku = ?";
            // $stmt_estoque = $conn->prepare($query_estoque);
            // $stmt_estoque->bind_param("is", $item['quantidade'], $item['sku']);
            // $stmt_estoque->execute();
        }
    }
    */
    // Fim da lógica de estoque (removida ou comentada para compatibilidade)

    $_SESSION['success_message'] = "Pedido finalizado com sucesso!";
} else {
    $_SESSION['error_message'] = "Erro ao finalizar o pedido: " . $conn->error;
}

// Fechar statements e conexão
$stmt->close();
if (isset($stmt_update)) $stmt_update->close();
// Não há mais $stmt_itens ou $stmt_estoque a serem fechados se a lógica de estoque for removida/comentada.

$conn->close();

// Redirecionar de volta para a página de detalhes do pedido
header("Location: detalhes_pedido.php?id=" . $pedido_id);
exit();
?>