<?php
require_once 'funcoes.php';

// Verifica se está logado (opcional - pode remover se não for necessário)
@session_start();

// Busca os clientes
$clientes = buscarClientes(); // Você precisará criar esta função em funcoes.php
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Clientes - PedidoPronto</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #f94144;
            --light: #f8f9fa;
            --dark: #212529;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: var(--primary);
            color: white;
            padding: 15px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        h1, h2 {
            font-weight: 500;
        }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background-color: var(--secondary);
        }

        .btn.logout {
            background-color: var(--danger);
        }

        .btn.logout:hover {
            background-color: #c82333;
        }

        .btn.add {
            background-color: var(--success);
            margin-bottom: 20px;
        }

        .btn.add:hover {
            background-color: #3aa8c4;
        }

        .clientes-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .clientes-table th, 
        .clientes-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .clientes-table th {
            background-color: #f8f9fa;
            font-weight: 500;
            color: #666;
        }

        .clientes-table tr:hover {
            background-color: #f8f9fa;
        }

        .actions-cell {
            width: 50px;
            text-align: center;
        }

        .card-menu-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 1.2em;
            padding: 0 5px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .card-menu-btn:hover {
            background-color: #e9ecef;
        }

        .card-dropdown {
            position: absolute;
            right: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 100;
            display: none;
            min-width: 120px;
        }

        .card-dropdown.show {
            display: block;
        }

        .card-dropdown button {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 8px 12px;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }

        .card-dropdown button:hover {
            background-color: #f8f9fa;
        }

        .card-dropdown button.edit {
            color: var(--primary);
        }

        .card-dropdown button.delete {
            color: var(--danger);
        }

        .notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            display: none;
            z-index: 1100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .notification.success {
            background-color: var(--success);
        }

        .notification.error {
            background-color: var(--danger);
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-buttons {
                width: 100%;
                justify-content: center;
            }
            
            .clientes-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1><a href="index.php" style="color: white; text-decoration: none;">PedidoPronto</a></h1>
            <div class="header-buttons">
                <button class="btn" onclick="location.href='index_gerente.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button class="btn" onclick="location.href='mostrarcardapio.php'">
                    <i class="fas fa-utensils"></i> Cardápio
                </button>
                <button class="btn" onclick="location.href='historicopedidos.php'">
                    <i class="fas fa-history"></i> Histórico
                </button>
                <button class="btn logout" onclick="location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>
        </div>
    </header>

    <div class="container">
        <button class="btn add" onclick="location.href='adicionarcliente.php'">
            <i class="fas fa-plus"></i> Adicionar Cliente
        </button>

        <h2>Clientes Registrados</h2>

        <?php if (!empty($clientes)): ?>
            <table class="clientes-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Endereço</th>
                        <th>Data de Cadastro</th>
                        <th class="actions-cell">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= htmlspecialchars($cliente['id']) ?></td>
                            <td><?= htmlspecialchars($cliente['nome']) ?></td>
                            <td><?= htmlspecialchars($cliente['telefone']) ?></td>
                            <td><?= htmlspecialchars($cliente['endereco']) ?></td>
                            <td><?= htmlspecialchars($cliente['data_cadastro']) ?></td>
                            <td class="actions-cell">
                                <div style="position: relative;">
                                    <button class="card-menu-btn" onclick="toggleDropdown(event, this)">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="card-dropdown">
                                        <button class="edit" onclick="editarCliente(<?= $cliente['id'] ?>)">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button class="delete" onclick="confirmarExclusao(<?= $cliente['id'] ?>)">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-message">
                <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>Nenhum cliente cadastrado ainda.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        // Função para mostrar/ocultar o dropdown de ações
        function toggleDropdown(event, button) {
            event.stopPropagation();
            const dropdown = button.nextElementSibling;
            const allDropdowns = document.querySelectorAll('.card-dropdown');
            
            allDropdowns.forEach(dd => {
                if (dd !== dropdown) dd.classList.remove('show');
            });
            
            dropdown.classList.toggle('show');
        }

        // Fechar dropdowns ao clicar fora
        document.addEventListener('click', function() {
            document.querySelectorAll('.card-dropdown').forEach(dd => {
                dd.classList.remove('show');
            });
        });

        // Função para editar cliente
        function editarCliente(clienteId) {
            showNotification('Abrindo cliente #' + clienteId + ' para edição...', 'success');
            window.location.href = 'adicionarcliente.php?editar=' + clienteId;
        }

        // Função para confirmar exclusão
        function confirmarExclusao(clienteId) {
            if (confirm(`Tem certeza que deseja excluir este cliente?`)) {
                fetch('funcoes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        acao: 'excluir_cliente',
                        cliente_id: clienteId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => location.reload(), 1500);
                    }
                });
            }
        }

        // Função para mostrar notificação
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>