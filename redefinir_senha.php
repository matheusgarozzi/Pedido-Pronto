<?php
session_start();
require_once 'conexao.php';

$mensagem = '';
$erro = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        // Verifica se o token é válido
        $sql = "SELECT id, username FROM Usuarios WHERE reset_token = ? AND reset_token_expira > NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['reset_user_id'] = $user['id'];
        } else {
            $erro = "Token inválido ou expirado. Solicite um novo link.";
        }
    } catch (Exception $e) {
        $erro = "Erro no sistema. Tente novamente mais tarde.";
        error_log($e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['reset_user_id'])) {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if ($nova_senha === $confirmar_senha) {
        try {
            $conn = Database::getInstance()->getConnection();
            $senha_hash = hash('sha256', $nova_senha);
            
            $sql = "UPDATE Usuarios SET senha = ?, reset_token = NULL, reset_token_expira = NULL WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $senha_hash, $_SESSION['reset_user_id']);
            $stmt->execute();
            
            if ($stmt->affected_rows === 1) {
                $mensagem = "Senha redefinida com sucesso!";
                unset($_SESSION['reset_user_id']);
            } else {
                $erro = "Erro ao atualizar a senha.";
            }
        } catch (Exception $e) {
            $erro = "Erro no banco de dados: " . $e->getMessage();
        }
    } else {
        $erro = "As senhas não coincidem!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f4f4f4; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .container { 
            background: white; 
            padding: 2rem; 
            border-radius: 8px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
            width: 300px; 
            text-align: center; 
        }
        .success { color: green; margin: 1rem 0; }
        .error { color: red; margin: 1rem 0; }
        input { 
            width: 100%; 
            padding: 10px; 
            margin: 8px 0; 
            box-sizing: border-box; 
        }
        button { 
            background-color: #4CAF50; 
            color: white; 
            padding: 10px; 
            border: none; 
            width: 100%; 
            cursor: pointer; 
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Redefinir Senha</h2>
        
        <?php if (!empty($erro)): ?>
            <div class="error"><?= $erro ?></div>
        <?php endif; ?>
        
        <?php if (!empty($mensagem)): ?>
            <div class="success"><?= $mensagem ?></div>
            <p><a href="login.php">Voltar ao Login</a></p>
        <?php elseif (isset($_SESSION['reset_user_id'])): ?>
            <form method="POST">
                <input type="password" name="nova_senha" placeholder="Nova senha" required>
                <input type="password" name="confirmar_senha" placeholder="Confirmar nova senha" required>
                <button type="submit">Redefinir Senha</button>
            </form>
        <?php else: ?>
            <div class="error">Link inválido ou expirado</div>
            <p><a href="esqueci_senha.php">Solicitar novo link</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
