<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "PedidoProntoDB";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Atualizar cliente
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['editar_id'])) {
    $id = intval($_POST['editar_id']);
    $nome = $conn->real_escape_string($_POST['editar_nome']);
    $telefone = $conn->real_escape_string($_POST['editar_telefone']);
    $endereco = $conn->real_escape_string($_POST['editar_endereco']);

    $sql = "UPDATE Clientes SET nome=?, telefone=?, endereco=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $nome, $telefone, $endereco, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: clientes.php?sucesso=1");
    exit;
}

// Excluir cliente
if (isset($_GET['excluir'])) {
    $idExcluir = intval($_GET['excluir']);
    
    // Verifica se existem pedidos relacionados ao cliente
    $result = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE cliente_id = $idExcluir");
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        // Impede exclusão e redireciona com erro
        header("Location: clientes.php?erro_exclusao=1");
        exit;
    } else {
        // Exclui o cliente
        $conn->query("DELETE FROM Clientes WHERE id = $idExcluir");
        header("Location: clientes.php?excluido=1");
        exit;
    }
}

// Buscar todos os clientes
$clientes = $conn->query("SELECT * FROM Clientes");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - PedidoPronto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --gray: #95a5a6;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
        }

        header {
            background: linear-gradient(135deg, var(--dark), #1a2530);
            color: var(--white);
            padding: 15px 20px;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 100%;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        h1 {
            font-size: 1.8rem;
            margin-bottom: 0;
            font-weight: 600;
            color: var(--white);
        }

        .header-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            text-decoration: none;
            color: var(--white);
            font-size: 0.9rem;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn.primary {
            background-color: var(--primary);
        }

        .btn.primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn.success {
            background-color: var(--secondary);
        }

        .btn.success:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .btn.danger {
            background-color: var(--danger);
        }

        .btn.danger:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .btn.warning {
            background-color: var(--warning);
        }

        .btn.logout {
            background-color: var(--gray);
        }

        .btn.logout:hover {
            background-color: #7f8c8d;
            transform: translateY(-2px);
        }

        .btn:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 25px 0;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            color: var(--dark);
            font-weight: 700;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-title i {
            color: var(--primary);
        }

        .action-buttons {
            display: flex;
            gap: 12px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        th {
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
        }

        tbody tr {
            border-bottom: 1px solid #e1e5eb;
            transition: background-color 0.2s;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        tbody tr.editing {
            background-color: #e8f4fc;
        }

        td {
            padding: 15px 20px;
            color: #34495e;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e1e5eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }
        
        input[type="text"]:focus, textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: white;
        }

        .btn-action {
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit {
            background-color: #3498db;
            color: white;
            border: none;
        }

        .btn-edit:hover {
            background-color: #2980b9;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
        }

        .btn-delete:hover {
            background-color: #c0392b;
        }

        .btn-save {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
        }

        .btn-save:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .mensagens {
            margin: 20px 0;
        }

        .mensagem {
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .mensagem-sucesso {
            background-color: var(--secondary);
            color: white;
        }
        
        .mensagem-erro {
            background-color: var(--danger);
            color: white;
        }

        .notification {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: var(--secondary);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            display: none;
            z-index: 9999;
            font-weight: 500;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease;
        }
        
        .notification.error {
            background-color: var(--danger);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-top: 20px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--gray);
            margin-bottom: 15px;
        }

        .header-buttons .btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(4px);
        }
        
        .header-buttons .btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.35);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-buttons {
                width: 100%;
                justify-content: center;
            }
            
            .header-buttons .btn {
                flex: 1;
                min-width: 120px;
                justify-content: center;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .action-buttons {
                width: 100%;
                justify-content: center;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            thead, tbody, th, td, tr {
                display: block;
            }
            
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            tr {
                border: 1px solid #e1e5eb;
                border-radius: 8px;
                margin-bottom: 15px;
                padding: 10px;
            }
            
            td {
                border: none;
                border-bottom: 1px solid #e1e5eb;
                position: relative;
                padding-left: 50%;
            }
            
            td:before {
                position: absolute;
                top: 15px;
                left: 20px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: 600;
                color: var(--dark);
            }
            
            td:nth-of-type(1):before { content: "Nome"; }
            td:nth-of-type(2):before { content: "Telefone"; }
            td:nth-of-type(3):before { content: "Endereço"; }
            td:nth-of-type(4):before { content: "Ações"; }
            
            .btn-action {
                margin-top: 10px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1><a href="index_gerente.php" style="color: white; text-decoration: none;"><i class="fas fa-utensils"></i> PedidoPronto</a></h1>
            <div class="header-buttons">
                <button class="btn primary" onclick="location.href='../Gerente/index_gerente.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button class="btn warning" onclick="location.href='../Pedidos/historicopedidos.php'">
                    <i class="fas fa-history"></i> Histórico
                </button>
                <button class="btn" onclick="location.href='../Clientes/clientes.php'">
                    <i class="fas fa-users"></i> Clientes
                </button>
                <button class="btn logout" onclick="location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2 class="page-title"><i class="fas fa-users"></i> Gerenciamento de Clientes</h2>
            <div class="action-buttons">
                <button class="btn success" onclick="location.href='adicionarcliente.php'">
                    <i class="fas fa-plus"></i> Adicionar Cliente
                </button>
            </div>
        </div>

        <div class="mensagens">
            <?php if (isset($_GET['sucesso'])): ?>
                <div class="mensagem mensagem-sucesso">
                    <i class="fas fa-check-circle"></i> Cliente atualizado com sucesso!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['excluido'])): ?>
                <div class="mensagem mensagem-sucesso">
                    <i class="fas fa-check-circle"></i> Cliente excluído com sucesso!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['erro_exclusao'])): ?>
                <div class="mensagem mensagem-erro">
                    <i class="fas fa-exclamation-circle"></i> Erro: Não é possível excluir cliente que possui pedidos associados.
                </div>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <?php if ($clientes->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Endereço</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($cliente = $clientes->fetch_assoc()): ?>
                        <?php if (isset($_GET['editar']) && $_GET['editar'] == $cliente['id']): ?>
                            <form method="POST">
                                <input type="hidden" name="editar_id" value="<?= $cliente['id'] ?>">
                                <tr class="editing">
                                    <td>
                                        <input type="text" name="editar_nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" name="editar_telefone" value="<?= htmlspecialchars($cliente['telefone']) ?>">
                                    </td>
                                    <td>
                                        <textarea name="editar_endereco" rows="2"><?= htmlspecialchars($cliente['endereco']) ?></textarea>
                                    </td>
                                    <td>
                                        <button type="submit" class="btn-save">
                                            <i class="fas fa-save"></i> Salvar
                                        </button>
                                    </td>
                                </tr>
                            </form>
                        <?php else: ?>
                            <tr>
                                <td><?= htmlspecialchars($cliente['nome']) ?></td>
                                <td><?= htmlspecialchars($cliente['telefone']) ?></td>
                                <td><?= htmlspecialchars($cliente['endereco']) ?></td>
                                <td style="display: flex; gap: 10px;">
                                    <a href="?editar=<?= $cliente['id'] ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="?excluir=<?= $cliente['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Tem certeza que deseja excluir este cliente?');">
                                        <i class="fas fa-trash"></i> Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <h3>Nenhum cliente cadastrado</h3>
                    <p>Adicione clientes para começar a gerenciar</p>
                    <button class="btn success" onclick="location.href='adicionarcliente.php'" style="margin-top: 20px;">
                        <i class="fas fa-plus"></i> Adicionar Primeiro Cliente
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

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
        
        // Fechar mensagens após 5 segundos
        setTimeout(() => {
            document.querySelectorAll('.mensagem').forEach(msg => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>