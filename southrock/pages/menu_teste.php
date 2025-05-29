<?php
    // southrock/pages/menu_teste.php
    session_start(); 

    // Simular um tipo de usuário (opcional)
    $_SESSION['tipo_usuario'] = 1; // Admin
    // $_SESSION['tipo_usuario'] = 2; // Filial 

    // **1. DEFINIR CAMINHOS A PARTIR DESTA PÁGINA (southrock/pages/menu_teste.php) **
    $path_to_css_folder_from_page = '../css/';      // Caminho para a PASTA css/
    $logo_image_path_from_page = '../images/zamp.png'; // Caminho para sua logo
    $logout_script_path_from_page = '../logout.php';   // Caminho para script de logout

    // Links de navegação (relativos à pasta 'pages/', pois é onde menu_teste.php está)
    $link_dashboard = 'admin/dashboard.php';             
    $link_pedidos_admin = 'admin/pedidos.php';            
    $link_produtos_admin = 'admin/produtos.php';          
    $link_usuarios_admin = 'admin/usuarios.php';          
    $link_cadastro_usuario_admin = 'admin/cadastro_usuario.php'; 
    // $link_fazer_pedidos_filial = 'lojas/fazer_pedidos.php'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste do Header com Menu Lateral</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="hcm-body-fixed-header"> 

    <?php
        // Inclui o header com o menu lateral
        // __DIR__ é southrock/pages/
        include __DIR__ . '/../includes/header_com_menu.php';
    ?>

    <div class="hcm-main-content"> 
        <h1>Página de Teste do Menu (Estilo Header)</h1>
        <p>Se o header estiver no topo e o menu lateral abrir ao clicar no ícone hambúrguer, a configuração está correta!</p>
        <p>Verifique o console do navegador (F12) para erros de carregamento de arquivos CSS ou JS (especialmente jQuery).</p>
        <hr>
        <h3>Verificações:</h3>
        <ul>
            <li>O header (navbar) está visível no topo?</li>
            <li>O logo (`../images/zamp.png`) aparece corretamente no header?</li>
            <li>O ícone de menu hambúrguer está visível?</li>
            <li>Ao clicar no ícone hambúrguer, o menu lateral (`.hcm-sidebar`) abre e fecha?</li>
            <li>Os ícones (Font Awesome) estão visíveis no menu lateral?</li>
            <li>As cores e o layout geral correspondem ao `header_com_menu.css`?</li>
            <li>O conteúdo principal da página (`hcm-main-content`) está posicionado corretamente abaixo do header?</li>
            <li>Se você simulou um `$_SESSION['tipo_usuario']`, os itens corretos são exibidos no menu lateral?</li>
        </ul>
        <p>Para testar os links de navegação, certifique-se de que as páginas de destino existem nos caminhos especificados (ex: `southrock/pages/admin/dashboard.php`).</p>
    </div>

</body>
</html>