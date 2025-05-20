<!DOCTYPE html>
<html lang="pt-BR">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Southrock</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Verdana, Geneva, Tahoma, sans-serif;
            background-size: cover;
            background-color: #e9ecef;
        }
 
        .container {
            background-color: black;
            filter: blur(20%);
            border-radius: 45px;
            padding: 40px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            width: 390px;
            max-width: 100%;
            margin-right: 15%;
        }
 
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
 
        .logo {
            height: 80px;
            margin-bottom: 15px;
        }
 
        h1 {
            text-align: center;
            /* Alinha o texto ao centro */
            color: white;
            margin-bottom: 25px;
            font-weight: 600;
            font-family: 'Times New Roman', Times, serif;
        }
 
        .form-control {
            height: 45px;
            border: 1px solid #ddd;
            border-radius: 15px !important;
            margin-bottom: 15px;
            transition: border-color 0.3s;
            padding-left: 15px;
            padding-right: 40px;
            /* Espaço para o ícone */
        }
 
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-primary {
            background-color: #0028f1;
            margin-right: 15%;
            height: 45px;
            font-weight: 500;
            margin-top: 25px;
            transition: background-color 0.3s;
            border-radius: 15px;
            font-family:'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;
            
        }

        .btn-primary:hover {
            background-color: #fd7f25;
        }
 
        .form-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: white;
        }
 
        .form-footer a {
            color: #0028f1;
            text-decoration: none;
        }
 
        .form-footer a:hover {
            text-decoration: underline;
        }
 
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
 
        .input-icon {
            position: absolute;
            right: 15px;
            /* Ajustado para alinhar melhor com border-radius */
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            pointer-events: none;
            /* Evita que o ícone interfira na interação com o input */
        }
 
        #username,
        #password {
            border-radius: 15px;
            width: 100%;
        }
 
        .img{
            margin-left: 10%;
            width: 380px;
            align-items: center;
            justify-content: center;
        }
       
    </style>
</head>
 
<body>
 
      <img class="img" src="../images/zamp.png" alt="logo">
 
    <div class="container">
        <h1>LOGIN</h1>
        <form action="login.php" method="POST">
            <div class="input-group">
                <input type="text" id="username" name="username" class="form-control" placeholder="E-MAIL" required>
                <i class="fas fa-user input-icon"></i>
                <img src="" alt="">
            </div>
 
            <div class="input-group">
                <input type="password" id="password" name="password" class="form-control" placeholder="PASSWORD" required>
                <i class="fas fa-lock input-icon"></i>
            </div>
 
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </form>
 
        <div class="form-footer">
            <p>Esqueceu sua senha? <a href="#">Recuperar acesso</a></p>
        </div>
    </div>
</body>
 
</html>