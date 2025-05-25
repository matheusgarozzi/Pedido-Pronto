<?php
require_once '../Geral/verificalog.php';
require_once '../Geral/conexao.php';
require_once 'funcoesGerente.php';

// Processa requisições POST (atualizações de status, cancelamentos, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $response = ['success' => false, 'message' => ''];

    if (isset($data['acao'])) {
        try {
            switch ($data['acao']) {
                case 'atualizar-status':
                    $success = FuncoesGerente::atualizarStatus($data['pedido_id'], $data['status']);
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Status atualizado!' : 'Erro ao atualizar status'
                    ];
                    break;

                case 'cancelar-pedido':
                    $success = FuncoesGerente::cancelarPedido($data['pedido_id']);
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Pedido cancelado!' : 'Erro ao cancelar pedido'
                    ];
                    break;

                case 'abrir-caixa':
                    $valor = floatval($data['valor']);
                    if ($valor <= 0) {
                        throw new Exception("Valor inválido para abertura de caixa");
                    }
                    $success = FuncoesGerente::gerenciarCaixa('abrir', $valor);
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Caixa aberto com sucesso!' : 'Erro ao abrir caixa'
                    ];
                    break;

                case 'fechar-caixa':
                    $success = FuncoesGerente::gerenciarCaixa('fechar');
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Caixa fechado com sucesso!' : 'Erro ao fechar caixa'
                    ];
                    break;

                default:
                    $response['message'] = 'Ação não reconhecida';
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
    }
    echo json_encode($response);
    exit;
}

// Busca dados para a página
$pedidos = FuncoesGerente::buscarPedidos();
$clientes = FuncoesGerente::buscarClientes();
$produtos = FuncoesGerente::buscarProdutos();
$caixa = FuncoesGerente::buscarStatusCaixa();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>PedidoPronto - Gerente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="stylegerente.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>PedidoPronto - Gerente</h1>
            <div class="header-buttons">
                <button class="btn" onclick="openModal('pedido')">
                    <i class="fas fa-plus"></i> Novo Pedido
                </button>
                <button class="btn" onclick="location.href='../Cardapio/mostrarcardapio.php'">
                    <i class="fas fa-utensils"></i> Cardápio
                </button>
                <button class="btn" onclick="location.href='../Pedidos/historicopedidos.php'">
                    <i class="fas fa-history"></i> Histórico
                </button>
                <button class="btn" onclick="location.href='../Clientes/clientes.php'">
                    <i class="fas fa-users"></i> Clientes
                </button>
                <button class="btn logout" onclick="location.href='../geral/logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Seção do Caixa -->
        <div class="caixa-info">
            <div class="caixa-status">
                <h2>Situação do Caixa</h2>
                <button class="btn" onclick="abrirCaixa()">Abrir Caixa</button>
                <button class="btn" onclick="fecharCaixa()">Fechar Caixa</button>
                <span class="status-badge status-<?= $caixa['status'] ?>">
                    <?= strtoupper($caixa['status']) ?>
                </span>
            </div>
            <div class="caixa-valores">
                <div class="caixa-valor">
                    <span>Saldo Inicial</span>
                    <p>R$ <?= number_format($caixa['saldo_inicial'], 2, ',', '.') ?></p>
                </div>
                <div class="caixa-valor">
                    <span>Entradas</span>
                    <p>R$ <?= number_format($caixa['entradas'], 2, ',', '.') ?></p>
                </div>
                <div class="caixa-valor">
                    <span>Saídas</span>
                    <p>R$ <?= number_format($caixa['saidas'], 2, ',', '.') ?></p>
                </div>
                <div class="caixa-valor">
                    <span>Saldo Atual</span>
                    <p>R$ <?= number_format($caixa['saldo_atual'], 2, ',', '.') ?></p>
                </div>
            </div>
        </div>

        <!-- Kanban de Pedidos -->
        <div class="kanban-container">
            <!-- Coluna Pendente -->
            <div class="kanban-column" id="pendente" ondrop="drop(event)" ondragover="allowDrop(event)">
                <div class="column-header">
                    <h3 class="column-title">Pendente</h3>
                    <span class="badge-count"><?= count(array_filter($pedidos, fn($p) => $p['status'] === 'pendente')) ?></span>
                </div>
                <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'pendente'): ?>
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
                                    <button class="cancel" onclick="cancelarPedido(<?= $pedido['id'] ?>)">
                                        <i class="fas fa-times"></i> Cancelar
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

            <!-- Coluna Em Preparo -->
            <div class="kanban-column" id="preparando" ondrop="drop(event)" ondragover="allowDrop(event)">
                <div class="column-header">
                    <h3 class="column-title">Em Preparo</h3>
                    <span class="badge-count"><?= count(array_filter($pedidos, fn($p) => $p['status'] === 'preparando')) ?></span>
                </div>
                <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'preparando'): ?>
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
                                    <button class="cancel" onclick="cancelarPedido(<?= $pedido['id'] ?>)">
                                        <i class="fas fa-times"></i> Cancelar
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

            <!-- Coluna Pronto -->
            <div class="kanban-column" id="pronto" ondrop="drop(event)" ondragover="allowDrop(event)">
                <div class="column-header">
                    <h3 class="column-title">Pronto</h3>
                    <span class="badge-count"><?= count(array_filter($pedidos, fn($p) => $p['status'] === 'pronto')) ?></span>
                </div>
                <?php foreach ($pedidos as $pedido): if ($pedido['status'] === 'pronto'): ?>
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
                                    <button class="cancel" onclick="cancelarPedido(<?= $pedido['id'] ?>)">
                                        <i class="fas fa-times"></i> Cancelar
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

            <!-- Coluna Entregue -->
            <div class="kanban-column" id="entregue" ondrop="drop(event)" ondragover="allowDrop(event)">
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
                                    <button class="cancel" onclick="cancelarPedido(<?= $pedido['id'] ?>)">
                                        <i class="fas fa-times"></i> Cancelar
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
    </div>

    <!-- Modal de Edição de Pedido -->
    <div class="modal" id="modalEditarPedido">
        <div class="modal-content">
            <span class="close" onclick="fecharModalEditar()">&times;</span>
            <h3>Editar Pedido #<span id="pedidoNumero"></span></h3>
            <input type="hidden" id="edit_pedido_id">
            
            <div class="form-group">
                <label>Cliente</label>
                <select id="edit_cliente_id" class="form-control">
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id'] ?>">
                            <?= htmlspecialchars($cliente['nome']) ?> - <?= $cliente['telefone'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <h4>Itens do Pedido</h4>
            <div id="edit_itens_container"></div>
            
            <button class="btn btn-add-item" onclick="adicionarItemEdicao()">
                <i class="fas fa-plus"></i> Adicionar Item
            </button>
            
            <div class="total-section">
                <strong>Total:</strong> R$ <span id="edit_total_pedido">0,00</span>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-save" onclick="salvarEdicaoPedido()">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
                <button class="btn btn-cancel" onclick="fecharModalEditar()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>

    <div class="modal" id="pedidoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Novo Pedido</h3>
                <button class="close" onclick="closeModal('pedido')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Cliente</label>
                    <select id="cliente_id" class="form-control" required>
                        <option value="">Selecione um cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= $cliente['id'] ?>">
                                <?= htmlspecialchars($cliente['nome']) ?> - <?= $cliente['telefone'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <h4>Itens do Pedido</h4>
                <div id="itensContainer">
                    <!-- Itens serão adicionados aqui dinamicamente -->
                </div>

                <button type="button" class="btn btn-primary" onclick="adicionarItem()" style="margin-bottom: 15px;">
                    <i class="fas fa-plus"></i> Adicionar Item
                </button>

                <div class="total-section">
                    <strong>Total do Pedido:</strong> R$ <span id="totalPedido">0,00</span>
                </div>

                <button type="button" class="submit" onclick="enviarPedido()" style="margin-top: 15px; width: 100%;">
                    <i class="fas fa-save"></i> Salvar Pedido
                </button>
            </div>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        // Funções para o modal de novo pedido
        function openModal(modalType) {
            if (modalType === 'pedido') {
                // Limpa o formulário ao abrir
                document.getElementById('cliente_id').value = '';
                document.getElementById('itensContainer').innerHTML = '';
                document.getElementById('totalPedido').textContent = '0,00';
                
                // Adiciona o primeiro item
                adicionarItem();
            }
            document.getElementById(modalType + 'Modal').style.display = 'flex';
        }

        function closeModal(modalType) {
            document.getElementById(modalType + 'Modal').style.display = 'none';
        }

        function adicionarItem() {
            const container = document.getElementById('itensContainer');
            const novoItem = document.createElement('div');
            novoItem.className = 'item-pedido';
            novoItem.innerHTML = `
                <div class="form-group">
                    <label>Produto</label>
                    <select class="form-control produto-select" required>
                        <option value="">Selecione um produto</option>
                        <?php foreach ($produtos as $produto): ?>
                            <option value="<?= $produto['id'] ?>" data-preco="<?= $produto['preco'] ?>">
                                <?= htmlspecialchars($produto['nome']) ?> - R$ <?= number_format($produto['preco'], 2, ',', '.') ?>
                            </option>
                        <?php endforeach; ?>
                                    </select>
                </div>
                <div class="form-group">
                    <label>Quantidade</label>
                    <input type="number" class="form-control quantidade-input" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label>Subtotal</label>
                    <span class="subtotal">R$ 0,00</span>
                </div>
                <button type="button" class="btn btn-danger remove-item" onclick="removerItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
                <hr>
            `;

            container.appendChild(novoItem);
            
            // Adiciona event listeners para o novo item
            const select = novoItem.querySelector('.produto-select');
            const input = novoItem.querySelector('.quantidade-input');
            
            select.addEventListener('change', calcularSubtotal);
            input.addEventListener('input', calcularSubtotal);
        }

        function removerItem(btn) {
            const item = btn.closest('.item-pedido');
            if (document.querySelectorAll('.item-pedido').length > 1) {
                item.remove();
                calcularTotal();
            } else {
                showNotification('O pedido deve ter pelo menos um item.', 'error');
            }
        }

        function calcularSubtotal(event) {
            const item = event.target.closest('.item-pedido');
            const select = item.querySelector('.produto-select');
            const input = item.querySelector('.quantidade-input');
            const subtotalSpan = item.querySelector('.subtotal');

            const preco = parseFloat(select.selectedOptions[0]?.dataset.preco || 0);
            const quantidade = parseInt(input.value) || 0;
            const subtotal = preco * quantidade;

            subtotalSpan.textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
            calcularTotal();
        }

        function calcularTotal() {
            let total = 0;
            document.querySelectorAll('.item-pedido').forEach(item => {
                const subtotalText = item.querySelector('.subtotal').textContent;
                const subtotal = parseFloat(subtotalText.replace('R$ ', '').replace(',', '.')) || 0;
                total += subtotal;
            });

            document.getElementById('totalPedido').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
        }

        function enviarPedido() {
            const clienteId = document.getElementById('cliente_id').value;
            const itens = [];

            if (!clienteId) {
                showNotification('Selecione um cliente', 'error');
                return;
            }

            document.querySelectorAll('.item-pedido').forEach(item => {
                const produtoId = item.querySelector('.produto-select').value;
                const quantidade = item.querySelector('.quantidade-input').value;

                if (produtoId && quantidade) {
                    itens.push({
                        produto_id: produtoId,
                        quantidade: quantidade
                    });
                }
            });

            if (itens.length === 0) {
                showNotification('Adicione pelo menos um item ao pedido', 'error');
                return;
            }

            fetch('criar_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cliente_id: clienteId,
                    itens: itens
                })
            })
            .then(response => response.json())
            .then(data => {
                showNotification(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    closeModal('pedido');
                    setTimeout(() => location.reload(), 1000);
                }
            })
            .catch(error => {
                console.error('Erro ao enviar pedido:', error);
                showNotification('Erro ao enviar pedido. Verifique o console para mais detalhes.', 'error');
            });
        }

        // Funções do Kanban (Drag and Drop)
        let draggedItem = null;

        function drag(event) {
            draggedItem = event.target;
            event.target.classList.add('dragging');
        }

        function allowDrop(event) {
            event.preventDefault();
        }

        function drop(event) {
            event.preventDefault();
            if (!draggedItem) return;
            
            const novoStatus = event.target.closest('.kanban-column').id;
            const pedidoId = draggedItem.id.split('-')[1];
            
            // Atualiza no servidor
            atualizarStatusPedido(pedidoId, novoStatus);
            
            // Remove a classe de arrasto
            draggedItem.classList.remove('dragging');
            draggedItem = null;
        }

        function atualizarStatusPedido(pedidoId, novoStatus) {
            fetch('index_gerente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    acao: 'atualizar-status',
                    pedido_id: pedidoId,
                    status: novoStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                showNotification(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    // Atualiza a interface após 1 segundo
                    setTimeout(() => location.reload(), 1000);
                }
            });
        }

        // Funções para Edição de Pedidos
        function editarPedido(id) {
            fetch(`buscar_pedido.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        showNotification(data.message, 'error');
                        return;
                    }
                    
                    // Preenche os dados básicos
                    document.getElementById('edit_pedido_id').value = id;
                    document.getElementById('pedidoNumero').textContent = id;
                    document.getElementById('edit_cliente_id').value = data.pedido.cliente_id;
                    
                    // Limpa e preenche os itens
                    const container = document.getElementById('edit_itens_container');
                    container.innerHTML = '';
                    
                    data.itens.forEach((item) => {
                        adicionarItemEdicao(item);
                    });
                    
                    // Calcula o total
                    calcularTotalEdicao();
                    
                    // Mostra o modal
                    document.getElementById('modalEditarPedido').style.display = 'flex';
                })
                .catch(error => {
                    showNotification('Erro ao carregar pedido: ' + error, 'error');
                });
        }

        function adicionarItemEdicao(itemExistente = null) {
            const container = document.getElementById('edit_itens_container');
            const itemDiv = document.createElement('div');
            itemDiv.className = 'edit-item';
            
            itemDiv.innerHTML = `
                <div class="form-group">
                    <label>Produto</label>
                    <select class="form-control edit-produto-select">
                        <option value="">Selecione um produto</option>
                        ${produtos.map(prod => `
                            <option value="${prod.id}" 
                                    ${itemExistente && itemExistente.produto_id == prod.id ? 'selected' : ''}
                                    data-preco="${prod.preco}">
                                ${prod.nome} - R$ ${prod.preco.toFixed(2).replace('.', ',')}
                            </option>
                        `).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantidade</label>
                    <input type="number" class="form-control edit-quantidade-input" 
                            value="${itemExistente ? itemExistente.quantidade : 1}" min="1">
                </div>
                <div class="form-group">
                    <label>Subtotal</label>
                    <span class="edit-subtotal">R$ 0,00</span>
                </div>
                <button type="button" class="btn btn-danger btn-sm remove-item-btn" 
                        onclick="removerItemEdicao(this)">
                    <i class="fas fa-trash"></i> Remover
                </button>
                <hr>
            `;
            
            container.appendChild(itemDiv);
            
            // Adiciona event listeners
            const select = itemDiv.querySelector('.edit-produto-select');
            const input = itemDiv.querySelector('.edit-quantidade-input');
            
            select.addEventListener('change', calcularSubtotalEdicao);
            input.addEventListener('input', calcularSubtotalEdicao);
            
            // Calcula subtotal inicial se for um item existente
            if (itemExistente) {
                calcularSubtotalEdicao({ target: select });
            }
        }

        function removerItemEdicao(btn) {
            const itemDiv = btn.closest('.edit-item');
            if (document.querySelectorAll('.edit-item').length > 1) {
                itemDiv.remove();
                calcularTotalEdicao();
            } else {
                showNotification('O pedido deve ter pelo menos um item.', 'error');
            }
        }

        function calcularSubtotalEdicao(event) {
            const itemDiv = event.target.closest('.edit-item');
            const select = itemDiv.querySelector('.edit-produto-select');
            const input = itemDiv.querySelector('.edit-quantidade-input');
            const subtotalSpan = itemDiv.querySelector('.edit-subtotal');
            
            const preco = parseFloat(select.selectedOptions[0]?.dataset.preco || 0);
            const quantidade = parseInt(input.value) || 0;
            const subtotal = preco * quantidade;
            
            subtotalSpan.textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
            calcularTotalEdicao();
        }

        function calcularTotalEdicao() {
            let total = 0;
            document.querySelectorAll('.edit-item').forEach(itemDiv => {
                const subtotalText = itemDiv.querySelector('.edit-subtotal').textContent;
                const subtotal = parseFloat(subtotalText.replace('R$ ', '').replace(',', '.')) || 0;
                total += subtotal;
            });
            
            document.getElementById('edit_total_pedido').textContent = total.toFixed(2).replace('.', ',');
        }

        function salvarEdicaoPedido() {
            const pedidoId = document.getElementById('edit_pedido_id').value;
            const clienteId = document.getElementById('edit_cliente_id').value;
            
            const itens = [];
            document.querySelectorAll('.edit-item').forEach(itemDiv => {
                const produtoId = itemDiv.querySelector('.edit-produto-select').value;
                const quantidade = itemDiv.querySelector('.edit-quantidade-input').value;
                
                if (produtoId && quantidade) {
                    itens.push({
                        produto_id: produtoId,
                        quantidade: quantidade
                    });
                }
            });
            
            if (itens.length === 0) {
                showNotification('Adicione pelo menos um item ao pedido', 'error');
                return;
            }
            
            fetch('editar_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    pedido_id: pedidoId,
                    cliente_id: clienteId,
                    itens: itens
                })
            })
            .then(response => response.json())
            .then(data => {
                showNotification(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => location.reload(), 1000);
                }
            });
        }

        function fecharModalEditar() {
            document.getElementById('modalEditarPedido').style.display = 'none';
        }

        // Funções auxiliares
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        function toggleDropdown(event, button) {
            event.stopPropagation();
            const dropdown = button.nextElementSibling;
            dropdown.classList.toggle('show');
        }

        // Fechar dropdowns ao clicar fora
        document.addEventListener('click', function() {
            document.querySelectorAll('.card-dropdown').forEach(dd => {
                dd.classList.remove('show');
            });
        });

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Fecha o modal ao pressionar ESC
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    fecharModalEditar();
                }
            });
        });
    </script>
</body>
</html>
