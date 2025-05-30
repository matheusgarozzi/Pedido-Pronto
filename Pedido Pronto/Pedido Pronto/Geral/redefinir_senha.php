<?php
session_start();
require 'conexao.php';

$mensagem = '';
$erro = '';
$token_valido = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Verificar se o token é válido e não expirou
    $sql = "SELECT id FROM Usuarios WHERE reset_token = ? AND reset_token_expira > NOW()";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['reset_user_id'] = $user['id'];
            $token_valido = true;
        } else {
            $erro = "Token inválido ou expirado. Solicite um novo link de redefinição.";
        }
        $stmt->close();
    } else {
        $erro = "Erro na conexão com o banco de dados.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['reset_user_id'])) {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if ($nova_senha === $confirmar_senha) {
        // Hash da nova senha (usando SHA-256 como no seu login)
        $senha_hash = hash('sha256', $nova_senha);

        // Atualizar senha e limpar token
        $sql_update = "UPDATE Usuarios SET senha = ?, reset_token = NULL, reset_token_expira = NULL WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);

        if ($stmt_update) {
            $stmt_update->bind_param('si', $senha_hash, $_SESSION['reset_user_id']);
            $stmt_update->execute();

            if ($stmt_update->affected_rows === 1) {
                $mensagem = "Senha redefinida com sucesso! Você já pode fazer login com sua nova senha.";
                unset($_SESSION['reset_user_id']);
            } else {
                $erro = "Erro ao atualizar a senha.";
            }
            $stmt_update->close();
        } else {
            $erro = "Erro na conexão com o banco de dados.";
        }
    } else {
        $erro = "As senhas não coincidem.";
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinir senha - PedidoPronto</title>
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
        .reset-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 300px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        input {
            width: 100%;
            padding: 0.8rem;
            margin: 0.5rem 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 0.8rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 1rem;
        }
        .error {
            color: red;
            text-align: center;
            margin: 1rem 0;
        }
        .success {
            color: green;
            text-align: center;
            margin: 1rem 0;
        }
        .back-to-login {
            text-align: center;
            margin-top: 1rem;
        }
        .back-to-login a {
            color: #007bff;
            text-decoration: none;
        }
        .back-to-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>Redefinir senha</h2>
        <?php if (!empty($erro)): ?>
            <div class="error"><?= $erro ?></div>
        <?php endif; ?>
        <?php if (!empty($mensagem)): ?>
            <div class="success"><?= $mensagem ?></div>
            <div class="back-to-login">
                <a href="login.php">Voltar para login</a>
            </div>
        <?php elseif ($token_valido): ?>
            <form method="POST">
                <input type="password" name="nova_senha" placeholder="Nova senha" required>
                <input type="password" name="confirmar_senha" placeholder="Confirmar nova senha" required>
                <button type="submit">Redefinir senha</button>
            </form>
        <?php else: ?>
            <div class="back-to-login">
                <a href="esqueci_senha.php">Solicitar novo link</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>