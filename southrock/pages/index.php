<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Southrock</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif; /* Fonte Inter, moderna e clean */
            background-color: #f9fbfd; /* Fundo muito claro, quase branco */
            overflow: hidden;
        }

        .login-wrapper {
            display: flex;
            width: 90%; /* Mais largo para o layout dividido */
            max-width: 1000px; /* Largura máxima controlada */
            min-height: 550px; /* Altura mínima para o wrapper */
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); /* Sombra suave e elegante */
            overflow: hidden;
            border: 1px solid #e8eaf1; /* Borda sutil */
        }

        .left-panel {
            flex: 1.2; /* Painel esquerdo um pouco maior */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 50px;
            background-color: #eff3f8; /* Cor de fundo suave para o painel da logo */
            border-right: 1px solid #e8eaf1; /* Linha divisória sutil */
            text-align: center;
        }

        .left-panel img {
            max-width: 80%;
            height: auto;
            margin-bottom: 30px;
            filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.1)); /* Sombra discreta na logo */
        }

        .left-panel h2 {
            font-size: 2em;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .left-panel p {
            font-size: 1em;
            color: #666;
            line-height: 1.6;
            max-width: 350px; /* Limita largura do texto */
        }

        .right-panel {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px;
            background-color: #ffffff; /* Fundo branco para o painel de login */
        }

        .login-container {
            width: 100%;
            max-width: 380px; /* Tamanho controlado do formulário */
            padding: 0; /* Remover padding adicional da div para o form ocupar o espaço */
        }

        h1 {
            text-align: center;
            color: #222; /* Cor de texto mais escura para o título do login */
            margin-bottom: 40px;
            font-weight: 700;
            font-size: 2.5em;
        }

        .form-control {
            height: 50px;
            border: 1px solid #dcdfe6; /* Borda neutra e fina */
            border-radius: 10px !important;
            margin-bottom: 25px;
            padding-left: 15px;
            padding-right: 45px;
            font-size: 1.05em;
            color: #333;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control::placeholder {
            color: #a0a5ad; /* Placeholder em tom de cinza */
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15); /* Sombra mais discreta no foco */
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0a5ad;
            pointer-events: none;
            font-size: 1.1em;
        }

        .btn-primary {
            background-color: #007bff; /* Azul primário */
            border: none;
            height: 55px;
            font-weight: 600;
            margin-top: 35px;
            border-radius: 10px;
            font-size: 1.15em;
            letter-spacing: 0.5px;
            color: white;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.2); /* Sombra sutil para o botão */
            transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: #0056b3; /* Azul mais escuro no hover */
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(0, 123, 255, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(0, 123, 255, 0.2);
        }

        .form-footer {
            text-align: center;
            margin-top: 35px;
            font-size: 0.95em;
            color: #777;
        }

        .form-footer a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .form-footer a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
                width: 95%;
                min-height: auto;
                border-radius: 15px;
            }

            .left-panel {
                border-bottom: 1px solid #e8eaf1;
                border-right: none;
                padding: 30px;
                border-radius: 15px 15px 0 0;
            }
            .left-panel img {
                max-width: 60%;
                margin-bottom: 20px;
            }
            .left-panel h2 {
                font-size: 1.7em;
            }
            .left-panel p {
                font-size: 0.9em;
                max-width: 90%;
            }

            .right-panel {
                padding: 30px;
            }

            .login-container {
                max-width: 100%; /* Permite que o formulário ocupe mais espaço em telas pequenas */
            }

            h1 {
                font-size: 2em;
                margin-bottom: 30px;
            }

            .form-control {
                height: 48px;
                font-size: 1em;
                margin-bottom: 20px;
            }

            .btn-primary {
                height: 50px;
                font-size: 1.1em;
                margin-top: 30px;
            }

            .form-footer {
                margin-top: 30px;
                font-size: 0.9em;
            }
        }
    </style>
</head>

<body>

    <div class="login-wrapper">
        <div class="left-panel">
            <img src="../images/zamp.png" alt="Zamp Logo">
            <h2>Gestão Simplificada para o seu Negócio</h2>
            <p>Acesse nossa plataforma para otimizar suas operações e alcançar novos patamares de eficiência.</p>
        </div>
        <div class="right-panel">
            <div class="login-container">
                <h1>Acessar Conta</h1>
                <form action="login.php" method="POST">
                    <div class="input-group">
                        <input type="text" id="username" name="username" class="form-control" placeholder="E-MAIL" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>

                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control" placeholder="SENHA" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Entrar</button>
                </form>

                <div class="form-footer">
                    <p>Esqueceu sua senha? <a href="#">Recuperar acesso</a></p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>