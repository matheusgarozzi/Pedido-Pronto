<?php
session_start();
require_once 'conexao.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['usuario'] ?? '';
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        // Verifica se o usuário existe
        $sql = "SELECT id, username FROM Usuarios WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Gera token único (válido por 1 hora)
            $token = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Atualiza token no banco
            $sql_update = "UPDATE Usuarios SET reset_token = ?, reset_token_expira = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('ssi', $token, $expiracao, $user['id']);
            $stmt_update->execute();
            
            // Mostra o link na tela (em produção, isso seria enviado para o admin)
            $link = "http://".$_SERVER['HTTP_HOST']."/redefinir_senha.php?token=$token";
            $mensagem = "Mostre este link ao administrador para redefinir sua senha:";
            $mensagem .= "<br><br><a href='$link'>$link</a>";
            
        } else {
            $erro = "Usuário não encontrado!";
        }
    } catch (Exception $e) {
        $erro = "Erro no sistema. Tente novamente mais tarde.";
        error_log($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        .success { color: green; margin: 1rem 0; word-break: break-all; }
        .error { color: red; margin: 1rem 0; }
        input, button { width: 100%; padding: 10px; margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Recuperar Senha</h2>
        
        <?php if (!empty($erro)): ?>
            <div class="error"><?= $erro ?></div>
        <?php endif; ?>
        
        <?php if (!empty($mensagem)): ?>
            <div class="success"><?= $mensagem ?></div>
        <?php else: ?>
            <form method="POST">
                <input type="text" name="usuario" placeholder="Digite seu usuário" required>
                <button type="submit">Gerar Link de Recuperação</button>
            </form>
        <?php endif; ?>
        
        <p><a href="login.php">Voltar ao Login</a></p>
    </div>
</body>
</html>
