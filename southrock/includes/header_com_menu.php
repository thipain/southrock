<?php
// southrock/includes/header_com_menu.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$current_page_basename = basename($_SERVER['PHP_SELF']);

// Variáveis de caminho (DEVEM ser definidas pela página que inclui este header)
$path_to_css_folder_from_page = isset($path_to_css_folder_from_page) ? $path_to_css_folder_from_page : './css/';
$logo_image_path_from_page = isset($logo_image_path_from_page) ? $logo_image_path_from_page : './images/zamp.png';
$logout_script_path_from_page = isset($logout_script_path_from_page) ? $logout_script_path_from_page : './logout.php';

// Links de navegação (DEVEM ser definidos pela página que inclui este header)
$link_dashboard = isset($link_dashboard) ? $link_dashboard : 'dashboard.php';
$link_pedidos_admin = isset($link_pedidos_admin) ? $link_pedidos_admin : 'pedidos.php';
$link_produtos_admin = isset($link_produtos_admin) ? $link_produtos_admin : 'produtos.php';
$link_usuarios_admin = isset($link_usuarios_admin) ? $link_usuarios_admin : 'usuarios.php';
$link_cadastro_usuario_admin = isset($link_cadastro_usuario_admin) ? $link_cadastro_usuario_admin : 'cadastro_usuario.php';
$link_fazer_pedidos_filial = isset($link_fazer_pedidos_filial) ? $link_fazer_pedidos_filial : 'fazer_pedidos.php';

?>

<link rel="stylesheet" href="<?php echo htmlspecialchars($path_to_css_folder_from_page); ?>header_com_menu.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<nav class="hcm-navbar">
    <div class="hcm-navbar-left">
        <i class="fas fa-bars hcm-menu-toggle"></i>
        <a href="<?php echo htmlspecialchars($link_dashboard); ?>" class="hcm-logo-link">
            <img src="<?php echo htmlspecialchars($logo_image_path_from_page); ?>" alt="Logo Empresa" class="hcm-logo-img">
        </a>
    </div>
    <div class="hcm-user-info">
        <span>Olá, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Usuário'; ?></span>
        <i class="fas fa-user-circle"></i>
    </div>
</nav>

<div class="hcm-sidebar">
    <ul class="hcm-sidebar-menu">
        <li <?php if($current_page_basename == 'dashboard.php') echo 'class="hcm-active"'; ?>>
            <a href="<?php echo htmlspecialchars($link_dashboard); ?>">
                <i class="fas fa-home"></i>
                <span>Início</span>
            </a>
        </li>

        <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 1): // Admin ?>
            <li <?php if($current_page_basename == 'pedidos.php' || $current_page_basename == 'aprovar_rejeitar_pedidos.php') echo 'class="hcm-active"'; ?>>
                <a href="<?php echo htmlspecialchars($link_pedidos_admin); ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Pedidos</span>
                </a>
            </li>
            <li <?php if($current_page_basename == 'produtos.php') echo 'class="hcm-active"'; ?>>
                <a href="<?php echo htmlspecialchars($link_produtos_admin); ?>">
                    <i class="fas fa-box-open"></i>
                    <span>Produtos</span>
                </a>
            </li>
            <li <?php if($current_page_basename == 'usuarios.php') echo 'class="hcm-active"'; ?>>
                <a href="<?php echo htmlspecialchars($link_usuarios_admin); ?>">
                    <i class="fas fa-users"></i>
                    <span>Usuários</span>
                </a>
            </li>
            <li <?php if($current_page_basename == 'cadastro_usuario.php') echo 'class="hcm-active"'; ?>>
                <a href="<?php echo htmlspecialchars($link_cadastro_usuario_admin); ?>">
                    <i class="fas fa-user-plus"></i>
                    <span>Cadastro Usuário</span>
                </a>
            </li>
        <?php elseif (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 2): // Filial ?>
            <li <?php if($current_page_basename == 'fazer_pedidos.php') echo 'class="hcm-active"'; ?>>
                <a href="<?php echo htmlspecialchars($link_fazer_pedidos_filial); ?>">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Pedidos</span>
                </a>
            </li>
        <?php endif; ?>

    </ul>

    <div class="hcm-sidebar-footer">
        <a href="<?php echo htmlspecialchars($logout_script_path_from_page); ?>" class="hcm-logout-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Sair</span>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarElement = document.querySelector('.hcm-sidebar');
    const menuToggleElement = document.querySelector('.hcm-menu-toggle');
    const bodyElement = document.body;

    function openSidebar() {
        if (sidebarElement) sidebarElement.classList.add('hcm-open');
        if (bodyElement) bodyElement.classList.add('hcm-sidebar-is-open');
    }

    function closeSidebar() {
        if (sidebarElement) sidebarElement.classList.remove('hcm-open');
        if (bodyElement) bodyElement.classList.remove('hcm-sidebar-is-open');
    }

    function toggleSidebar() {
        if (sidebarElement) sidebarElement.classList.toggle('hcm-open');
        if (bodyElement) bodyElement.classList.toggle('hcm-sidebar-is-open');
    }

    if (menuToggleElement) {
        menuToggleElement.addEventListener('click', function(event) {
            event.stopPropagation(); 
            toggleSidebar();
        });
    }

    document.addEventListener('click', function(event) {
        if (sidebarElement && sidebarElement.classList.contains('hcm-open')) {
            const isClickInsideSidebar = sidebarElement.contains(event.target);
            const isClickOnToggle = menuToggleElement ? menuToggleElement.contains(event.target) : false;

            if (!isClickInsideSidebar && !isClickOnToggle) {
                closeSidebar();
            }
        }
    });
});
</script>