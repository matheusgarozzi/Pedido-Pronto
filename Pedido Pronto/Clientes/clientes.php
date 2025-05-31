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
    <title>Cardápio - PedidoPronto</title>
    <link rel="stylesheet" href="Styleclientes.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1><a href="index_gerente.php" style="color: white; text-decoration: none;">PedidoPronto</a></h1>
            <div class="header-buttons">
                <button class="btn" onclick="location.href='../Gerente/index_gerente.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button class="btn" onclick="location.href='../Pedidos/historicopedidos.php'">
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

<h1>Clientes</h1>

<div class="container">
    <button class="btn add-client" onclick="location.href='adicionarcliente.php'">
        <i class="fas fa-plus"></i> Adicionar Cliente
    </button>
</div>

<?php if (isset($_GET['sucesso'])): ?>
    <div class="success">Cliente atualizado com sucesso!</div>
<?php endif; ?>

<?php if (isset($_GET['excluido'])): ?>
    <div class="deleted">Cliente excluído com sucesso!</div>
<?php endif; ?>

<?php if (isset($_GET['erro_exclusao'])): ?>
    <div class="error">Erro: Não é possível excluir cliente que possui pedidos associados.</div>
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

