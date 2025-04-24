<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
 
include '../../includes/db.php';
 
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
 
    // Verificar se o usuário logado é admin e se o id que está sendo excluído é de um admin
    if ($_SESSION['tipo_usuario'] == 1) {
        // Verificar se o usuário logado está tentando excluir um admin
        $sql = "SELECT tipo_usuario FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
 
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if ($user['tipo_usuario'] == 1) {
                // Impedir a exclusão do admin
                echo "<script>alert('Não é possível excluir o usuário admin.'); window.location.href='usuarios.php';</script>";
                exit();
            }
        }
    }
 
    // Caso contrário, excluir o usuário
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
 
$sql = "SELECT * FROM usuarios";
$result = $conn->query($sql);
?>
 
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.075);
        }
        .card-custom {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid px-4 py-4">
        <div class="row mb-4 align-items-center">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h1 class="h2 text-primary">
                    <i class="bi bi-people-fill me-2"></i>Gerenciar Usuários
                </h1>
                <div>
                    <a href="cadastro_usuario.php" class="btn btn-success me-2">
                        <i class="bi bi-plus-circle me-1"></i>Novo Usuário
                    </a>
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left me-1"></i>Voltar ao Dashboard
                    </a>
                </div>
            </div>
        </div>
 
        <div class="card card-custom border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nome de Usuário</th>
                                <th>CNPJ</th>
                                <th>Responsável</th>
                                <th>Endereço</th>
                                <th>CEP</th>
                                <th>Bairro</th>
                                <th>Cidade</th>
                                <th>UF</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['cnpj']); ?></td>
                                <td><?php echo htmlspecialchars($row['responsavel']); ?></td>
                                <td><?php echo htmlspecialchars($row['endereco']); ?></td>
                                <td><?php echo htmlspecialchars($row['cep']); ?></td>
                                <td><?php echo htmlspecialchars($row['bairro']); ?></td>
                                <td><?php echo htmlspecialchars($row['cidade']); ?></td>
                                <td><?php echo htmlspecialchars($row['uf']); ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="editar_usuario.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($row['tipo_usuario'] == 1): ?>
                                            <!-- Desabilita o botão de excluir para admin -->
                                            <button class="btn btn-sm btn-outline-danger" disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
 
    <!-- Bootstrap JS e Dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 para confirmação de exclusão -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Tem certeza?',
                text: 'Você não poderá reverter esta ação!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?delete=' + id;
                }
            });
        }
    </script>
</body>
</html>
 
<?php
$conn->close();
?>
 
 