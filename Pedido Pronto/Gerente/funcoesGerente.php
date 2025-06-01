<?php
// Geral/funcoes.php - VERSÃO CORRIGIDA FINAL - Corrigido erro de sintaxe em buscarPedidos

require_once '../Geral/conexao.php'; // Certifique-se que o caminho para sua classe Database (conexao.php) está correto

class FuncoesGerente {
    /**
     * Registra uma ação da equipe na tabela log_acoes.
     * @param string $responsavel Nome do responsável pela ação.
     * @param string $acao Descrição breve da ação.
     * @param string|null $detalhes Detalhes adicionais sobre a ação.
     * @return bool True se a ação foi registrada com sucesso, false caso contrário.
     */
    public static function registrarAcao(string $responsavel, string $acao, ?string $detalhes = null): bool {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("INSERT INTO log_acoes (responsavel, acao, detalhes) VALUES (?, ?, ?)");
        if (!$stmt) {
            error_log("Erro ao preparar registro de ação: " . $conn->error);
            return false;
        }
        $stmt->bind_param("sss", $responsavel, $acao, $detalhes);
        $success = $stmt->execute();
        $stmt->close();
        if (!$success) {
            error_log("Erro ao executar registro de ação: " . $conn->error);
        }
        return $success;
    }

    /**
     * Busca logs de ações da equipe com filtros opcionais.
     * @param array $filters Array associativo de filtros (data_inicio, data_fim, responsavel, acao_termo).
     * @return array Lista de logs de ações.
     */
    public static function buscarLogsAcoes(array $filters = []): array {
        $conn = Database::getInstance()->getConnection();
        $query = "SELECT id, data_acao, responsavel, acao, detalhes FROM log_acoes";
        
        $whereClauses = [];
        $types = '';
        $params = [];

        if (!empty($filters['data_inicio'])) {
            $whereClauses[] = "data_acao >= ?";
            $types .= 's';
            $params[] = $filters['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filters['data_fim'])) {
            $whereClauses[] = "data_acao <= ?";
            $types .= 's';
            $params[] = $filters['data_fim'] . ' 23:59:59';
        }
        if (!empty($filters['responsavel'])) {
            $whereClauses[] = "responsavel LIKE ?";
            $types .= 's';
            $params[] = '%' . $filters['responsavel'] . '%';
        }
        if (!empty($filters['acao_termo'])) {
            $whereClauses[] = "acao LIKE ?";
            $types .= 's';
            $params[] = '%' . $filters['acao_termo'] . '%';
        }

        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(" AND ", $whereClauses);
        }
        $query .= " ORDER BY data_acao DESC";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Erro na preparação da consulta de logs de ações: " . $conn->error);
            return [];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $logs;
    }

    // Função para buscar pedidos com status específico
    public static function buscarPedidos($status = null) {
        $conn = Database::getInstance()->getConnection();
        
        $query = "SELECT p.id, p.status, p.data_pedido, p.observacoes, 
                             c.nome AS cliente_nome,
                             GROUP_CONCAT(CONCAT(ip.quantidade, 'x ', pr.nome) SEPARATOR ', ') AS produtos,
                             SUM(ip.quantidade * ip.preco_unitario) AS total,
                             fp.nome AS forma_pagamento_nome,
                             mc.motivo AS motivo_cancelamento,
                             p.status_anterior
                      FROM pedidos p
                      JOIN clientes c ON p.cliente_id = c.id
                      LEFT JOIN itenspedido ip ON p.id = ip.pedido_id -- MUDANÇA AQUI: de JOIN para LEFT JOIN
                      LEFT JOIN produtos pr ON ip.produto_id = pr.id";
        
        $query .= " LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id";
        $query .= " LEFT JOIN motivos_cancelamento mc ON p.motivo_cancelamento_id = mc.id";

        // Variáveis para a preparação do statement
        $types = "";
        $params = [];

        if ($status) {
            $query .= " WHERE p.status = ?";
            $types .= "s"; // 's' para string
            $params[] = $status;
        } else {
            // Se nenhum status for especificado, traga todos para o Kanban
            $query .= " WHERE p.status IN ('pendente', 'preparo', 'pronto', 'entregue', 'cancelado')"; 
        }
        
        $query .= " GROUP BY p.id, p.status, p.data_pedido, p.observacoes, c.nome, fp.nome, mc.motivo, p.status_anterior ORDER BY p.data_pedido DESC";
        
        // Preparar o statement FINALMENTE
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Erro na preparação final da consulta buscarPedidos: " . $conn->error);
            return [];
        }

        // Fazer o bind_param APENAS se houver parâmetros (ou seja, se $status foi fornecido)
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params); // Usar operador '...' para desempacotar o array
        }

        if (!$stmt->execute()) {
            error_log("Erro ao executar buscarPedidos: " . $stmt->error);
            return [];
        }
        $result = $stmt->get_result();
        
        $pedidos = [];
        while ($row = $result->fetch_assoc()) {
            $pedidos[] = $row;
        }
        $stmt->close();
        
        return $pedidos;
    }

    // Função para atualizar status do pedido
    public static function atualizarStatus($pedido_id, $novo_status) {
        $conn = Database::getInstance()->getConnection();
        
        // Buscar status anterior para o log
        $stmt_old_status = $conn->prepare("SELECT status FROM pedidos WHERE id = ?");
        if (!$stmt_old_status) {
            error_log("Erro ao preparar busca de status antigo: " . $conn->error);
            return false;
        }
        $stmt_old_status->bind_param("i", $pedido_id);
        $stmt_old_status->execute();
        $result_old_status = $stmt_old_status->get_result();
        $old_status_row = $result_old_status->fetch_assoc();
        $old_status = $old_status_row ? $old_status_row['status'] : 'desconhecido';
        $stmt_old_status->close();

        $query = "UPDATE pedidos SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
             error_log("Erro na preparação da atualização de status: " . $conn->error);
             return false;
        }
        $stmt->bind_param("si", $novo_status, $pedido_id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            // TODO: Substituir "Gerente" pelo nome do usuário logado
            self::registrarAcao("Gerente", "atualizou status do pedido #{$pedido_id}", "De '{$old_status}' para '{$novo_status}'");
        }
        return $success;
    }

    /**
     * Atualiza o status de um pedido para 'cancelado' e registra o motivo.
     * Agora, não exclui fisicamente o pedido.
     * @param int $pedidoId ID do pedido a ser cancelado.
     * @param int $motivoId ID do motivo de cancelamento.
     * @return bool True se o status for atualizado com sucesso, false caso contrário.
     */
    public static function cancelarPedido(int $pedidoId, int $motivoId): bool {
        $conn = Database::getInstance()->getConnection();
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("SELECT status FROM pedidos WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Erro na preparação da consulta de status (cancelarPedido): " . $conn->error);
            }
            $stmt->bind_param("i", $pedidoId);
            $stmt->execute();
            $result = $stmt->get_result();
            $pedidoExistente = $result->fetch_assoc();
            $stmt->close();

            if (!$pedidoExistente) {
                throw new Exception("Pedido #{$pedidoId} não encontrado para cancelamento.");
            }

            $statusAnterior = $pedidoExistente['status'];

            $stmt = $conn->prepare(
                "UPDATE pedidos SET status = 'cancelado', status_anterior = ?, motivo_cancelamento_id = ? WHERE id = ?"
            );
            if (!$stmt) {
                throw new Exception("Erro na preparação da atualização do status (cancelarPedido): " . $conn->error);
            }
            $stmt->bind_param("sii", $statusAnterior, $motivoId, $pedidoId);
            $success = $stmt->execute();
            $stmt->close();

            if (!$success) {
                throw new Exception("Falha ao cancelar pedido #{$pedidoId}: " . $conn->error);
            }

            $conn->commit();
            // TODO: Substituir "Gerente" pelo nome do usuário logado
            self::registrarAcao("Gerente", "cancelou o pedido #{$pedidoId}", "Status anterior: '{$statusAnterior}', Motivo ID: {$motivoId}");
            return true;

        } catch (Exception $e) {
            $conn->rollback();
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Busca todos os motivos de cancelamento ativos.
     * @return array Lista de motivos de cancelamento.
     */
    public static function buscarMotivosCancelamento(): array {
        $conn = Database::getInstance()->getConnection();

        $stmt = $conn->prepare("SELECT id, motivo FROM motivos_cancelamento WHERE ativo = TRUE ORDER BY motivo ASC");
        if (!$stmt) {
            error_log("Erro na preparação (buscarMotivosCancelamento): " . $conn->error);
            return [];
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $motivos = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $motivos;
    }

    /**
     * Gera um relatório consolidado de pedidos com filtros opcionais.
     * @param array $filters Array associativo de filtros (data_inicio, data_fim, status, pedido_id, cliente_nome).
     * @return array Relatório contendo totais e detalhes de cancelamentos.
     */
    public static function gerarRelatorioPedidos(array $filters = []): array {
        $conn = Database::getInstance()->getConnection();
        $relatorio = [
            'total_pedidos' => 0,
            'total_pedidos_cancelados' => 0,
            'total_pedidos_finalizados' => 0,
            'detalhes_cancelamento_motivo' => [],
            'detalhes_cancelamento_pedidos' => [],
            'pedidos_por_status' => [],
            'pedidos_filtrados' => [] // Novo array para os pedidos detalhados filtrados
        ];

        // Construção da query base para pedidos detalhados
        $baseQuery = "SELECT p.id, p.data_pedido, p.status, p.observacoes, 
                             c.nome AS cliente_nome,
                             GROUP_CONCAT(CONCAT(ip.quantidade, 'x ', pr.nome) SEPARATOR ', ') AS produtos,
                             SUM(ip.quantidade * ip.preco_unitario) AS total,
                             fp.nome AS forma_pagamento_nome,
                             mc.motivo AS motivo_cancelamento,
                             p.status_anterior
                      FROM pedidos p
                      JOIN clientes c ON p.cliente_id = c.id
                      LEFT JOIN itenspedido ip ON p.id = ip.pedido_id
                      LEFT JOIN produtos pr ON ip.produto_id = pr.id
                      LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
                      LEFT JOIN motivos_cancelamento mc ON p.motivo_cancelamento_id = mc.id";
        
        $whereClauses = [];
        $types = '';
        $params = [];

        // Adicionar filtros
        if (!empty($filters['data_inicio'])) {
            $whereClauses[] = "p.data_pedido >= ?";
            $types .= 's';
            $params[] = $filters['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filters['data_fim'])) {
            $whereClauses[] = "p.data_pedido <= ?";
            $types .= 's';
            $params[] = $filters['data_fim'] . ' 23:59:59';
        }
        if (!empty($filters['status']) && $filters['status'] !== 'todos') {
            $whereClauses[] = "p.status = ?";
            $types .= 's';
            $params[] = $filters['status'];
        }
        if (!empty($filters['pedido_id'])) {
            $whereClauses[] = "p.id = ?";
            $types .= 'i';
            $params[] = $filters['pedido_id'];
        }
        if (!empty($filters['cliente_nome'])) {
            $whereClauses[] = "c.nome LIKE ?";
            $types .= 's';
            $params[] = '%' . $filters['cliente_nome'] . '%';
        }

        $fullQuery = $baseQuery;
        if (!empty($whereClauses)) {
            $fullQuery .= " WHERE " . implode(" AND ", $whereClauses);
        }
        $fullQuery .= " GROUP BY p.id, p.data_pedido, p.status, p.observacoes, c.nome, fp.nome, mc.motivo, p.status_anterior ORDER BY p.data_pedido DESC";

        $stmtPedidosFiltrados = $conn->prepare($fullQuery);
        if ($stmtPedidosFiltrados) {
            if (!empty($params)) {
                $stmtPedidosFiltrados->bind_param($types, ...$params);
            }
            $stmtPedidosFiltrados->execute();
            $resultPedidosFiltrados = $stmtPedidosFiltrados->get_result();
            $relatorio['pedidos_filtrados'] = $resultPedidosFiltrados->fetch_all(MYSQLI_ASSOC);
            $stmtPedidosFiltrados->close();
        } else {
            error_log("Erro na preparação da consulta de pedidos filtrados: " . $conn->error);
        }


        // As contagens totais (total_pedidos, total_pedidos_cancelados, total_pedidos_finalizados, pedidos_por_status)
        // serão baseadas nos pedidos filtrados para o relatório.
        // Se você quiser que esses totais sejam sempre do banco de dados completo,
        // remova os filtros das queries abaixo. Por enquanto, eles refletirão os filtros.

        $queryTotal = "SELECT COUNT(*) AS total FROM pedidos p";
        if (!empty($whereClauses)) {
            $queryTotal .= " WHERE " . implode(" AND ", $whereClauses);
        }
        $stmtTotal = $conn->prepare($queryTotal);
        if ($stmtTotal) {
            if (!empty($params)) {
                $stmtTotal->bind_param($types, ...$params);
            }
            $stmtTotal->execute();
            $relatorio['total_pedidos'] = $stmtTotal->get_result()->fetch_assoc()['total'];
            $stmtTotal->close();
        }

        $queryCancelados = "SELECT COUNT(*) AS total FROM pedidos p WHERE p.status = 'cancelado'";
        if (!empty($whereClauses)) {
            $queryCancelados .= " AND " . implode(" AND ", $whereClauses);
        }
        $stmtCancelados = $conn->prepare($queryCancelados);
        if ($stmtCancelados) {
            if (!empty($params)) {
                $stmtCancelados->bind_param($types, ...$params);
            }
            $stmtCancelados->execute();
            $relatorio['total_pedidos_cancelados'] = $stmtCancelados->get_result()->fetch_assoc()['total'];
            $stmtCancelados->close();
        }

        $queryFinalizados = "SELECT COUNT(*) AS total FROM pedidos p WHERE p.status IN ('pronto', 'entregue')";
        if (!empty($whereClauses)) {
            $queryFinalizados .= " AND " . implode(" AND ", $whereClauses);
        }
        $stmtFinalizados = $conn->prepare($queryFinalizados);
        if ($stmtFinalizados) {
            if (!empty($params)) {
                $stmtFinalizados->bind_param($types, ...$params);
            }
            $stmtFinalizados->execute();
            $relatorio['total_pedidos_finalizados'] = $stmtFinalizados->get_result()->fetch_assoc()['total'];
            $stmtFinalizados->close();
        }
        
        $queryStatusCount = "SELECT status, COUNT(*) AS count FROM pedidos p";
        if (!empty($whereClauses)) {
            $queryStatusCount .= " WHERE " . implode(" AND ", $whereClauses);
        }
        $queryStatusCount .= " GROUP BY status";
        $stmtStatusCount = $conn->prepare($queryStatusCount);
        if ($stmtStatusCount) {
            if (!empty($params)) {
                $stmtStatusCount->bind_param($types, ...$params);
            }
            $stmtStatusCount->execute();
            $resultStatusCount = $stmtStatusCount->get_result();
            while ($row = $resultStatusCount->fetch_assoc()) {
                $relatorio['pedidos_por_status'][$row['status']] = $row['count'];
            }
            $stmtStatusCount->close();
        }


        // Detalhes de cancelamento por motivo (sempre filtrados pelos pedidos cancelados E pelos filtros gerais)
        $queryMotivoCancelamento = "SELECT mc.motivo, COUNT(p.id) AS quantidade
                                    FROM pedidos p
                                    JOIN motivos_cancelamento mc ON p.motivo_cancelamento_id = mc.id
                                    WHERE p.status = 'cancelado'";
        if (!empty($whereClauses)) {
            $queryMotivoCancelamento .= " AND " . implode(" AND ", $whereClauses);
        }
        $queryMotivoCancelamento .= " GROUP BY mc.motivo ORDER BY quantidade DESC";
        $stmtMotivoCancelamento = $conn->prepare($queryMotivoCancelamento);
        if ($stmtMotivoCancelamento) {
            if (!empty($params)) {
                $stmtMotivoCancelamento->bind_param($types, ...$params);
            }
            $stmtMotivoCancelamento->execute();
            $resultMotivoCancelamento = $stmtMotivoCancelamento->get_result();
            while ($row = $resultMotivoCancelamento->fetch_assoc()) {
                $relatorio['detalhes_cancelamento_motivo'][$row['motivo']] = $row['quantidade'];
            }
            $stmtMotivoCancelamento->close();
        }

        // Últimos 10 pedidos cancelados (também filtrados pelos filtros gerais)
        $queryUltimosCancelados = "SELECT p.id, p.data_pedido, c.nome AS cliente_nome, GROUP_CONCAT(pr.nome SEPARATOR ', ') AS produtos,
                                mc.motivo AS motivo_cancelamento, p.status_anterior
                                FROM pedidos p
                                JOIN clientes c ON p.cliente_id = c.id
                                LEFT JOIN itenspedido ip ON p.id = ip.pedido_id
                                LEFT JOIN produtos pr ON ip.produto_id = pr.id
                                LEFT JOIN motivos_cancelamento mc ON p.motivo_cancelamento_id = mc.id
                                WHERE p.status = 'cancelado'";
        if (!empty($whereClauses)) {
            $queryUltimosCancelados .= " AND " . implode(" AND ", $whereClauses);
        }
        $queryUltimosCancelados .= " GROUP BY p.id, p.data_pedido, c.nome, mc.motivo, p.status_anterior
                                ORDER BY p.data_pedido DESC
                                LIMIT 10";
        $stmtUltimosCancelados = $conn->prepare($queryUltimosCancelados);
        if ($stmtUltimosCancelados) {
            if (!empty($params)) {
                $stmtUltimosCancelados->bind_param($types, ...$params);
            }
            $stmtUltimosCancelados->execute();
            $relatorio['detalhes_cancelamento_pedidos'] = $stmtUltimosCancelados->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtUltimosCancelados->close();
        }

        return $relatorio;
    }

    /**
     * Gera um relatório de produtos mais vendidos com filtros de data.
     * @param array $filters Array associativo de filtros (data_inicio, data_fim).
     * @return array Lista de produtos mais vendidos com suas quantidades e número de pedidos.
     */
    public static function gerarRelatorioProdutosVendidos(array $filters = []): array {
        $conn = Database::getInstance()->getConnection();
        $produtosVendidos = [];

        $query = "SELECT pr.nome AS produto_nome, 
                         SUM(ip.quantidade) AS total_quantidade_vendida,
                         COUNT(DISTINCT ip.pedido_id) AS total_pedidos
                  FROM itenspedido ip
                  JOIN produtos pr ON ip.produto_id = pr.id
                  JOIN pedidos p ON ip.pedido_id = p.id";
        
        $whereClauses = [];
        $types = '';
        $params = [];

        if (!empty($filters['data_inicio'])) {
            $whereClauses[] = "p.data_pedido >= ?";
            $types .= 's';
            $params[] = $filters['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filters['data_fim'])) {
            $whereClauses[] = "p.data_pedido <= ?";
            $types .= 's';
            $params[] = $filters['data_fim'] . ' 23:59:59';
        }

        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $query .= " GROUP BY pr.nome ORDER BY total_quantidade_vendida DESC";

        $stmt = $conn->prepare($query);
        if ($stmt) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $produtosVendidos = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            error_log("Erro na preparação da consulta de produtos vendidos: " . $conn->error);
        }

        return $produtosVendidos;
    }

    // Função para buscar clientes
    public static function buscarClientes() {
        $conn = Database::getInstance()->getConnection();
        
        $query = "SELECT id, nome, telefone FROM clientes ORDER BY nome";
        $result = $conn->query($query);
        
        $clientes = [];
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }
        
        return $clientes;
    }

    // Função para buscar produtos ativos
    public static function buscarProdutos() {
        $conn = Database::getInstance()->getConnection();
        
        $query = "SELECT id, nome, preco FROM produtos WHERE ativo = 1 ORDER BY nome";
        $result = $conn->query($query);
        
        $produtos = [];
        while ($row = $result->fetch_assoc()) {
            $produtos[] = $row;
        }
        
        return $produtos;
    }

    // Função para cadastrar pedidos
    public static function cadastrarPedido($cliente_id, $itens) {
        $conn = Database::getInstance()->getConnection();
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO pedidos (cliente_id, data_pedido, status) VALUES (?, NOW(), 'pendente')");
            if (!$stmt) throw new Exception("Erro ao preparar cadastro de pedido: " . $conn->error);
            $stmt->bind_param("i", $cliente_id);
            $stmt->execute();
            $pedido_id = $conn->insert_id;
            $stmt->close();

            foreach ($itens as $item) {
                $produto_id = $item['produto_id'];
                $quantidade = $item['quantidade'];
                
                // Buscar preco_unitario do produto
                $stmt_preco = $conn->prepare("SELECT preco FROM produtos WHERE id = ?");
                if (!$stmt_preco) throw new Exception("Erro ao preparar busca de preço: " . $conn->error);
                $stmt_preco->bind_param("i", $produto_id);
                $stmt_preco->execute();
                $result_preco = $stmt_preco->get_result();
                $produto_data = $result_preco->fetch_assoc();
                $stmt_preco->close();
                
                if (!$produto_data) throw new Exception("Produto ID {$produto_id} não encontrado.");
                $preco_unitario = $produto_data['preco'];

                $stmt_item = $conn->prepare("INSERT INTO itenspedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
                if (!$stmt_item) throw new Exception("Erro ao preparar cadastro de item de pedido: " . $conn->error);
                $stmt_item->bind_param("iiid", $pedido_id, $produto_id, $quantidade, $preco_unitario);
                $stmt_item->execute();
                $stmt_item->close();
            }
            $conn->commit();
            // TODO: Substituir "Gerente" pelo nome do usuário logado
            self::registrarAcao("Gerente", "cadastrou o pedido #{$pedido_id}", "Cliente ID: {$cliente_id}");
            return $pedido_id;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Erro no cadastro de pedido: " . $e->getMessage());
            return false;
        }
    }

    public static function cadastrarCliente($nome, $telefone, $endereco) {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("INSERT INTO clientes (nome, telefone, endereco) VALUES (?, ?, ?)");
        if (!$stmt) {
             error_log("Erro ao preparar cadastro de cliente: " . $conn->error);
             return false;
        }
        $stmt->bind_param("sss", $nome, $telefone, $endereco);
        $success = $stmt->execute();
        $stmt->close();
        if ($success) {
            // TODO: Substituir "Gerente" pelo nome do usuário logado
            self::registrarAcao("Gerente", "cadastrou o cliente '{$nome}'", "Telefone: {$telefone}");
        }
        return $success;
    }

    public static function cadastrarProduto($nome_produto, $preco_produto) {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("INSERT INTO produtos (nome, preco, ativo) VALUES (?, ?, 1)");
        if (!$stmt) {
             error_log("Erro ao preparar cadastro de produto: " . $conn->error);
             return false;
        }
        $stmt->bind_param("sd", $nome_produto, $preco_produto);
        $success = $stmt->execute();
        $stmt->close();
        if ($success) {
            // TODO: Substituir "Gerente" pelo nome do usuário logado
            self::registrarAcao("Gerente", "cadastrou o produto '{$nome_produto}'", "Preço: R$ " . number_format($preco_produto, 2, ',', '.'));
        }
        return $success;
    }

    public static function editarItensPedido(int $pedidoId, array $novosItens): bool {
        $conn = Database::getInstance()->getConnection();
        $conn->begin_transaction();

        try {
            // 1. Remover todos os itens antigos do pedido
            $stmt = $conn->prepare("DELETE FROM itenspedido WHERE pedido_id = ?");
            if (!$stmt) throw new Exception("Erro ao preparar exclusão de itens antigos: " . $conn->error);
            $stmt->bind_param("i", $pedidoId);
            $stmt->execute();
            $stmt->close();

            // 2. Inserir os novos itens
            foreach ($novosItens as $item) {
                $produto_id = $item['produto_id'];
                $quantidade = $item['quantidade'];

                // Buscar preco_unitario do produto
                $stmt_preco = $conn->prepare("SELECT preco FROM produtos WHERE id = ?");
                if (!$stmt_preco) throw new Exception("Erro ao preparar busca de preço para edição: " . $conn->error);
                $stmt_preco->bind_param("i", $produto_id);
                $stmt_preco->execute();
                $result_preco = $stmt_preco->get_result();
                $produto_data = $result_preco->fetch_assoc();
                $stmt_preco->close();
                
                if (!$produto_data) throw new Exception("Produto ID {$produto_id} não encontrado na edição.");
                $preco_unitario = $produto_data['preco'];

                $stmt_item = $conn->prepare("INSERT INTO itenspedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
                if (!$stmt_item) throw new Exception("Erro ao preparar inserção de novo item para edição: " . $conn->error);
                $stmt_item->bind_param("iiid", $pedidoId, $produto_id, $quantidade, $preco_unitario);
                $stmt_item->execute();
                $stmt_item->close();
            }

            $conn->commit();
            // TODO: Substituir "Gerente" pelo nome do usuário logado
            self::registrarAcao("Gerente", "editou os itens do pedido #{$pedidoId}", "Total de itens: " . count($novosItens));
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Erro na edição de pedido: " . $e->getMessage());
            return false;
        }
    }
}
