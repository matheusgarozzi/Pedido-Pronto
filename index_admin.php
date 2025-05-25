<?php
require_once __DIR__ . '/../Geral/verificalog.php';
require_once __DIR__ . '/../Geral/conexao.php';
require_once __DIR__ . '/funcoesAdmin.php';

// Verifica se o usuário é admin
if ($_SESSION['usuario']['nivel_acesso'] !== 'admin') {
    header('Location: ../Geral/acessoneg.php');
    exit;
}

// Obtém conexão do Database
$db = Database::getInstance();
$conn = $db->getConnection();

// Obtém dados
$caixa = AdminFunctions::obterUltimoCaixa();
$usuarios = AdminFunctions::obterUsuarios();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Admin - PedidoPronto</title>
    <link rel="stylesheet" href="styleAdmin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Cabeçalho -->
    <header>
        <h1>Painel Administrativo</h1>
        <div class="header-buttons">
            <button class="btn" onclick="location.href='cadastrousu.php'">Usuários</button>
            <button class="btn" onclick="location.href='caixa.php'">Caixa</button>
            <button class="btn logout" onclick="location.href='../Geral/logout.php'">Sair</button>
        </div>
    </header>

    <div class="dashboard">
        <!-- Seção Caixa -->
        <div class="card">
            <h2>Controle do Caixa</h2>
            <p>Status: <strong><?= htmlspecialchars($caixa['status'] ?? 'fechado') ?></strong></p>
            <button onclick="abrirCaixa()">Abrir Caixa</button>
            <button onclick="fecharCaixa()">Fechar Caixa</button>
        </div>

        <!-- Seção Usuários -->
        <div class="card">
            <h2>Usuários Cadastrados</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Cargo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($user['nivel_acesso'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button onclick="location.href='cadastrousu.php'">Novo Usuário</button>
        </div>
    </div>

    <script>
        function abrirCaixa() {
            const valor = prompt("Informe o saldo inicial:");
            if (valor) {
                fetch('gerenciar_caixa.php?acao=abrir&valor=' + valor)
                    .then(response => {
                        if (!response.ok) throw new Error('Erro na rede');
                        return response.json();
                    })
                    .then(() => location.reload())
                    .catch(error => alert('Erro: ' + error.message));
            }
        }

        function fecharCaixa() {
            if (confirm('Deseja fechar o caixa?')) {
                fetch('gerenciar_caixa.php?acao=fechar')
                    .then(response => {
                        if (!response.ok) throw new Error('Erro na rede');
                        return response.json();
                    })
                    .then(() => location.reload())
                    .catch(error => alert('Erro: ' + error.message));
            }
        }
    </script>
</body>
</html>