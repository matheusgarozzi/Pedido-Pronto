<?php
session_start();
require_once 'conexao.php';

$mensagem = '';
$erro = '';
$usuario_existe = false;

// Função para debug
function debug_log($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    
    debug_log("Iniciando recuperação para usuário: " . $username);
    
    try {
        // Verifica se a classe Database existe
        if (!class_exists('Database')) {
            throw new Exception("Classe Database não encontrada");
        }
        
        $conn = Database::getInstance()->getConnection();
        
        if (!$conn) {
            throw new Exception("Falha ao obter conexão com o banco de dados");
        }
        
        debug_log("Conexão com banco obtida com sucesso");
        
        // Verifica se a tabela existe
        $table_check = $conn->query("SHOW TABLES LIKE 'Usuarios'");
        if ($table_check->num_rows === 0) {
            throw new Exception("Tabela Usuarios não encontrada no banco de dados");
        }
        
        debug_log("Tabela Usuarios encontrada");
        
        // Consulta segura com tratamento de case
        $sql = "SELECT id, username, senha FROM Usuarios WHERE username COLLATE utf8_general_ci = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Erro ao preparar consulta: " . $conn->error);
        }
        
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        debug_log("Consulta executada. Linhas encontradas: " . $result->num_rows);
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            debug_log("Usuário encontrado: ID " . $user['id'] . " - " . $user['username']);
            
            $usuario_existe = true;
            $_SESSION['reset_user_id'] = $user['id'];
        } else {
            $erro = "Usuário não encontrado. Verifique se digitou corretamente.";
            debug_log("Usuário não encontrado: " . $username);
            
            // Sugestões de usuários similares (opcional)
            $sql_similar = "SELECT username FROM Usuarios WHERE username LIKE ? LIMIT 3";
            $stmt_similar = $conn->prepare($sql_similar);
            $like_username = "%$username%";
            $stmt_similar->bind_param('s', $like_username);
            $stmt_similar->execute();
            $similar_result = $stmt_similar->get_result();
            
            if ($similar_result->num_rows > 0) {
                $erro .= "<br>Você quis dizer: ";
                $suggestions = [];
                while ($row = $similar_result->fetch_assoc()) {
                    $suggestions[] = $row['username'];
                }
                $erro .= implode(", ", $suggestions);
            }
        }
    } catch (Exception $e) {
        $erro = "Erro no sistema. Por favor, tente novamente mais tarde.";
        debug_log("ERRO: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha</title>
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
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .success { 
            color: green; 
            margin: 1rem 0; 
        }
        .error { 
            color: red; 
            margin: 1rem 0; 
        }
        .back-link {
            display: block;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Redefinir Senha</h2>
        
        <?php if (!empty($erro)): ?>
            <div class="error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($mensagem)): ?>
            <div class="success"><?= htmlspecialchars($mensagem) ?></div>
            <a href="login.php" class="back-link">Voltar ao Login</a>
        <?php elseif (!$usuario_existe): ?>
            <form method="POST">
                <p>Digite seu nome de usuário:</p>
                <input type="text" name="username" required placeholder="Nome de usuário">
                <button type="submit">Verificar Usuário</button>
            </form>
            <a href="login.php" class="back-link">Voltar ao Login</a>
        <?php else: ?>
            <form method="POST">
                <p>Digite sua nova senha:</p>
                <input type="password" name="nova_senha" required placeholder="Nova senha (mínimo 6 caracteres)">
                <button type="submit">Redefinir Senha</button>
            </form>
            <a href="login.php" class="back-link">Cancelar</a>
        <?php endif; ?>
    </div>
</body>
</html>