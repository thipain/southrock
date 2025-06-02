<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['username']) || $_SESSION['tipo_usuario'] != 1) { 
    // Apenas admin pode editar pedidos neste contexto
    header("Location: ../../index.php"); // Ajuste o caminho para o login se necessário
    exit();
}

require_once '../../includes/db.php';
// require_once '../../includes/config_helper.php'; // Se precisar de configs

// Definir caminhos para o header_com_menu.php
$path_to_css_folder_from_page = '../../css/';
$logo_image_path_from_page = '../../images/zamp.png'; // Verifique se este é o caminho correto
$logout_script_path_from_page = '../../logout/logout.php';

// Links de navegação para o header_com_menu.php (ajuste conforme sua estrutura)
$link_dashboard = 'dashboard.php';
$link_pedidos_admin = 'pedidos.php'; // Página ativa
$link_produtos_admin = 'produtos.php';
$link_usuarios_admin = 'usuarios.php';
$link_cadastro_usuario_admin = 'cadastro_usuario.php';
// $link_configuracoes_admin = 'configuracoes.php'; // Se tiver

// $nome_aplicacao = getConfig('nome_aplicacao', $conn, 'SouthRock Pedidos'); // Se usar no título

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pedido_id = intval($_POST['pedido_id']);
    $tipo_pedido = $_POST['tipo_pedido'];
    // Garante que filial_usuario_id seja um inteiro ou NULL se não aplicável
    $filial_usuario_id = isset($_POST['filial_usuario_id']) && !empty($_POST['filial_usuario_id']) ? intval($_POST['filial_usuario_id']) : null;

    // filial_destino_id só é relevante para certos tipos de pedido
    $filial_destino_id = (isset($_POST['filial_destino_id']) && !empty($_POST['filial_destino_id']) && in_array($_POST['tipo_pedido'], ['doacao', 'troca'])) ? intval($_POST['filial_destino_id']) : NULL;
    
    $observacoes = trim($_POST['observacoes']);
    $status = $_POST['status']; 

    $item_sku = $_POST['item_sku'] ?? [];
    $item_quantidade = $_POST['item_quantidade'] ?? [];
    $item_observacao = $_POST['item_observacao'] ?? [];
    $item_id_existente = $_POST['item_id_existente'] ?? []; // Para identificar itens existentes

    if ($filial_usuario_id === null && !in_array($tipo_pedido, ['doacao'])) { // Matriz originando para ela mesma? Ou filial não selecionada?
        // Adicione uma lógica aqui se filial_usuario_id for obrigatório para certos tipos
        // Por exemplo, uma requisição sempre deve ter uma filial de origem.
        // Se for uma doação DA MATRIZ, filial_usuario_id pode ser o ID do usuário admin/matriz.
        // Vamos assumir que o ID do usuário logado (admin) pode ser o filial_usuario_id se for uma ação da matriz
        if($_SESSION['tipo_usuario'] == 1) { // Se admin/matriz está logado
             // Busca o ID do usuário admin que representa a MATRIZ (ex: ID 1 ou um específico)
            $stmt_matriz = $conn->prepare("SELECT id FROM usuarios WHERE tipo_usuario = 1 AND username = ? LIMIT 1"); // Ou outra lógica para pegar ID da Matriz
            $stmt_matriz->bind_param("s", $_SESSION['username']); // Supondo que o admin logado é o representante da matriz
            $stmt_matriz->execute();
            $res_matriz = $stmt_matriz->get_result();
            if($matriz_data = $res_matriz->fetch_assoc()){
                $filial_usuario_id = $matriz_data['id'];
            }
            $stmt_matriz->close();
        }
        if($filial_usuario_id === null){
             $_SESSION['error_message'] = "Filial de origem não identificada para este tipo de pedido.";
             header("Location: editar_pedido.php?id=" . $pedido_id);
             exit();
        }
    }


    $conn->begin_transaction(); 

    try {
        $query_update_pedido = "UPDATE pedidos SET tipo_pedido = ?, status = ?, filial_usuario_id = ?, filial_destino_id = ?, observacoes = ?, usuario_id = ? WHERE id = ?";
        $stmt_update_pedido = $conn->prepare($query_update_pedido);
        
        // usuario_id é quem está fazendo a alteração
        $usuario_logado_id = $_SESSION['user_id']; 

        $stmt_update_pedido->bind_param("ssiisis", $tipo_pedido, $status, $filial_usuario_id, $filial_destino_id, $observacoes, $usuario_logado_id, $pedido_id);
        
        $stmt_update_pedido->execute();
        $stmt_update_pedido->close();

        // Remover itens que não foram reenviados (ou seja, foram excluídos da UI)
        // Primeiro, pegar todos os IDs dos itens atuais do pedido
        $sql_get_current_items = "SELECT id FROM pedido_itens WHERE pedido_id = ?";
        $stmt_get_current_items = $conn->prepare($sql_get_current_items);
        $stmt_get_current_items->bind_param("i", $pedido_id);
        $stmt_get_current_items->execute();
        $result_current_items = $stmt_get_current_items->get_result();
        $current_item_ids_db = [];
        while($row = $result_current_items->fetch_assoc()){
            $current_item_ids_db[] = $row['id'];
        }
        $stmt_get_current_items->close();

        $submitted_item_ids = array_map('intval', array_filter($item_id_existente, 'is_numeric'));
        $items_to_delete_ids = array_diff($current_item_ids_db, $submitted_item_ids);

        if(!empty($items_to_delete_ids)){
            $query_delete_itens = "DELETE FROM pedido_itens WHERE id IN (" . implode(',', array_fill(0, count($items_to_delete_ids), '?')) . ") AND pedido_id = ?";
            $stmt_delete_itens = $conn->prepare($query_delete_itens);
            $types_delete = str_repeat('i', count($items_to_delete_ids)) . 'i';
            $params_delete = array_merge($items_to_delete_ids, [$pedido_id]);
            $stmt_delete_itens->bind_param($types_delete, ...$params_delete);
            $stmt_delete_itens->execute();
            $stmt_delete_itens->close();
        }


        $query_update_item = "UPDATE pedido_itens SET sku = ?, quantidade = ?, observacao = ? WHERE id = ? AND pedido_id = ?";
        $stmt_update_item = $conn->prepare($query_update_item);

        $query_insert_item = "INSERT INTO pedido_itens (pedido_id, sku, quantidade, observacao) VALUES (?, ?, ?, ?)";
        $stmt_insert_item = $conn->prepare($query_insert_item);

        for ($i = 0; $i < count($item_sku); $i++) {
            $current_item_id = isset($item_id_existente[$i]) ? intval($item_id_existente[$i]) : 0;
            $sku = intval($item_sku[$i]);
            $quantidade = floatval($item_quantidade[$i]); 
            $observacao_item = !empty($item_observacao[$i]) ? trim($item_observacao[$i]) : NULL;

            if ($sku > 0 && $quantidade > 0) {
                if ($current_item_id > 0 && in_array($current_item_id, $submitted_item_ids)) { // Item existente para atualizar
                    $stmt_update_item->bind_param("idsii", $sku, $quantidade, $observacao_item, $current_item_id, $pedido_id);
                    $stmt_update_item->execute();
                } else { // Novo item para inserir
                    $stmt_insert_item->bind_param("iids", $pedido_id, $sku, $quantidade, $observacao_item);
                    $stmt_insert_item->execute();
                }
            }
        }
        if(isset($stmt_update_item)) $stmt_update_item->close();
        if(isset($stmt_insert_item)) $stmt_insert_item->close();

        $conn->commit(); 
        $_SESSION['success_message'] = "Pedido atualizado com sucesso!";
        header("Location: detalhes_pedido.php?id=" . $pedido_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback(); 
        $_SESSION['error_message'] = "Erro ao atualizar o pedido: " . $e->getMessage();
        // Não redireciona daqui, para que o formulário possa ser exibido com os dados e a mensagem de erro
    }
}


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID do pedido inválido.";
    header("Location: pedidos.php");
    exit();
}

$pedido_id = intval($_GET['id']);

$query_pedido = "SELECT p.*,
          u_origem.nome_filial AS nome_filial_origem,
          u_destino.nome_filial AS nome_filial_destino,
          usr.nome AS usuario_nome_processou -- Nome do usuário que processou/registrou
          FROM pedidos p
          LEFT JOIN usuarios u_origem ON p.filial_usuario_id = u_origem.id
          LEFT JOIN usuarios u_destino ON p.filial_destino_id = u_destino.id 
          LEFT JOIN usuarios usr ON p.usuario_id = usr.id
          WHERE p.id = ?";

$stmt_pedido = $conn->prepare($query_pedido);
$stmt_pedido->bind_param("i", $pedido_id);
$stmt_pedido->execute();
$resultado_pedido = $stmt_pedido->get_result();

if ($resultado_pedido->num_rows === 0) {
    $_SESSION['error_message'] = "Pedido não encontrado.";
    header("Location: pedidos.php");
    exit();
}
$pedido = $resultado_pedido->fetch_assoc();
$stmt_pedido->close();

// Verifica se o pedido pode ser editado
if (in_array($pedido['status'], ['finalizado', 'rejeitado', 'cancelado'])) {
    $_SESSION['error_message'] = "Não é possível editar um pedido com status '" . htmlspecialchars($pedido['status']) . "'.";
    header("Location: detalhes_pedido.php?id=" . $pedido_id);
    exit();
}

$query_itens = "SELECT pi.id as item_id_existente, pi.sku, pi.quantidade, pi.observacao, prod.produto, prod.unidade_medida
                FROM pedido_itens pi
                JOIN produtos prod ON pi.sku = prod.sku
                WHERE pi.pedido_id = ?";
$stmt_itens = $conn->prepare($query_itens);
$stmt_itens->bind_param("i", $pedido_id);
$stmt_itens->execute();
$itens_pedido = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_itens->close();

// Buscar todas as filiais (usuários marcados como eh_filial = TRUE) e a Matriz (tipo_usuario = 1)
$query_filiais_e_matriz = "SELECT id, nome, nome_filial, cnpj FROM usuarios WHERE eh_filial = TRUE OR tipo_usuario = 1 ORDER BY nome_filial, nome";
$result_filiais = $conn->query($query_filiais_e_matriz);
$filiais_options = $result_filiais->fetch_all(MYSQLI_ASSOC);

$query_produtos = "SELECT sku, produto, unidade_medida FROM produtos ORDER BY produto";
$result_produtos = $conn->query($query_produtos);
$produtos_options = $result_produtos->fetch_all(MYSQLI_ASSOC);

// A conexão $conn é fechada no final do HTML
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pedido #<?= htmlspecialchars($pedido_id) ?> - <?php // echo htmlspecialchars($nome_aplicacao); ?></title>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <?php
        if (file_exists(__DIR__ . '/../../includes/header_com_menu.php')) {
            include __DIR__ . '/../../includes/header_com_menu.php';
        }
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($path_to_css_folder_from_page); ?>dashboard.css"> 
    <link rel="stylesheet" href="<?php echo htmlspecialchars($path_to_css_folder_from_page); ?>editar_pedido.css"> <style>
        /* Estilos que estavam no editar_pedido.css original podem ser movidos para o arquivo CSS linkado
           ou mantidos aqui se forem muito específicos apenas para esta página.
           Por exemplo:
        */
        .item-row {
            border: 1px solid #e0e0e0; padding: 15px; margin-bottom: 15px;
            border-radius: 8px; background-color: #fcfcfc; position: relative;
        }
        .remove-item {
            position: absolute; top: 5px; right: 5px; background: none; border: none;
            color: #dc3545; font-size: 1.2rem; cursor: pointer; padding: 5px;
            line-height: 1; transition: color 0.2s ease-in-out;
        }
        .remove-item:hover { color: #c82333; }
        .form-section label { font-weight: bold; margin-bottom: 5px; display: block; }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body class="hcm-body-fixed-header">

    <div class="hcm-main-content">
        <div class="container-fluid px-4 py-4">
            <div class="row mb-3">
                <div class="col-12">
                     <div class="painel-titulo d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-pencil-fill me-2"></i>Editar Pedido #<?= htmlspecialchars($pedido_id) ?>
                        </div>
                        <a href="detalhes_pedido.php?id=<?= htmlspecialchars($pedido_id) ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i>Ver Detalhes
                        </a>
                    </div>
                    <hr class="barrinha" style="width:250px;">
                </div>
            </div>

            <?php
            if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                unset($_SESSION['success_message']);
            }
            // Verifica se a mensagem de erro da sessão de POST é a mesma que a de GET.
            // Isso evita mostrar a mensagem de erro duas vezes se o usuário for redirecionado de volta para esta página
            // após uma falha no POST e uma mensagem de erro já tiver sido definida.
            $session_error_message = $_SESSION['error_message'] ?? null;
            if ($session_error_message && (empty($error_message) || $session_error_message !== $error_message) ) {
                 echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($session_error_message) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
             unset($_SESSION['error_message']); // Limpa sempre para não mostrar em outros loads

            // Mensagem de erro vinda do processamento POST nesta mesma página (se não houve redirect)
            if (!empty($error_message) && $error_message !== $session_error_message) {
                 echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($error_message) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            ?>

            <form id="formEditarPedido" action="editar_pedido.php?id=<?= htmlspecialchars($pedido_id) ?>" method="POST">
                <input type="hidden" name="pedido_id" value="<?= htmlspecialchars($pedido_id) ?>">

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0 text-primary"><i class="bi bi-info-circle-fill me-2"></i>Informações do Pedido</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="tipo_pedido" class="form-label">Tipo de Pedido:</label>
                                <select class="form-select" id="tipo_pedido" name="tipo_pedido" required>
                                    <option value="requisicao" <?= ($pedido['tipo_pedido'] == 'requisicao') ? 'selected' : '' ?>>Requisição</option>
                                    <option value="troca" <?= ($pedido['tipo_pedido'] == 'troca') ? 'selected' : '' ?>>Troca</option>
                                    <option value="doacao" <?= ($pedido['tipo_pedido'] == 'doacao') ? 'selected' : '' ?>>Doação</option>
                                    <option value="devolucao" <?= ($pedido['tipo_pedido'] == 'devolucao') ? 'selected' : '' ?>>Devolução</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label">Status:</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="novo" <?= ($pedido['status'] == 'novo') ? 'selected' : '' ?>>Novo</option>
                                    <option value="aprovado" <?= ($pedido['status'] == 'aprovado') ? 'selected' : '' ?>>Aprovado</option>
                                    <option value="processo" <?= ($pedido['status'] == 'processo') ? 'selected' : '' ?>>Em Processo</option>
                                    </select>
                            </div>

                            <div class="col-md-6">
                                <label for="filial_usuario_id" class="form-label">Filial de Origem:</label>
                                <select class="form-select" id="filial_usuario_id" name="filial_usuario_id" required>
                                    <option value="">Selecione a Origem</option>
                                    <?php foreach ($filiais_options as $filial_opt) : ?>
                                        <option value="<?= htmlspecialchars($filial_opt['id']) ?>" <?= ($pedido['filial_usuario_id'] == $filial_opt['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($filial_opt['nome_filial'] ?: $filial_opt['nome']) ?> (<?= htmlspecialchars($filial_opt['cnpj'] ?: 'Matriz') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6" id="filial_destino_group" style="<?= in_array($pedido['tipo_pedido'], ['doacao', 'troca']) ? '' : 'display: none;' ?>">
                                <label for="filial_destino_id" class="form-label">Filial de Destino:</label>
                                <select class="form-select" id="filial_destino_id" name="filial_destino_id">
                                    <option value="">Selecione o Destino (se aplicável)</option>
                                    <?php foreach ($filiais_options as $filial_opt) : ?>
                                         <?php if ($filial_opt['id'] == $pedido['filial_usuario_id']) continue; // Não pode ser o mesmo que a origem ?>
                                        <option value="<?= htmlspecialchars($filial_opt['id']) ?>" <?= ($pedido['filial_destino_id'] == $filial_opt['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($filial_opt['nome_filial'] ?: $filial_opt['nome']) ?> (<?= htmlspecialchars($filial_opt['cnpj'] ?: 'Matriz') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label for="observacoes" class="form-label">Observações Gerais do Pedido:</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= htmlspecialchars($pedido['observacoes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-4 mb-4">
                    <div class="card-header bg-light py-3 card-header-flex">
                        <h5 class="mb-0 text-primary"><i class="bi bi-list-ul me-2"></i>Itens do Pedido</h5>
                        <button type="button" class="btn btn-sm btn-success" onclick="addItem()">
                            <i class="bi bi-plus-circle me-1"></i> Adicionar Novo Item
                        </button>
                    </div>
                    <div class="card-body p-4" id="items-container">
                        <?php if (!empty($itens_pedido)) : ?>
                            <?php foreach ($itens_pedido as $index => $item) : ?>
                                <div class="item-row mb-3 p-3 border rounded bg-white">
                                    <input type="hidden" name="item_id_existente[<?= $index ?>]" value="<?= htmlspecialchars($item['item_id_existente']) ?>">
                                    <button type="button" class="remove-item btn-close float-end" aria-label="Remover item" onclick="removeItem(this)"></button>
                                    <div class="row g-3">
                                        <div class="col-md-5 form-section">
                                            <label for="item_sku_<?= $index ?>" class="form-label">Produto (SKU)</label>
                                            <select class="form-select" id="item_sku_<?= $index ?>" name="item_sku[]" required>
                                                <option value="">Selecione um Produto</option>
                                                <?php foreach ($produtos_options as $produto_option) : ?>
                                                    <option value="<?= htmlspecialchars($produto_option['sku']) ?>" <?= ($item['sku'] == $produto_option['sku']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($produto_option['produto']) ?> (SKU: <?= htmlspecialchars($produto_option['sku']) ?> - <?= htmlspecialchars($produto_option['unidade_medida']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 form-section">
                                            <label for="item_quantidade_<?= $index ?>" class="form-label">Quantidade</label>
                                            <input type="number" class="form-control" id="item_quantidade_<?= $index ?>" name="item_quantidade[]" min="0.01" step="any" value="<?= htmlspecialchars($item['quantidade']) ?>" required>
                                        </div>
                                        <div class="col-md-4 form-section">
                                            <label for="item_observacao_<?= $index ?>" class="form-label">Observação do Item</label>
                                            <input type="text" class="form-control" id="item_observacao_<?= $index ?>" name="item_observacao[]" value="<?= htmlspecialchars($item['observacao'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="text-muted text-center py-3" id="no-items-message">Nenhum item neste pedido. Clique em "Adicionar Novo Item" para começar.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 mb-3">
                    <a href="detalhes_pedido.php?id=<?= htmlspecialchars($pedido_id) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Cancelar Edição
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle-fill me-1"></i>Salvar Alterações no Pedido
                    </button>
                </div>
            </form>
            <div class="sistema-info text-center mt-3">
                 </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const productsData = <?= json_encode($produtos_options) ?>;
        let itemIndexCounter = <?= count($itens_pedido) ?>; // Para dar IDs únicos aos novos itens

        document.addEventListener('DOMContentLoaded', function() {
            const tipoPedidoSelect = document.getElementById('tipo_pedido');
            const filialDestinoGroup = document.getElementById('filial_destino_group');
            const filialDestinoSelect = document.getElementById('filial_destino_id');
            const filialOrigemSelect = document.getElementById('filial_usuario_id');

            function toggleFilialDestino() {
                const tipo = tipoPedidoSelect.value;
                if (tipo === 'doacao' || tipo === 'troca') {
                    filialDestinoGroup.style.display = '';
                    filialDestinoSelect.required = true;
                } else {
                    filialDestinoGroup.style.display = 'none';
                    filialDestinoSelect.required = false;
                    // filialDestinoSelect.value = ''; // Limpa seleção se não aplicável
                }
                updateDestinoOptions(); // Atualiza opções de destino ao mudar tipo
            }
            
            function updateDestinoOptions() {
                const origemId = filialOrigemSelect.value;
                const currentDestinoId = filialDestinoSelect.value; // Salva o valor atual
                
                // Limpa opções atuais, exceto a primeira "Selecione..."
                const firstOption = filialDestinoSelect.options[0];
                filialDestinoSelect.innerHTML = '';
                filialDestinoSelect.appendChild(firstOption);
                firstOption.selected = true; // Resseleciona "Selecione"

                <?php foreach ($filiais_options as $filial_opt) : ?>
                    if ('<?= htmlspecialchars($filial_opt['id']) ?>' !== origemId) { // Não adiciona se for igual à origem
                        const option = document.createElement('option');
                        option.value = '<?= htmlspecialchars($filial_opt['id']) ?>';
                        option.textContent = '<?= htmlspecialchars($filial_opt['nome_filial'] ?: $filial_opt['nome']) ?> (<?= htmlspecialchars($filial_opt['cnpj'] ?: 'Matriz') ?>)';
                        if ('<?= htmlspecialchars($filial_opt['id']) ?>' === currentDestinoId) { // Resseleciona se era o valor anterior
                            option.selected = true;
                        }
                        filialDestinoSelect.appendChild(option);
                    }
                <?php endforeach; ?>
            }


            if(tipoPedidoSelect) tipoPedidoSelect.addEventListener('change', toggleFilialDestino);
            if(filialOrigemSelect) filialOrigemSelect.addEventListener('change', updateDestinoOptions);

            toggleFilialDestino(); // Chamada inicial para configurar a visibilidade
            updateDestinoOptions(); // Chamada inicial para filtrar destino

            // Fechar alertas automaticamente
            var alertSuccess = document.querySelector('.alert-success.alert-dismissible');
            if (alertSuccess && typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                setTimeout(function() { bootstrap.Alert.getOrCreateInstance(alertSuccess).close(); }, 5000);
            }
            var alertError = document.querySelector('.alert-danger.alert-dismissible');
            if (alertError && typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                 setTimeout(function() { bootstrap.Alert.getOrCreateInstance(alertError).close(); }, 7000);
            }
        });

        function addItem() {
            itemIndexCounter++;
            const itemsContainer = document.getElementById('items-container');
            const noItemsMessage = document.getElementById('no-items-message');
            if (noItemsMessage) noItemsMessage.style.display = 'none';

            const newItemRow = document.createElement('div');
            newItemRow.className = 'item-row mb-3 p-3 border rounded bg-white';
            // Não adiciona item_id_existente para novos itens

            let productOptionsHTML = '<option value="">Selecione um Produto</option>';
            productsData.forEach(product => {
                productOptionsHTML += `<option value="${product.sku}">${product.produto} (SKU: ${product.sku} - ${product.unidade_medida})</option>`;
            });

            newItemRow.innerHTML = `
                <input type="hidden" name="item_id_existente[${itemIndexCounter}]" value="0"> <button type="button" class="remove-item btn-close float-end" aria-label="Remover item" onclick="removeItem(this)"></button>
                <div class="row g-3">
                    <div class="col-md-5 form-section">
                        <label for="item_sku_${itemIndexCounter}" class="form-label">Produto (SKU)</label>
                        <select class="form-select" id="item_sku_${itemIndexCounter}" name="item_sku[]" required>
                            ${productOptionsHTML}
                        </select>
                    </div>
                    <div class="col-md-3 form-section">
                        <label for="item_quantidade_${itemIndexCounter}" class="form-label">Quantidade</label>
                        <input type="number" class="form-control" id="item_quantidade_${itemIndexCounter}" name="item_quantidade[]" min="0.01" step="any" value="1" required>
                    </div>
                    <div class="col-md-4 form-section">
                        <label for="item_observacao_${itemIndexCounter}" class="form-label">Observação do Item</label>
                        <input type="text" class="form-control" id="item_observacao_${itemIndexCounter}" name="item_observacao[]" placeholder="Opcional">
                    </div>
                </div>
            `;
            itemsContainer.appendChild(newItemRow);
        }

        function removeItem(button) {
            Swal.fire({
                title: 'Remover Item?',
                text: "Este item será removido do pedido ao salvar. Confirma?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, remover!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const itemRow = button.closest('.item-row');
                    itemRow.remove(); // Apenas remove do DOM. A lógica de exclusão do DB ocorre no submit.
                    
                    const itemsContainer = document.getElementById('items-container');
                    if (itemsContainer.querySelectorAll('.item-row').length === 0) {
                        const noItemsMessage = document.getElementById('no-items-message');
                        if (noItemsMessage) noItemsMessage.style.display = 'block';
                        else {
                             const p = document.createElement('p');
                             p.id = 'no-items-message';
                             p.className = 'text-muted text-center py-3';
                             p.textContent = 'Nenhum item neste pedido. Clique em "Adicionar Novo Item" para começar.';
                             itemsContainer.appendChild(p);
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>