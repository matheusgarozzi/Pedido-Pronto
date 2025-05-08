<?php
session_start();
require 'verificalog.php';

if ($_SESSION['usuario']['nivel_acesso'] !== 'admin') {
    header('Location: acessoneg.php');
    exit;
}
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
            font-family: 'Inter', sans-serif;
            margin: 0;
            background-color: #eef1f5;
        }
        header {
            background-color: #1e2a38;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-buttons .btn {
            background-color: #2980b9;
            color: white;
            border: none;
            padding: 10px 18px;
            margin-left: 10px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }
        .logout {
            background-color: #c0392b !important;
        }
        .dashboard {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 30px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            flex: 1 1 400px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        h2 {
            margin-top: 0;
            color: #2c3e50;
        }
        button {
            background-color: #3498db;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            margin-top: 10px;
            cursor: pointer;
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