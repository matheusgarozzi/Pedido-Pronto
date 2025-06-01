<?php
require_once '../Geral/conexao.php';
require_once '../Geral/funcoes.php'; // Changed to 'Geral' for consistency

@session_start();

// Ensure buscarProdutos() fetches the 'estoque' column
$produtos = buscarProdutosAtivos();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Estoque - PedidoPronto</title>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #f94144;
            --light: #f8f9fa;
            --dark: #212529; /* Assuming this is your header dark color */
            --white: #ffffff; /* Added for consistency */
            --shadow: 0 2px 10px rgba(0,0,0,0.1); /* Added for consistency */
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--light);
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            box-shadow: var(--shadow);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }
        th {
            background-color: var(--primary);
            color: var(--white);
        }
        tr:last-child td {
            border-bottom: none;
        }
        /* General button style for consistency */
        button {
            background-color: var(--primary);
            border: none;
            color: var(--white);
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: 500;
        }
        button:hover {
            background-color: var(--secondary);
        }
        /* Notification styles */
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #28a745; /* Green for success, consider adding a --success-alt or similar */
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            display: none;
            font-weight: 600;
            z-index: 1000;
        }
        .notification.error {
            background-color: var(--danger);
        }
        /* Header styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: var(--white);
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        .header h2 {
            margin: 0;
            color: var(--dark);
        }
        .header button {
            background-color: var(--primary);
            border: none;
            color: var(--white);
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .header button:hover {
            background-color: var(--secondary);
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Estoque</h2>
        <button onclick="location.href='mostrarcardapio.php'">Voltar para Cardápio</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Estoque Atual</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($produtos)): ?>
                <?php foreach ($produtos as $produto): ?>
                    <tr>
                        <td><?= htmlspecialchars($produto['id']) ?></td>
                        <td><?= htmlspecialchars($produto['nome']) ?></td>
                        <td>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                        <td><?= isset($produto['estoque']) ? intval($produto['estoque']) : 0 ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">Nenhum produto encontrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="notification" id="notification"></div>

    <script>
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + (type === 'error' ? 'error' : '');
            notification.style.display = 'block';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>