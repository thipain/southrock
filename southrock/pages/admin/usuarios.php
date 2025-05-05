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
    <link rel="stylesheet" href="../../css/usuarios.css">
    <style>
        /* Estilos herdados do pedidos.css */
        body {
            display: flex;
            font-family: 'Arial', sans-serif;
            margin: 0;
            height: 100vh;
            background-color: #fff4e8;
        }

        .sidebar {
            width: 60px;
            background-image: linear-gradient(to left, rgb(124, 187, 235) 0%, rgb(60, 111, 177) 50%, rgb(0, 37, 78) 100%);
            transition: width 0.3s;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            height: 100vh;
            position: fixed;
            z-index: 1000;
        }

        .sidebar:hover {
            width: 200px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 15px;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }

        .sidebar a:hover {
            background-color: #480ca8;
        }

        .sidebar-header {
            color: white;
            text-align: center;
            padding: 13px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .icon {
            color: white;
            font-size: 20px;
            width: 30px;
            text-align: center;
            margin-right: 10px;
        }

        .text {
            display: none;
        }

        .sidebar a:hover .text {
            display: inline;
        }

        .sidebar a .text {
            display: inline;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .sidebar:hover a .text {
            opacity: 1;
        }

        .content {
            flex: 1;
            padding: 0;
            background-color: #E2EDFA;
            display: flex;
            flex-direction: column;
            margin-left: 60px;
            transition: margin-left 0.3s;
            width: 100%;
        }

        .header {
            background-image: linear-gradient(to right, rgb(124, 187, 235) 0%, rgb(60, 111, 177) 50%, rgb(0, 37, 78) 100%);
            color: white;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            margin-bottom: 0;
            border-radius: 0;
        }

        .header h1 {
            margin: 0;
            font-size: 1.5rem;
        }

        .main-content {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }

        .btn-primary {
            background-color: #0077B6;
            border-color: #0077B6;
        }

        .btn-primary:hover {
            background-color: #023E8A;
            border-color: #023E8A;
        }

        .btn-success {
            background-color: #0096C7;
            border-color: #0096C7;
        }

        .btn-success:hover {
            background-color: #0077B6;
            border-color: #0077B6;
        }

        .card-custom {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .search-container {
            background-color: #F8FAFC;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .search-input {
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 10px 15px;
            width: 100%;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            border-color: #0077B6;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 119, 182, 0.25);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <div class="sidebar-header">
                <i class="bi bi-shield-lock icon"></i>
            </div>
            <a href="dashboard.php">
                <i class="bi bi-speedometer2 icon"></i>
                <span class="text">Dashboard</span>
            </a>
            <a href="usuarios.php" class="active">
                <i class="bi bi-people-fill icon"></i>
                <span class="text">Usuários</span>
            </a>
            <a href="pedidos.php">
                <i class="bi bi-cart-fill icon"></i>
                <span class="text">Pedidos</span>
            </a>
            <a href="produtos.php">
                <i class="bi bi-box-seam icon"></i>
                <span class="text">Produtos</span>
            </a>
            <a href="relatorios.php">
                <i class="bi bi-file-earmark-bar-graph icon"></i>
                <span class="text">Relatórios</span>
            </a>
        </div>
        <div>
            <a href="configuracoes.php">
                <i class="bi bi-gear-fill icon"></i>
                <span class="text">Configurações</span>
            </a>
            <a href="logout.php">
                <i class="bi bi-box-arrow-right icon"></i>
                <span class="text">Sair</span>
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="header">
            <h1><i class="bi bi-people-fill me-2"></i>Gerenciamento de Usuários</h1>
        </div>
        
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary h3">Lista de Usuários</h2>
                <a href="cadastro_usuario.php" class="btn btn-success">
                    <i class="bi bi-plus-circle me-1"></i>Novo Usuário
                </a>
            </div>
            
            <!-- Search Box -->
            <div class="search-container mb-4">
                <div class="row">
                    <div class="col-md-8 mb-3 mb-md-0">
                        <div class="search-wrapper">
                            <input type="text" id="searchUsuario" class="search-input" placeholder="Buscar por nome, CNPJ ou responsável...">
                            <i class="bi bi-search search-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select search-input" id="filterUsuario">
                            <option value="">Todos os usuários</option>
                            <option value="admin">Administradores</option>
                            <option value="cliente">Clientes</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
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
    </div>
 
    <!-- Bootstrap JS e Dependências -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 para confirmação de exclusão -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Script para confirmação de exclusão
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

        // Script para pesquisa dinâmica
        document.getElementById('searchUsuario').addEventListener('keyup', function() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById('searchUsuario');
            filter = input.value.toUpperCase();
            table = document.querySelector('.table');
            tr = table.getElementsByTagName('tr');

            for (i = 1; i < tr.length; i++) {
                let found = false;
                for (let j = 0; j < 3; j++) { // Buscar nos 3 primeiros campos (username, cnpj, responsável)
                    td = tr[i].getElementsByTagName('td')[j];
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                if (found) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        });

        // Script para filtragem por tipo de usuário
        document.getElementById('filterUsuario').addEventListener('change', function() {
            var filter = this.value;
            var table = document.querySelector('.table');
            var tr = table.getElementsByTagName('tr');

            for (var i = 1; i < tr.length; i++) {
                var td = tr[i];
                if (filter === '') {
                    tr[i].style.display = '';
                } else {
                    var tipoUsuario = tr[i].getAttribute('data-tipo') || '';
                    if (tipoUsuario === filter) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        });
    </script>
</body>
</html>
 
<?php
$conn->close();
?>