<?php
session_start();
require 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    try {
        $conn = getConnection();
        
        if (!$conn) {
            throw new Exception("Falha na conexão com o banco de dados");
        }

        $sql = "SELECT id, username, senha, nivel_acesso FROM Usuarios WHERE username = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Erro ao preparar a consulta: " . $conn->error);
        }
        
        $stmt->bind_param('s', $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verifica a senha - compatível com BCRYPT ou SHA256 (legado)
            if (password_verify($senha, $user['senha']) || 
                hash('sha256', $senha) === $user['senha']) {
                
                $_SESSION['usuario'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'nivel_acesso' => $user['nivel_acesso']
                ];

                // Corrigindo o typo "Cozinhinheiro" para "Cozinheiro"
                $nivel = strtolower($user['nivel_acesso']);
                $nivel = str_replace(['cozinhinheiro', 'cozinhiheiro'], 'cozinheiro', $nivel);
                
                switch ($nivel) {
                    case 'admin':
                        header('Location: ../admin/index_admin.php');
                        break;
                    case 'gerente':
                        header('Location: ../Gerente/index_gerente.php');
                        break;
                    case 'atendente':
                        header('Location: index.php');
                        break;
                    case 'cozinheiro':
                        header('Location: ../Geral/index_cozinha.php');
                        break;
                    default:
                        header('Location: index.php');
                }
                exit();
            } else {
                $erro = "Senha incorreta!";
            }
        } else {
            $erro = "Usuário não encontrado!";
        }
    } catch (Exception $e) {
        $erro = "Erro no sistema: " . $e->getMessage();
        error_log($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PedidoPronto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1e5799, #207cca);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            z-index: 0;
        }
        
        .content {
            position: relative;
            z-index: 1;
        }
        
        .logo {
            width: 120px;
            margin: 0 auto 25px;
            display: block;
            filter: drop-shadow(0 3px 5px rgba(0,0,0,0.1));
        }
        
        h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 2rem;
            text-shadow: 0 2px 3px rgba(0,0,0,0.1);
            position: relative;
        }
        
        h2::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(to right, #3498db, #2ecc71);
            margin: 10px auto;
            border-radius: 2px;
        }
        
        .form-group {
            margin-bottom: 25px;
            text-align: left;
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #3498db;
            font-size: 1.1rem;
        }
        
        input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: #f8f9fa;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
        }
        
        input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: white;
        }
        
        button {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        button::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -60%;
            width: 20px;
            height: 200%;
            background: rgba(255,255,255,0.3);
            transform: rotate(30deg);
            transition: all 0.6s;
        }
        
        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.4);
        }
        
        button:hover::after {
            left: 120%;
        }
        
        button:active {
            transform: translateY(1px);
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.4);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 16px;
            text-align: center;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            animation: shake 0.5s;
        }
        
        .forgot-password {
            margin-top: 20px;
            text-align: center;
        }
        
        .forgot-password a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
            display: inline-block;
            position: relative;
        }
        
        .forgot-password a:hover {
            color: #2980b9;
        }
        
        .forgot-password a::after {
            content: '';
            display: block;
            width: 0;
            height: 2px;
            background: #3498db;
            transition: width 0.3s;
            position: absolute;
            bottom: -2px;
            left: 0;
        }
        
        .forgot-password a:hover::after {
            width: 100%;
        }
        
        .social-login {
            margin-top: 30px;
        }
        
        .social-text {
            color: #7f8c8d;
            margin-bottom: 15px;
            position: relative;
        }
        
        .social-text::before,
        .social-text::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e1e5eb;
            margin: 0 10px;
        }
        
        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            color: #3498db;
            font-size: 1.2rem;
            transition: all 0.3s;
            border: 1px solid #e1e5eb;
        }
        
        .social-icon:hover {
            background: #3498db;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(52, 152, 219, 0.3);
        }
        
        .register-link {
            margin-top: 25px;
            color: #7f8c8d;
        }
        
        .register-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        @keyframes shake {
            0% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-10px); }
            80% { transform: translateX(10px); }
            100% { transform: translateX(0); }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            h2 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <svg class="logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="45" fill="#3498db" opacity="0.8" />
                <path d="M35,30 L65,30 L75,50 L65,70 L35,70 L25,50 Z" fill="white" />
                <circle cx="50" cy="50" r="15" fill="#e74c3c" />
                <text x="50" y="52" text-anchor="middle" fill="white" font-size="10" font-weight="bold">PP</text>
            </svg>
            
            <h2>Acesse sua conta</h2>
            
            <?php if (!empty($erro)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="usuario">Usuário</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="usuario" name="usuario" placeholder="Digite seu usuário" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
                    </div>
                </div>
                
                <button type="submit">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
                
                <div class="forgot-password">
                    <a href="esqueci_senha.php">
                        <i class="fas fa-key"></i> Esqueci minha senha
                    </a>
                </div>
            </form>
            
            <div class="social-login">
                <div class="social-text" style="display: flex; align-items: center;">
                    <span style="flex: 1; height: 1px; background: #e1e5eb;"></span>
                    <span style="padding: 0 10px;"></span>
                    <span style="flex: 1; height: 1px; background: #e1e5eb;"></span>
                </div>
        </div>
    </div>
    
    <script>
        // Adicionando animação ao botão de login
        const loginButton = document.querySelector('button');
        if (loginButton) {
            loginButton.addEventListener('click', function() {
                this.classList.add('clicked');
                setTimeout(() => this.classList.remove('clicked'), 300);
            });
        }
        
        // Animação ao focar nos campos
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', () => {
                input.parentElement.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>