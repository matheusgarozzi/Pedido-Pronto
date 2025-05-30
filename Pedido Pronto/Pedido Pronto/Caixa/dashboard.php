<?php

// dashboard.php

// 1. Inclua seu arquivo de conexão
require_once '../Geral/conexao.php'; // Caminho corrigido, assumindo que conexao.php está na pasta pai

// 2. Inclua a classe CaixaManager refatorada
require_once 'CaixaManager.php'; // Assumindo que CaixaManager.php está na mesma pasta que dashboard.php

// 3. Obtenha a conexão mysqli através da sua função global getConnection()
$mysqliConnection = getConnection(); // Esta função é definida no seu conexao.php e retorna um objeto mysqli

// 4. Instancia o CaixaManager com a conexão mysqli
$caixaManager = new CaixaManager($mysqliConnection);

// --- Tratamento das ações do formulário ---

// Supondo que você tenha uma interface onde o usuário clica em "Abrir Caixa"
if (isset($_POST['action']) && $_POST['action'] === 'abrir_caixa') {
    $responsavel = $_POST['responsavel'] ?? '';
    $saldoInicial = floatval($_POST['saldo_inicial'] ?? 0.00);

    $resultado = $caixaManager->abrirCaixa($responsavel, $saldoInicial);
    if (isset($resultado['error'])) {
        echo "<p class='error'>Erro ao abrir caixa: " . htmlspecialchars($resultado['error']) . "</p>";
    } else {
        echo "<p class='success'>Caixa aberto com sucesso! ID: " . $resultado['id'] . ", Responsável: " . htmlspecialchars($resultado['responsavel']) . ", Saldo Inicial: R$ " . number_format($resultado['saldo_inicial'], 2, ',', '.') . "</p>";
    }
}

// Supondo que o usuário clica em "Fechar Caixa"
if (isset($_POST['action']) && $_POST['action'] === 'fechar_caixa') {
    $responsavel = $_POST['responsavel_fechamento'] ?? '';

    $resultado = $caixaManager->fecharCaixa($responsavel);
    if (isset($resultado['error'])) {
        echo "<p class='error'>Erro ao fechar caixa: " . htmlspecialchars($resultado['error']) . "</p>";
    } else {
        echo "<p class='success'>Caixa fechado com sucesso! ID: " . $resultado['id'] . ", Data Fechamento: " . $resultado['data_fechamento'] . "</p>";
    }
}

// Supondo que você tem uma lista de pedidos e um botão para "Finalizar Pedido"
if (isset($_POST['action']) && $_POST['action'] === 'finalizar_pedido' && isset($_POST['pedido_id']) && isset($_POST['forma_pagamento'])) {
    $pedidoId = intval($_POST['pedido_id']);
    $formaPagamentoId = intval($_POST['forma_pagamento']);

    $resultado = $caixaManager->finalizarPedidoEAdicionarAoCaixa($pedidoId, $formaPagamentoId); // Passa a forma de pagamento

    if (isset($resultado['error'])) {
        echo "<p class='error'>Erro ao finalizar pedido: " . htmlspecialchars($resultado['error']) . "</p>";
    } else {
        echo "<p class='success'>Pedido ID " . $pedidoId . " finalizado (status 'pronto') e valor de R$ " . number_format($resultado['valor_pedido'], 2, ',', '.') . " adicionado ao caixa.</p>";
        echo "<p>Novo saldo do caixa: R$ " . number_format($resultado['caixa']['saldo_atual'], 2, ',', '.') . "</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Caixa</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        form div { margin-bottom: 10px; }
        label { display: inline-block; width: 180px; } /* Ajustado para melhor alinhamento */
        input[type="text"], input[type="number"], select { padding: 8px; width: 200px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        h2, h3 { color: #333; }
        pre { background-color: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .error { color: red; }
        .success { color: green; }
        .pedido-item { border: 1px solid #eee; padding: 10px; margin-bottom: 10px; border-radius: 5px; background-color: #f9f9f9; }
        .pedido-item form { display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerenciamento de Caixa</h1>

        <h2>Status do Caixa Atual</h2>
        <?php
        $caixaAtual = $caixaManager->getCaixaAtual();
        if ($caixaAtual) {
            echo "<p><strong>ID do Caixa:</strong> " . $caixaAtual['id'] . "</p>";
            echo "<p><strong>Status:</strong> <span style='color: green; font-weight: bold;'>" . ucfirst($caixaAtual['status']) . "</span></p>";
            echo "<p><strong>Responsável:</strong> " . htmlspecialchars($caixaAtual['responsavel']) . "</p>";
            echo "<p><strong>Saldo Inicial:</strong> R$ " . number_format($caixaAtual['saldo_inicial'], 2, ',', '.') . "</p>";
            echo "<p><strong>Saldo Atual:</strong> R$ " . number_format($caixaAtual['saldo_atual'], 2, ',', '.') . "</p>";
            echo "<p><strong>Data Abertura:</strong> " . $caixaAtual['data_abertura'] . "</p>";
            if ($caixaAtual['status'] === 'fechado') {
                echo "<p><strong>Data Fechamento:</strong> " . $caixaAtual['data_fechamento'] . "</p>";
            }
        } else {
            echo "<p>Nenhum caixa aberto no momento.</p>";
        }
        ?>

        <hr>

        <h2>Abrir Caixa</h2>
        <form method="POST">
            <input type="hidden" name="action" value="abrir_caixa">
            <div>
                <label for="responsavel">Responsável:</label>
                <input type="text" id="responsavel" name="responsavel" required>
            </div>
            <div>
                <label for="saldo_inicial">Saldo Inicial (opcional):</label>
                <input type="number" id="saldo_inicial" name="saldo_inicial" step="0.01" value="0.00">
            </div>
            <button type="submit">Abrir Caixa</button>
        </form>

        <hr>

        <h2>Fechar Caixa</h2>
        <form method="POST">
            <input type="hidden" name="action" value="fechar_caixa">
            <div>
                <label for="responsavel_fechamento">Responsável para Fechar:</label>
                <input type="text" id="responsavel_fechamento" name="responsavel_fechamento" required>
                <small>(Opcional: Pode ser o mesmo nome do responsável que abriu o caixa, se você quiser forçar)</small>
            </div>
            <button type="submit">Fechar Caixa</button>
        </form>

        <hr>

        <h2>Pedidos Pendentes (Finalizar e registrar Forma de Pagamento)</h2>
        <?php
        // Obtém as formas de pagamento para o select
        $formasPagamento = $caixaManager->getFormasPagamento();

        // Simulação de como você listaria pedidos pendentes
        $stmtPendentes = $mysqliConnection->prepare("SELECT id, data_pedido, status FROM pedidos WHERE status != 'pronto' LIMIT 5");
        if ($stmtPendentes) {
            $stmtPendentes->execute();
            $resultPendentes = $stmtPendentes->get_result();
            $pedidosPendentes = $resultPendentes->fetch_all(MYSQLI_ASSOC);
            $stmtPendentes->close();
        } else {
            $pedidosPendentes = [];
            echo "<p class='error'>Erro ao buscar pedidos pendentes: " . $mysqliConnection->error . "</p>";
        }


        if (!empty($pedidosPendentes)) {
            echo "<div>";
            foreach ($pedidosPendentes as $pedido) {
                echo "<div class='pedido-item'>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='action' value='finalizar_pedido'>";
                echo "<input type='hidden' name='pedido_id' value='" . $pedido['id'] . "'>";
                echo "<p>Pedido ID: <strong>" . $pedido['id'] . "</strong> | Data: " . $pedido['data_pedido'] . " | Status: " . htmlspecialchars($pedido['status']) . "</p>";
                echo "<label for='forma_pagamento_" . $pedido['id'] . "'>Forma de Pagamento:</label>";
                echo "<select name='forma_pagamento' id='forma_pagamento_" . $pedido['id'] . "' required>";
                if (empty($formasPagamento)) {
                    echo "<option value=''>Nenhuma forma de pagamento cadastrada</option>";
                } else {
                    echo "<option value=''>Selecione...</option>";
                    foreach ($formasPagamento as $forma) {
                        echo "<option value='" . $forma['id'] . "'>" . htmlspecialchars($forma['nome']) . "</option>";
                    }
                }
                echo "</select>";
                echo "<button type='submit'>Finalizar Pedido</button>";
                echo "</form>";
                echo "</div>"; // .pedido-item
            }
            echo "</div>";
        } else {
            echo "<p>Nenhum pedido pendente para finalizar.</p>";
        }
        ?>
        <p><small>Selecione a forma de pagamento e clique em "Finalizar Pedido" para registrar a venda e somar ao caixa.</small></p>

        <hr>

        <h2>Visualizar Total de Vendas do Caixa Atual</h2>
        <?php
        if ($caixaAtual) {
            $totalVendasCaixa = $caixaManager->getTotalVendasCaixa($caixaAtual['id']);
            echo "<p>Total de vendas (pedidos 'pronto') para o caixa atual (ID " . $caixaAtual['id'] . "): <strong>R$ " . number_format($totalVendasCaixa, 2, ',', '.') . "</strong></p>";
        } else {
            echo "<p>Abra um caixa para ver o total de vendas.</p>";
        }
        ?>

        <hr>

        <h2>Histórico de Caixas</h2>
        <?php
        $historico = $caixaManager->getHistoricoCaixas();
        if (!empty($historico)) {
            echo "<table border='1' cellpadding='5' cellspacing='0' style='width:100%;'>";
            echo "<thead><tr><th>ID</th><th>Responsável</th><th>Status</th><th>Saldo Inicial</th><th>Saldo Atual</th><th>Abertura</th><th>Fechamento</th><th>Vendas Total</th></tr></thead>";
            echo "<tbody>";
            foreach ($historico as $caixa) {
                $statusColor = ($caixa['status'] === 'aberto') ? 'green' : 'red';
                $totalVendas = $caixaManager->getTotalVendasCaixa($caixa['id']);
                echo "<tr>";
                echo "<td>" . $caixa['id'] . "</td>";
                echo "<td>" . htmlspecialchars($caixa['responsavel']) . "</td>";
                echo "<td style='color: " . $statusColor . "; font-weight: bold;'>" . ucfirst($caixa['status']) . "</td>";
                echo "<td>R$ " . number_format($caixa['saldo_inicial'], 2, ',', '.') . "</td>";
                echo "<td>R$ " . number_format($caixa['saldo_atual'], 2, ',', '.') . "</td>";
                echo "<td>" . $caixa['data_abertura'] . "</td>";
                echo "<td>" . ($caixa['data_fechamento'] ?? 'N/A') . "</td>";
                echo "<td>R$ " . number_format($totalVendas, 2, ',', '.') . "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>Nenhum caixa registrado no histórico.</p>";
        }
        ?>
    </div>
</body>
</html>