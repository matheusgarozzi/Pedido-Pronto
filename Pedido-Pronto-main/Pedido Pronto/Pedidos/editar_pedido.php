<?php
require_once '../Geral/funcoes.php';

// Verifica se o ID do pedido foi passado
if (!isset($_GET['id'])) {  // Faltava o parêntese aqui
    header('Location: ../Gerente/index_gerente.php');
    exit;
}

$pedido_id = $_GET['id'];

// Busca os dados do pedido
$pedido = buscarPedidos($pedido_id);
if (!$pedido) {
    header('Location: ../Gerente/index_gerente.php');
    exit;
}

// Busca clientes e produtos para os selects
$clientes = buscarClientes();
$produtos = buscarProdutos();

// Processa o formulário se for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $_POST['cliente_id'];
    $produto_id = $_POST['produto_id'];
    $quantidade = $_POST['quantidade'];
    
    // Atualiza o pedido no banco de dados
    if (atualizarPedido($pedido_id, $cliente_id, $produto_id, $quantidade)) {
        header('Location: index_gerente.php?success=1');
        exit;
    } else {
        $erro = "Erro ao atualizar o pedido!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Pedido #<?= $pedido_id ?></title>
    <link rel="stylesheet" href="stylegerente.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2><i class="fas fa-edit"></i> Editar Pedido #<?= $pedido_id ?></h2>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label for="cliente_id" class="form-label">Cliente</label>
                <select id="cliente_id" name="cliente_id" class="form-select" required>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id'] ?>" <?= $cliente['id'] == $pedido['cliente_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cliente['nome']) ?> - <?= $cliente['telefone'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="produto_id" class="form-label">Produto</label>
                <select id="produto_id" name="produto_id" class="form-select" required>
                    <?php foreach ($produtos as $produto): ?>
                        <option value="<?= $produto['id'] ?>" <?= $produto['id'] == $pedido['produto_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($produto['nome']) ?> - R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="quantidade" class="form-label">Quantidade</label>
                <input type="number" id="quantidade" name="quantidade" class="form-control" min="1" value="<?= $pedido['quantidade'] ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
            <a href="index_gerente.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </form>
    </div>
</body>
</html>