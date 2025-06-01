<?php

// caixaAdmin.php

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Variáveis CSS para cores e sombras */
        :root {
            --primary: #3498db; /* Azul */
            --primary-dark: #2980b9; /* Azul mais escuro */
            --secondary: #2ecc71; /* Verde */
            --danger: #e74c3c; /* Vermelho */
            --warning: #f39c12; /* Amarelo/Laranja */
            --dark: #2c3e50; /* Cinza escuro */
            --light: #ecf0f1; /* Cinza claro */
            --gray: #95a5a6; /* Cinza médio */
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        /* Reset básico e fonte */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; /* Remove margem padrão do body */
            background-color: var(--light); /* Cor de fundo suave */
            color: var(--dark); /* Cor de texto padrão */
            line-height: 1.6;
        }

        /* Estilo do cabeçalho */
        header {
            background: linear-gradient(135deg, var(--dark), #1a2530); /* Gradiente escuro */
            color: var(--white);
            padding: 15px 20px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Permite que os itens quebrem a linha */
            position: sticky; /* Cabeçalho fixo no topo */
            top: 0;
            z-index: 1000;
        }

        header h1 {
            font-size: 1.8rem;
            margin: 0; /* Remove margem padrão do h1 */
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px; /* Espaçamento entre o ícone e o texto */
        }

        /* Estilo dos botões do cabeçalho */
        .header-buttons {
            display: flex;
            gap: 10px;
            margin-top: 0px; /* Ajuste para alinhamento */
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
            text-decoration: none; /* Remove sublinhado de links */
            color: var(--white);
            font-size: 0.9rem;
        }

        /* Cores específicas para os botões */
        .btn { background-color: var(--primary); }
        .btn:hover { background-color: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .btn.logout { background-color: var(--gray); }
        .btn.logout:hover { background-color: #7f8c8d; }

        /* Estilo do container principal */
        .container {
            max-width: 1000px; /* Aumenta a largura máxima */
            margin: 25px auto; /* Centraliza e adiciona margem superior/inferior */
            padding: 30px; /* Mais padding interno */
            background-color: var(--white);
            border-radius: 12px; /* Cantos mais arredondados */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); /* Sombra mais pronunciada */
        }

        /* Estilo dos títulos de seção */
        h1 {
            font-size: 2.5rem; /* Tamanho maior para o título principal */
            text-align: center;
            padding-bottom: 20px;
            margin-bottom: 30px;
            border-bottom: 3px solid var(--primary); /* Linha mais grossa e colorida */
            color: var(--dark);
        }

        h2 {
            font-size: 1.8rem; /* Tamanho para subtítulos */
            margin-top: 35px; /* Mais espaço acima */
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        h2 i {
            color: var(--primary); /* Ícone com cor primária */
        }

        /* Estilo dos campos de formulário */
        form div {
            margin-bottom: 20px; /* Mais espaço entre os grupos de campos */
            display: flex;
            flex-wrap: wrap; /* Permite quebra de linha em telas pequenas */
            align-items: center;
            gap: 15px; /* Espaçamento entre label e input */
        }

        label {
            display: inline-block;
            width: 180px; /* Mantém o alinhamento das labels */
            font-weight: 600; /* Texto da label mais forte */
            color: #555;
        }

        input[type="text"], 
        input[type="number"], 
        select {
            flex: 1; /* Permite que o input preencha o espaço restante */
            padding: 12px 15px; /* Mais padding */
            border: 1px solid #ccc;
            border-radius: 8px; /* Cantos arredondados */
            font-size: 1.05rem; /* Fonte um pouco maior */
            min-width: 250px; /* Garante um tamanho mínimo */
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05); /* Sombra interna sutil */
        }

        /* Estilo dos botões de formulário */
        form button[type="submit"] {
            padding: 12px 25px;
            background-color: var(--primary); /* Usa variável primária */
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px; /* Espaçamento da label acima */
        }

        form button[type="submit"]:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        /* Botões específicos para as ações */
        form button[type="submit"]:contains("Abrir Caixa") { background-color: var(--secondary); }
        form button[type="submit"]:contains("Abrir Caixa"):hover { background-color: #27ae60; }
        form button[type="submit"]:contains("Fechar Caixa") { background-color: var(--danger); }
        form button[type="submit"]:contains("Fechar Caixa"):hover { background-color: #c0392b; }
        form button[type="submit"]:contains("Finalizar Pedido") { background-color: var(--primary); }
        form button[type="submit"]:contains("Finalizar Pedido"):hover { background-color: var(--primary-dark); }

        /* Linha divisória */
        hr {
            border: none;
            border-top: 1px dashed #ddd;
            margin: 40px 0; /* Mais espaço ao redor da linha */
        }

        /* Mensagens de erro e sucesso (mantendo suas classes originais) */
        .error {
            color: var(--danger);
            background-color: #ffe5e5;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid var(--danger);
            font-weight: 500;
        }

        .success {
            color: var(--secondary);
            background-color: #e5ffe5;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid var(--secondary);
            font-weight: 500;
        }

        /* Estilo para a pequena nota */
        small {
            color: var(--gray);
            font-size: 0.9em;
            margin-left: 5px; /* Espaçamento da label/input */
        }

        /* Estilo das informações do caixa atual */
        .status-info p {
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        .status-info strong {
            color: var(--dark);
        }

        /* Estilo dos itens de pedido pendente */
        .pedido-item {
            border: 1px solid #e1e5eb;
            padding: 18px;
            margin-bottom: 15px;
            border-radius: 10px;
            background-color: #fcfdff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .pedido-item p {
            margin-bottom: 5px;
            font-size: 1.05rem;
            font-weight: 500;
        }
        
        .pedido-item form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
            margin-top: 10px; /* Separa o formulário do parágrafo do pedido */
        }
        
        .pedido-item form label {
            min-width: unset; /* Remove o min-width para labels internas */
            width: auto;
        }

        .pedido-item form select {
            flex: 1; /* Permite que o select ocupe o espaço */
            min-width: 150px; /* Garante um tamanho mínimo para o select */
        }

        /* Estilo da tabela de histórico */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            background-color: var(--white);
            border-radius: 8px;
            overflow: hidden; /* Garante que os cantos arredondados sejam aplicados */
            box-shadow: var(--shadow);
        }

        thead {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
        }

        th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        tbody tr {
            border-bottom: 1px solid #e1e5eb;
            transition: background-color 0.2s ease;
        }

        tbody tr:last-child {
            border-bottom: none; /* Remove borda da última linha */
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        td {
            padding: 12px;
            color: #34495e;
            vertical-align: middle; /* Alinha o conteúdo ao meio */
            font-size: 0.95rem;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px 15px;
            }
            header h1 {
                font-size: 1.6rem;
                margin-bottom: 10px;
            }
            .header-buttons {
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }
            .btn {
                flex: 1;
                min-width: 100px;
                justify-content: center;
            }
            .container {
                margin: 15px auto;
                padding: 20px;
            }
            h1 {
                font-size: 2rem;
                padding-bottom: 15px;
                margin-bottom: 20px;
            }
            h2 {
                font-size: 1.5rem;
                margin-top: 25px;
            }
            form div {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            label {
                width: 100%; /* Labels ocupam toda a largura */
                min-width: unset;
            }
            input[type="text"], 
            input[type="number"], 
            select {
                width: 100%; /* Inputs ocupam toda a largura */
                min-width: unset;
            }
            form button[type="submit"] {
                width: 100%; /* Botões de formulário ocupam toda a largura */
                justify-content: center;
                font-size: 1rem;
            }
            .pedido-item form {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }
            .pedido-item form div {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
                width: 100%;
            }
            .pedido-item form select {
                width: 100%;
                min-width: unset;
            }
            .pedido-item form button {
                width: 100%;
            }

            /* Estilo responsivo para tabelas */
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            tr {
                border: 1px solid #ccc;
                margin-bottom: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            }
            td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%; /* Espaço para o label antes do conteúdo */
                text-align: right;
            }
            td:last-child {
                border-bottom: none;
            }
            td:before {
                position: absolute;
                top: 12px;
                left: 12px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                color: #555;
            }
            /* Labels para cada célula da tabela em mobile */
            td:nth-of-type(1):before { content: "ID:"; }
            td:nth-of-type(2):before { content: "Responsável:"; }
            td:nth-of-type(3):before { content: "Status:"; }
            td:nth-of-type(4):before { content: "Saldo Inicial:"; }
            td:nth-of-type(5):before { content: "Saldo Atual:"; }
            td:nth-of-type(6):before { content: "Abertura:"; }
            td:nth-of-type(7):before { content: "Fechamento:"; }
            td:nth-of-type(8):before { content: "Vendas Total:"; }
        }

        @media (max-width: 480px) {
            h1 { font-size: 1.8rem; }
            h2 { font-size: 1.3rem; }
            .container { padding: 15px; }
            .btn { font-size: 0.8rem; padding: 8px 10px; }
            small { font-size: 0.8em; }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1><i class="fas fa-cash-register"></i> Gerenciamento de Caixa</h1>
            <div class="header-buttons">
                <a href="../Admin/index_admin.php" class="btn"><i class="fas fa-arrow-alt-circle-left"></i> Voltar</a>
                <a href="../Geral/logout.php" class="btn logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </div>
    </header>
    <div class="container">
        <h1>Gerenciamento de Caixa</h1>

        <h2><i class="fas fa-info-circle"></i> Status do Caixa Atual</h2>
        <div class="status-info">
            <?php
            $caixaAtual = $caixaManager->getCaixaAtual();
            if ($caixaAtual) {
                // A cor do status é definida pelo estilo inline original, mantido.
                echo "<p><strong>ID do Caixa:</strong> " . $caixaAtual['id'] . "</p>";
                echo "<p><strong>Status:</strong> <span style='color: " . (($caixaAtual['status'] === 'aberto') ? 'var(--secondary)' : 'var(--danger)') . "; font-weight: bold;'>" . ucfirst($caixaAtual['status']) . "</span></p>";
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
        </div>

        <hr>

        <h2><i class="fas fa-lock-open"></i> Abrir Caixa</h2>
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
            <button type="submit"><i class="fas fa-cash-register"></i> Abrir Caixa</button>
        </form>

        <hr>

        <h2><i class="fas fa-lock"></i> Fechar Caixa</h2>
        <form method="POST">
            <input type="hidden" name="action" value="fechar_caixa">
            <div>
                <label for="responsavel_fechamento">Responsável para Fechar:</label>
                <input type="text" id="responsavel_fechamento" name="responsavel_fechamento" required>
                <small>(Opcional: Pode ser o mesmo nome do responsável que abriu o caixa, se você quiser forçar)</small>
            </div>
            <button type="submit"><i class="fas fa-lock"></i> Fechar Caixa</button>
        </form>

        <hr>

        <h2><i class="fas fa-receipt"></i> Pedidos Pendentes (Finalizar e registrar Forma de Pagamento)</h2>
        <?php
        // Obtém as formas de pagamento para o select
        $formasPagamento = $caixaManager->getFormasPagamento();

        // Simulação de como você listaria pedidos pendentes
        // AVISO: Em um ambiente de produção, certifique-se de que esta consulta está otimizada e segura.
        // O ideal é que o CaixaManager tenha um método para obter pedidos pendentes.
        // Adicionei 'valor_total' à consulta, pois é comum precisar dele ao finalizar um pedido.
        $stmtPendentes = $mysqliConnection->prepare("SELECT id, data_pedido, status, valor_total FROM pedidos WHERE status != 'pronto' LIMIT 5");
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
                echo "<p>Pedido ID: <strong>" . $pedido['id'] . "</strong> | Data: " . $pedido['data_pedido'] . " | Status: " . htmlspecialchars($pedido['status']) . " | Valor Total: R$ " . number_format($pedido['valor_total'] ?? 0, 2, ',', '.') . "</p>"; // Usa ?? 0 para evitar erro se valor_total não existir
                echo "<form method='POST'>";
                echo "<input type='hidden' name='action' value='finalizar_pedido'>";
                echo "<input type='hidden' name='pedido_id' value='" . $pedido['id'] . "'>";
                echo "<div>";
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
                echo "</div>";
                echo "<button type='submit'><i class='fas fa-check-circle'></i> Finalizar Pedido</button>";
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

        <h2><i class="fas fa-dollar-sign"></i> Visualizar Total de Vendas do Caixa Atual</h2>
        <?php
        if ($caixaAtual) {
            $totalVendasCaixa = $caixaManager->getTotalVendasCaixa($caixaAtual['id']);
            echo "<p>Total de vendas (pedidos 'pronto') para o caixa atual (ID " . $caixaAtual['id'] . "): <strong>R$ " . number_format($totalVendasCaixa, 2, ',', '.') . "</strong></p>";
        } else {
            echo "<p>Abra um caixa para ver o total de vendas.</p>";
        }
        ?>

        <hr>

        <h2><i class="fas fa-history"></i> Histórico de Caixas</h2>
        <?php
        $historico = $caixaManager->getHistoricoCaixas();
        if (!empty($historico)) {
            echo "<table>"; // Removi border, cellpadding, cellspacing e style inline
            echo "<thead><tr><th>ID</th><th>Responsável</th><th>Status</th><th>Saldo Inicial</th><th>Saldo Atual</th><th>Abertura</th><th>Fechamento</th><th>Vendas Total</th></tr></thead>";
            echo "<tbody>";
            foreach ($historico as $caixa) {
                // Mantive o estilo inline aqui pois ele já existia no seu código original
                $statusColor = ($caixa['status'] === 'aberto') ? 'var(--secondary)' : 'var(--danger)';
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