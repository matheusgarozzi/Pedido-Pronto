<?php
session_start();
require 'conexao.php';

$mensagem = '';
$erro = '';
$token_valido = false;
$token = $_GET['token'] ?? '';

if (!empty($token)) {
    try {
        $conn = Database::getInstance()->getConnection();
        
        // Verifica se o token é válido e não expirou
        $sql = "SELECT id FROM usuarios WHERE reset_token = ? AND reset_token_expira > NOW()";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Erro ao preparar consulta: " . $conn->error);
        }
        
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $token_valido = true;
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
        }
        
    } catch (Exception $e) {
        $erro = "Erro: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido) {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if (empty($nova_senha) || empty($confirmar_senha)) {
        $erro = "Por favor, preencha todos os campos.";
    } elseif ($nova_senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } else {
        try {
            // Cria hash da nova senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            // Atualiza a senha e limpa o token
            $update_sql = "UPDATE usuarios SET senha = ?, reset_token = NULL, reset_token_expira = NULL WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            
            if (!$update_stmt) {
                throw new Exception("Erro ao preparar atualização: " . $conn->error);
            }
            
            $update_stmt->bind_param('si', $senha_hash, $user_id);
            if (!$update_stmt->execute()) {
                throw new Exception("Erro ao atualizar a senha: " . $update_stmt->error);
            }
            
            $mensagem = "Senha redefinida com sucesso!";
            $token_valido = false; // Mostrar mensagem de sucesso
            
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
    <title>Redefinir Senha</title>
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
            margin-top: 10px;
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
        
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
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
        <h2>Redefinir Senha</h2>
        
        <?php if (!empty($erro)): ?>
            <div class="message error"><?= $erro ?></div>
        <?php endif; ?>
        
        <?php if (!empty($mensagem)): ?>
            <div class="message success"><?= $mensagem ?></div>
            <div class="login-link">
                <a href="login.php">Voltar para o login</a>
            </div>
        <?php elseif ($token_valido): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="nova_senha">Nova Senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" required>
                </div>
                
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Nova Senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                </div>
                
                <button type="submit">Atualizar Senha</button>
            </form>
        <?php else: ?>
            <div class="message info">
                <?php if (empty($token)): ?>
                    Token de redefinição não fornecido.
                <?php else: ?>
                    Token inválido ou expirado. Por favor, solicite um novo link de redefinição.
                <?php endif; ?>
            </div>
            <div class="login-link">
                <a href="esqueci_senha.php">Solicitar novo link</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>