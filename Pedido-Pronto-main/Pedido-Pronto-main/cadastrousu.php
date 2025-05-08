<?php
// Inicia a sessão de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'verificalog.php';

// Verifica se é admin
if ($_SESSION['usuario']['nivel_acesso'] !== 'admin') {
    header("Location: acessoneg.php");
    exit();
}

// Conexão com o banco (não fechar ainda)
require 'conexao.php';

$erro = '';
$sucesso = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $senha = trim($_POST['senha']);
    $nivel = $_POST['nivel_acesso'];

    try {
        // Validação
        if (empty($username) || empty($senha)) {
            throw new Exception("Preencha todos os campos!");
        }

        if (strlen($senha) < 6) {
            throw new Exception("A senha deve ter no mínimo 6 caracteres!");
        }

        // Verifica se usuário já existe (usando prepared statements)
        $sql_check = "SELECT id FROM Usuarios WHERE username = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param('s', $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            throw new Exception("Nome de usuário já existe!");
        }

        // Criptografa a senha (melhor usar password_hash na prática)
        $senha_hash = hash('sha256', $senha);

        // Insere no banco
        $sql_insert = "INSERT INTO Usuarios 
                      (username, senha, nivel_acesso, data_criacao) 
                      VALUES (?, ?, ?, NOW())";
        
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param('sss', $username, $senha_hash, $nivel);
        
        if ($stmt_insert->execute()) {
            $sucesso = "Usuário cadastrado com sucesso!";
        } else {
            throw new Exception("Erro ao cadastrar usuário: " . $conn->error);
        }

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Busca usuários existentes (usando a mesma conexão)
$sql_usuarios = "SELECT id, username, nivel_acesso, data_criacao FROM Usuarios";
$result_usuarios = $conn->query($sql_usuarios);

// Só fecha a conexão no final do arquivo
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Usuário</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f3f4f6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        input, select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        table {
            width: 100%;
            margin-top: 2rem;
            border-collapse: collapse;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cadastrar Novo Usuário</h1>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nome de Usuário:</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Senha:</label>
                <input type="password" name="senha" required>
            </div>

            <div class="form-group">
                <label>Nível de Acesso:</label>
                <select name="nivel_acesso" required>
                    <option value="admin">Administrador</option>
                    <option value="gerente">Gerente</option>
                    <option value="atendente">Atendente</option>
                </select>
            </div>

            <button type="submit">Cadastrar</button>
        </form>

        <h2>Usuários Existentes</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Usuário</th>
                <th>Cargo</th>
                <th>Data Cadastro</th>
            </tr>
            <?php while($row = $result_usuarios->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars(ucfirst($row['nivel_acesso'])) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row['data_criacao'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>