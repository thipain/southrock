<?php
require_once '../../includes/db.php';

$output = '';
$searchTerm = '';

if (isset($_POST['search'])) {
    $searchTerm = $_POST['search'];
    
    if (empty($searchTerm)) {
        $output = "<tr><td colspan='4' class='text-center py-3'>Digite algo na busca para ver os produtos.</td></tr>";
    } else {
        $likeTerm = '%' . $searchTerm . '%';

        $sql = "SELECT sku, produto, grupo FROM produtos WHERE 
                sku LIKE ? OR 
                produto LIKE ? OR 
                grupo LIKE ? 
                ORDER BY sku";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sss', $likeTerm, $likeTerm, $likeTerm);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 0) {
                while ($produto = $resultado->fetch_assoc()) {
                    $output .= "<tr>";
                    $output .= "<td>" . htmlspecialchars($produto['sku']) . "</td>";
                    $output .= "<td>" . htmlspecialchars($produto['produto']) . "</td>";
                    $output .= "<td>" . htmlspecialchars($produto['grupo']) . "</td>";
                    $output .= "<td class='text-center'>
                                    <div class='btn-group' role='group'>
                                        <a href='editar_produto.php?sku=" . htmlspecialchars($produto['sku']) . "' class='btn btn-sm btn-outline-primary'>
                                            <i class='bi bi-pencil'></i>
                                        </a>
                                        <button onclick='confirmDelete(\"" . htmlspecialchars(addslashes($produto['sku'])) . "\")' class='btn btn-sm btn-outline-danger'>
                                            <i class='bi bi-trash'></i>
                                        </button>
                                    </div>
                                </td>";
                    $output .= "</tr>";
                }
            } else {
                $output = "<tr><td colspan='4' class='text-center py-3'>Nenhum produto encontrado para \"".htmlspecialchars($searchTerm)."\".</td></tr>";
            }
            $stmt->close();
        } else {
            $output = "<tr><td colspan='4' class='text-center py-3'>Erro ao preparar a consulta.</td></tr>";
        }
    }
} else {
    $output = "<tr><td colspan='4' class='text-center py-3'>Termo de busca não fornecido.</td></tr>";
}

$conn->close();
echo $output;
?>