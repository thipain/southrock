<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit();
}

include '../../includes/db.php';

$path_to_css_folder_from_page = '../../css/';
$logo_image_path_from_page = '../../images/zamp.png';
$logout_script_path_from_page = '../../logout/logout.php';

$link_dashboard = 'dashboard.php';
$link_pedidos_admin = 'pedidos.php';
$link_produtos_admin = 'produtos.php';
$link_usuarios_admin = 'usuarios.php';
$link_cadastro_usuario_admin = 'cadastro_usuario.php';

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 1) {
        $sql_check_admin = "SELECT tipo_usuario FROM usuarios WHERE id = ?";
        $stmt_check_admin = $conn->prepare($sql_check_admin);
        $stmt_check_admin->bind_param("i", $id);
        $stmt_check_admin->execute();
        $result_check_admin = $stmt_check_admin->get_result();

        if ($result_check_admin->num_rows == 1) {
            $user_to_delete = $result_check_admin->fetch_assoc();
            if ($user_to_delete['tipo_usuario'] == 1) {
                // Para evitar quebrar o HTML antes do header, podemos redirecionar com uma mensagem via query string
                // ou apenas definir uma mensagem e não executar a exclusão.
                // Por simplicidade aqui, apenas não executa e pode adicionar uma mensagem na tela se desejado.
                // echo "<script>alert('Não é possível excluir o usuário admin.'); window.location.href='usuarios.php';</script>";
                // exit();
                // No entanto, o código original já tem um botão desabilitado para admin, então essa verificação server-side é uma dupla garantia.
            } else {
                 $sql_delete = "DELETE FROM usuarios WHERE id = ?";
                 $stmt_delete = $conn->prepare($sql_delete);
                 $stmt_delete->bind_param("i", $id);
                 $stmt_delete->execute();
                 $stmt_delete->close();
            }
        }
        $stmt_check_admin->close();
    } elseif (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] != 1) {
        // Usuário não admin tentando excluir, não deve ter chegado aqui se a UI estiver correta.
        // Apenas não permite a exclusão se o ID não for o próprio ou se não tiver permissão.
        // A lógica de exclusão já desabilita o botão para o admin mestre na interface.
        // Se for um usuário comum tentando excluir outro, a lógica de permissão mais ampla decidiria.
        // Aqui, o código original permite excluir se não for o admin mestre (tipo_usuario == 1)
        $sql_delete = "DELETE FROM usuarios WHERE id = ? AND tipo_usuario != 1"; // Garante que não exclui admin mestre
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id);
        $stmt_delete->execute();
        $stmt_delete->close();
    }
    // Redireciona para limpar o GET da URL
    header("Location: usuarios.php");
    exit();
}

$sql_select_users = "SELECT * FROM usuarios";
$result_users = $conn->query($sql_select_users);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Dashboard</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <?php
        if (file_exists(__DIR__ . '/../../includes/header_com_menu.php')) {
            include __DIR__ . '/../../includes/header_com_menu.php';
        } else {
            echo "";
        }
    ?>
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/usuarios.css">
</head>

<body class="hcm-body-fixed-header">
   
    <div class="hcm-main-content">
        <div class="header">
            <h1><i class="bi bi-people-fill me-2"></i>Gerenciamento de Usuários</h1>
            <hr>
        </div>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary h3">Lista de Usuários</h2>
                <a href="cadastro_usuario.php" class="btn btn-success">
                    <i class="bi bi-plus-circle me-1"></i>Novo Usuário
                </a>
            </div>

            <div class="search-container mb-4">
                <div class="row">
                    <div class="col-md-8 mb-3 mb-md-0">
                        <div class="search-wrapper">
                            <input type="text" id="searchUsuario" class="search-input form-control"
                                placeholder="Buscar por nome, CNPJ ou responsável...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select search-input" id="filterUsuario">
                            <option value="">Todos os usuários</option>
                            <option value="admin">Administradores</option>
                            <option value="cliente">Filiais</option>
                        </select>
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
                                <?php if ($result_users->num_rows > 0): ?>
                                    <?php while ($row = $result_users->fetch_assoc()): ?>
                                    <tr data-tipo="<?php echo ($row['tipo_usuario'] == 1 || $row['tipo_usuario'] == 2) ? 'admin' : 'cliente'; ?>">
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
                                                <a href="editar_usuario.php?id=<?php echo $row['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($row['tipo_usuario'] == 1): ?>
                                                    <button class="btn btn-sm btn-outline-danger" disabled>
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="confirmDelete(<?php echo $row['id']; ?>)"
                                                        class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" class="text-center">Nenhum usuário encontrado.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Confirmar exclusão?',
                text: "Esta ação não poderá ser revertida!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'usuarios.php?delete=' + id;
                }
            });
        }

        document.getElementById('searchUsuario').addEventListener('keyup', function () {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById('searchUsuario');
            filter = input.value.toUpperCase();
            table = document.querySelector('.table tbody');
            tr = table.getElementsByTagName('tr');

            for (i = 0; i < tr.length; i++) {
                let rowVisible = false;
                for (let j = 0; j < 3; j++) { 
                    td = tr[i].getElementsByTagName('td')[j];
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            rowVisible = true;
                            break;
                        }
                    }
                }
                if(tr[i].getElementsByTagName('td').length === 1 && tr[i].getElementsByTagName('td')[0].colSpan === 9) { // Não oculta a linha "Nenhum usuário encontrado"
                    rowVisible = true;
                }
                tr[i].style.display = rowVisible ? '' : 'none';
            }
        });
    
        document.getElementById('filterUsuario').addEventListener('change', function () {
            var filterValue = this.value;
            var table = document.querySelector('.table tbody');
            var tr = table.getElementsByTagName('tr');

            for (var i = 0; i < tr.length; i++) {
                 if(tr[i].getElementsByTagName('td').length === 1 && tr[i].getElementsByTagName('td')[0].colSpan === 9) { // Não filtra a linha "Nenhum usuário encontrado"
                    tr[i].style.display = '';
                    continue;
                }
                var rowTipo = tr[i].getAttribute('data-tipo');
                if (filterValue === "" || rowTipo === filterValue) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        });
    </script>
</body>
</html>

<?php
if (isset($result_users)) {
    // $result_users->close(); // Não precisa se for resultado de query() e não prepare()
}
if (isset($conn)) {
    $conn->close();
}
?>