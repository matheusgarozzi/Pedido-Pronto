<?php
session_start();
require 'conexao.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    
    if (empty($username)) {
        $erro = "Por favor, informe o nome de usuário.";
    } else {
        try {
            $conn = Database::getInstance()->getConnection();
            
            // Verifica se o usuário existe
            $sql = "SELECT id FROM usuarios WHERE username = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Erro ao preparar consulta: " . $conn->error);
            }
            
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Gera token e define expiração (1 hora)
                $token = bin2hex(random_bytes(32));
                $token_expira = date('Y-m-d H:i:s', time() + 3600);
                
                // Atualiza o token no banco
                $update_sql = "UPDATE usuarios SET reset_token = ?, reset_token_expira = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                
                if (!$update_stmt) {
                    throw new Exception("Erro ao preparar atualização: " . $conn->error);
                }
                
                $update_stmt->bind_param('ssi', $token, $token_expira, $user['id']);
                if (!$update_stmt->execute()) {
                    throw new Exception("Erro ao atualizar token: " . $update_stmt->error);
                }
                
                // Link de redefinição (para testes)
                $link = "redefinir_senha.php?token=$token";
                $mensagem = "Clique <a href='$link'>aqui</a> para redefinir sua senha.";
                
                // Em produção, você enviaria por e-mail:
                /*
                $to = $user['email'];
                $subject = "Redefinição de Senha";
                $message = "Clique no link para redefinir sua senha: $link";
                $headers = "From: no-reply@seusite.com";
                mail($to, $subject, $message, $headers);
                $mensagem = "Um link de redefinição foi enviado para seu e-mail.";
                */
                
            } else {
                $erro = "Usuário não encontrado.";
            }
            
        } catch (Exception $e) {
            $erro = "Erro: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recuperação de Senha</title>
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
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            text-align: center;
        }
        
        .logo {
            width: 120px;
            margin-bottom: 25px;
        }
        
        h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #2980b9;
        }
        
        .message {
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 15px;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .login-link {
            margin-top: 25px;
            color: #7f8c8d;
        }
        
        .login-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Recuperação de Senha</h2>
        
        <?php if (!empty($erro)): ?>
            <div class="message error"><?= $erro ?></div>
        <?php endif; ?>
        
        <?php if (!empty($mensagem)): ?>
            <div class="message success"><?= $mensagem ?></div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Nome de Usuário</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <button type="submit">Recuperar Senha</button>
            </form>
        <?php endif; ?>
        
        <div class="login-link">
            Lembrou sua senha? <a href="login.php">Faça login</a>
        </div>
    </div>
</body>
</html>