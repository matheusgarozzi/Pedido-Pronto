<?php
require_once 'funcoes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'excluir') {
        $id = $_POST['id'] ?? 0;
        excluirPedido($id);
        exit;
    }

    if ($action === 'editar') {
        $id = $_POST['id'] ?? 0;
        $novoProduto = $_POST['produto_id'] ?? null;
        editarPedidoProduto($id, $novoProduto);
        exit;
    }
}

$pedidos = buscarPedidos();
$produtos = buscarProdutos();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pedidos - Gerente</title>
    <link rel="stylesheet" href="estilo.css">
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</head>
<body>

<h2>Painel de Pedidos (Kanban)</h2>

<div class="kanban-board">
    <!-- Coluna: Entregue (exemplo) -->
    <div class="kanban-column" id="entregue" ondragover="allowDrop(event)" ondrop="drop(event, 'entregue')">
        <div class="column-header">
            <h3 class="column-title">Entregue</h3>
            <span class="badge-count"><?= count(array_filter($pedidos, fn($p) => $p['status'] === 'entregue')) ?></span>
        </div>

        <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'entregue'): ?>
        <div class="card" draggable="true" ondragstart="drag(event)" id="pedido-<?= $pedido['id'] ?>">
            <div class="card-header">
                <span class="card-title">Pedido #<?= $pedido['id'] ?></span>
                <div class="card-actions">
                    <button class="card-menu-btn" onclick="toggleDropdown(event, this)">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="card-dropdown">
                        <button class="edit" onclick="editarPedido(<?= $pedido['id'] ?>)">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button class="delete" onclick="excluirPedido(<?= $pedido['id'] ?>)">
                            <i class="fas fa-times"></i> Excluir
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?></p>
                <p><strong>Itens:</strong> <?= htmlspecialchars($pedido['produtos']) ?></p>
                <p><strong>Total:</strong> R$ <?= number_format($pedido['total'], 2, ',', '.') ?></p>
                <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
            </div>
        </div>
        <?php endif; endforeach; ?>
    </div>
</div>

<!-- Modal de Edição -->
<div class="modal" id="modalEditarPedido">
    <div class="modal-content">
        <h3>Editar Pedido</h3>
        <input type="hidden" id="edit_pedido_id">
        <form id="formEditarPedido">
            <label for="produto_id">Novo Produto:</label>
            <select id="produto_id" name="produto_id">
                <?php foreach ($produtos as $produto): ?>
                    <option value="<?= $produto['id'] ?>">
                        <?= $produto['nome'] ?> - R$<?= number_format($produto['preco'], 2, ',', '.') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>
            <button type="button" onclick="salvarEdicaoPedido()">Salvar Alterações</button>
            <button type="button" style="background: #ccc;" onclick="excluirPedido(document.getElementById('edit_pedido_id').value)">Cancelar Pedido</button>
        </form>
    </div>
</div>

<script>
function excluirPedido(id) {
    if (!confirm('Tem certeza que deseja excluir este pedido?')) return;

    const formData = new FormData();
    formData.append('action', 'excluir');
    formData.append('id', id);

    fetch('', {
        method: 'POST',
        body: formData
    }).then(res => {
        if (res.ok) {
            location.reload();
        } else {
            alert('Erro ao excluir o pedido.');
        }
    });
}

function editarPedido(id) {
    document.getElementById('edit_pedido_id').value = id;
    document.getElementById('modalEditarPedido').style.display = 'block';
}

function salvarEdicaoPedido() {
    const id = document.getElementById('edit_pedido_id').value;
    const produtoId = document.getElementById('produto_id').value;

    const formData = new FormData();
    formData.append('action', 'editar');
    formData.append('id', id);
    formData.append('produto_id', produtoId);

    fetch('', {
        method: 'POST',
        body: formData
    }).then(res => {
        if (res.ok) {
            location.reload();
        } else {
            alert('Erro ao editar o pedido.');
        }
    });
}

function fecharModalEditar() {
    document.getElementById('modalEditarPedido').style.display = 'none';
}
</script>

<style>
/* Estilos básicos do Kanban e Modal */
.kanban-board {
    display: flex;
    gap: 20px;
}
.kanban-column {
    background: #f0f0f0;
    padding: 10px;
    border-radius: 8px;
    width: 300px;
}
.card {
    background: #fff;
    border-radius: 5px;
    padding: 10px;
    margin-bottom: 10px;
    box-shadow: 0 2px 4px #ccc;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.card-dropdown {
    display: none;
    position: absolute;
    background: #fff;
    border: 1px solid #ccc;
    right: 0;
}
.card-menu-btn:hover + .card-dropdown,
.card-dropdown:hover {
    display: block;
}
.modal {
    display: none;
    position: fixed;
    top: 20%;
    left: 35%;
    width: 30%;
    background: #fff;
    padding: 20px;
    border: 2px solid #333;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    z-index: 999;
}
</style>

</body>
</html>
