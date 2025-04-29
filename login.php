<?php
session_start();


$usuarios = [
    'admin' => ['senha' => 'admin123', 'cargo' => 'admin'],
    'gerente' => ['senha' => 'gerente123', 'cargo' => 'gerente'],
    'atendente' => ['senha' => 'atendente123', 'cargo' => 'atendente']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (isset($usuarios[$usuario]) && $usuarios[$usuario]['senha'] === $senha) {
        $_SESSION['usuario'] = $usuario;
        $_SESSION['cargo'] = $usuarios[$usuario]['cargo'];
        header('Location: index.php');
        exit;
    } else {
        $erro = "Usuário ou senha inválidos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if (!empty($erro)) echo "<p style='color:red;'>$erro</p>"; ?>
    <form method="post">
        <input type="text" name="usuario" placeholder="Usuário" required><br>
        <input type="password" name="senha" placeholder="Senha" required><br>
        <button type="submit">Entrar</button>
    </form>
</body>
</html>
