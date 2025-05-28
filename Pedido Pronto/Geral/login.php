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
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #0069d9;
        }
        .error {
            color: red;
            margin: 1rem 0;
        }
        .forgot-password {
            margin-top: 15px;
        }
        .forgot-password a {
            color: #007bff;
            text-decoration: none;
        }
        .forgot-password a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (!empty($erro)): ?>
            <div class="error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="usuario" placeholder="Usuário" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit">Entrar</button>
            <div class="forgot-password">
                <a href="esqueci_senha.php">Esqueci minha senha</a>
            </div>
        </form>
    </div>
</body>
</html>