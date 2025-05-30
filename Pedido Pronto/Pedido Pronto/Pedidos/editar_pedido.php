<?php
require_once '../Geral/funcoes.php';

// Verifica se o ID do pedido foi passado
if (!isset($_GET['id'])) {
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

// Busca apenas os produtos (não precisamos mais dos clientes)
$produtos = buscarProdutos();

// Processa o formulário se for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto_id = $_POST['produto_id'];
    $quantidade = (int)$_POST['quantidade'];
    
    // Validação simples do produto
    $produto_valido = false;
    foreach ($produtos as $p) {
        if ($p['id'] == $produto_id) {
            $produto_valido = true;
            break;
        }
    }
    
    if (!$produto_valido) {
        $erro = "Por favor, selecione um produto válido!";
    } else {
        try {
            if (editarPedidoProduto($pedido_id, $produto_id, $quantidade)) {
                header('Location: ../Gerente/index_gerente.php?success=1');
                exit;
            } else {
                $erro = "Falha ao atualizar o pedido";
            }
        } catch (Exception $e) {
            $erro = "Erro: " . $e->getMessage();
        }
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
                <label for="produto_id" class="form-label">Produto</label>
                <select id="produto_id" name="produto_id" class="form-select" required>
                    <?php foreach ($produtos as $produto): ?>
                        <option value="<?= $produto['id'] ?>" 
                            <?= ($produto['id'] == ($pedido['produto_id'] ?? 0)) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($produto['nome']) ?> - R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="quantidade" class="form-label">Quantidade</label>
                <input type="number" id="quantidade" name="quantidade" class="form-control" min="1" 
                       value="<?= $pedido['quantidade'] ?? 1 ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
            <a href="../Gerente/index_gerente.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </form>
    </div>
</body>
</html>