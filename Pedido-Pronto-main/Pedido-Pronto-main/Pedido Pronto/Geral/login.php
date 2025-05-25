<?php
session_start();
require 'conexao.php'; // Certifique-se que este arquivo existe e está correto

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Consulta segura usando prepared statements
    $sql = "SELECT id, username, senha, nivel_acesso FROM Usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param('s', $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verifica a senha usando SHA-256
            $senhaHash = hash('sha256', $senha);
            
            if ($senhaHash === $user['senha']) {
                // Configura a sessão
                $_SESSION['usuario'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'nivel_acesso' => $user['nivel_acesso']
                ];

                // Redireciona conforme o nível de acesso
                switch ($user['nivel_acesso']) {
                    case 'admin':
                        header('Location: index_admin.php');
                        break;
                    case 'gerente':
                        header('Location: index_gerente.php');
                        break;
                    case 'atendente':
                        header('Location: index_atendente.php');
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
        $stmt->close();
    } else {
        $erro = "Erro na conexão com o banco de dados!";
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login - PedidoPronto</title>
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
        .login-container {
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
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (!empty($erro)): ?>
            <div class="error"><?= $erro ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="usuario" placeholder="Usuário" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>