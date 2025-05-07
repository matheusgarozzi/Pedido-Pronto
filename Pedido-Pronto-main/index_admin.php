<?php
session_start();
require 'verificalog.php';

if ($_SESSION['usuario']['nivel_acesso'] !== 'admin') {
    header('Location: acessoneg.php');
    exit;
}

// Busca dados do caixa
require 'conexao.php';
$caixa = $conn->query("SELECT * FROM Caixa ORDER BY id DESC LIMIT 1")->fetch_assoc();
$usuarios = $conn->query("SELECT id, username, nivel_acesso FROM Usuarios");
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Admin - PedidoPronto</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background-color: #f5f7fa;
        }
        header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-buttons {
            display: flex;
            gap: 10px;
        }
        .btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .logout {
            background-color: #e74c3c;
        }
        .dashboard {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <header>
        <h1>Painel Administrativo</h1>
        <div class="header-buttons">
            <button class="btn" onclick="location.href='cadastrousu.php'">Usuários</button>
            <button class="btn" onclick="location.href='caixa.php'">Caixa</button>
            <button class="btn logout" onclick="location.href='logout.php'">Sair</button>
        </div>
    </header>

    <div class="dashboard">
        <!-- Seção Caixa -->
        <div class="card">
            <h2>Controle do Caixa</h2>
            <p>Status: <strong><?= $caixa['status'] ?? 'fechado' ?></strong></p>
            <button onclick="abrirCaixa()">Abrir Caixa</button>
            <button onclick="fecharCaixa()">Fechar Caixa</button>
        </div>

        <!-- Seção Usuários -->
        <div class="card">
            <h2>Usuários Cadastrados</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Usuário</th>
                    <th>Cargo</th>
                </tr>
                <?php while($user = $usuarios->fetch_assoc()): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= ucfirst($user['nivel_acesso']) ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
            <button onclick="location.href='cadastro_usuario.php'">Novo Usuário</button>
        </div>
    </div>

    <script>
        function abrirCaixa() {
            const valor = prompt("Informe o saldo inicial:");
            if (valor) {
                fetch('gerenciar_caixa.php?acao=abrir&valor=' + valor)
                    .then(() => location.reload());
            }
        }

        function fecharCaixa() {
            if (confirm('Deseja fechar o caixa?')) {
                fetch('gerenciar_caixa.php?acao=fechar')
                    .then(() => location.reload());
            }
        }
    </script>
</body>
</html>