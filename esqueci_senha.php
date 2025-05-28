<?php
session_start();
require_once 'conexao.php'; // Importa a classe Database

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    try {
        // Obtém a conexão corretamente usando a classe Database
        $conn = Database::getInstance()->getConnection(); // Adicione esta linha ANTES de usar $conn
        $stmt = $conn->prepare("SELECT id, username FROM Usuarios WHERE email = ?");
        
        // Verifica se o email existe
        $sql = "SELECT id, username FROM Usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Gera token único
            $token = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Atualiza token no banco
            $sql_update = "UPDATE Usuarios SET reset_token = ?, reset_token_expira = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('ssi', $token, $expiracao, $user['id']);
            $stmt_update->execute();
            
            // Configuração do email (substitua com seus dados SMTP)
            $link = "http://".$_SERVER['HTTP_HOST']."/redefinir_senha.php?token=$token";
            $mensagem = "Um link de redefinição foi enviado para seu email.";
            
            /* REMOVA O COMENTÁRIO PARA USAR SMTP REAL
            require 'vendor/autoload.php'; // Se usar PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.seuprovedor.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'seu@email.com';
            $mail->Password = 'suasenha';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->setFrom('seu@email.com', 'Sistema');
            $mail->addAddress($email);
            $mail->Subject = 'Redefinição de Senha';
            $mail->Body = "Clique para redefinir: $link";
            $mail->send();
            */
            
            // Para teste (mostra o link na tela)
            $mensagem .= "<br><br>(Em produção, isto seria enviado por email)<br>Link: <a href='$link'>$link</a>";
            
        } else {
            $erro = "Email não encontrado.";
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
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; }
        .success { color: green; margin: 1rem 0; }
        .error { color: red; margin: 1rem 0; }
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
                <p>Digite seu email para receber o link de redefinição:</p>
                <input type="email" name="email" required>
                <button type="submit">Enviar Link</button>
            </form>
        <?php endif; ?>
        
        <p><a href="login.php">Voltar ao Login</a></p>
    </div>
</body>
</html>