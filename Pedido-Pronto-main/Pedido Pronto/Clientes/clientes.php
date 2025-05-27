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
    $conn->query("DELETE FROM Clientes WHERE id = $idExcluir");
    header("Location: clientes.php?excluido=1");
    exit;
}

// Buscar todos os clientes
$clientes = $conn->query("SELECT * FROM Clientes");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cardápio - PedidoPronto</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        table {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

          th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background-color: #3b82f6;
            color: #ffffff;
        }

        tr:last-child td {
            border-bottom: none;
        }

        input[type="text"],
        input[type="tel"],
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        button, a.btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: background-color 0.2s ease;
            display: inline-block;
            margin-right: 6px;
        }

        button:hover, a.btn:hover {
            background-color: #2563eb;
        }

        a.btn.delete {
            background-color: #ef4444;
        }

        a.btn.delete:hover {
            background-color: #dc2626;
        }

        @media (max-width: 640px) {
            th, td {
                font-size: 14px;
                padding: 10px;
            }

            button, a.btn {
                padding: 6px 12px;
                font-size: 13px;
            }
        }
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

        .cardapio-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .produto-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            position: relative;
        }

        .produto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .produto-imagem {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .produto-info {
            padding: 15px;
        }

        .produto-nome {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .produto-descricao {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .produto-preco {
            font-weight: bold;
            color: var(--success);
            font-size: 16px;
        }

        .produto-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }

        .card-menu-btn {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .card-menu-btn:hover {
            background: white;
        }

        .card-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
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

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-buttons {
                width: 100%;
                justify-content: center;
            }
            
            .cardapio-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
        <header>
        <div class="header-content">
            <h1><a href="index_gerente.php" style="color: white; text-decoration: none;">PedidoPronto</a></h1>
            <div class="header-buttons">
                <button class="btn" onclick="location.href='index_gerente.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button class="btn" onclick="location.href='historicopedidos.php'">
                    <i class="fas fa-history"></i> Histórico
                </button>
                <button class="btn" onclick="location.href='clientes.php'">
                    <i class="fas fa-users"></i> Clientes
                </button>
                <button class="btn logout" onclick="location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>
        </div>
    </header>

<h1>Clientes</h1>

<?php if (isset($_GET['sucesso'])): ?>
    <div class="success">Cliente atualizado com sucesso!</div>
<?php endif; ?>

<?php if (isset($_GET['excluido'])): ?>
    <div class="deleted">Cliente excluído com sucesso!</div>
<?php endif; ?>

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
                <tr>
                    <td><input type="text" name="editar_nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required></td>
                    <td><input type="text" name="editar_telefone" value="<?= htmlspecialchars($cliente['telefone']) ?>"></td>
                    <td><textarea name="editar_endereco"><?= htmlspecialchars($cliente['endereco']) ?></textarea></td>
                    <td><button type="submit">Salvar</button></td>
                </tr>
            </form>
        <?php else: ?>
            <tr>
                <td><?= htmlspecialchars($cliente['nome']) ?></td>
                <td><?= htmlspecialchars($cliente['telefone']) ?></td>
                <td><?= htmlspecialchars($cliente['endereco']) ?></td>
                <td>
                    <a href="?editar=<?= $cliente['id'] ?>" class="btn">Editar</a>
                    <a href="?excluir=<?= $cliente['id'] ?>" class="btn delete" onclick="return confirm('Tem certeza que deseja excluir este cliente?');">Excluir</a>
                </td>
            </tr>
        <?php endif; ?>
    <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
