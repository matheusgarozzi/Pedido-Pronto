<?php
// Geral/funcoes.php - VERSÃO CORRIGIDA FINAL - Corrigido erro de sintaxe em buscarPedidos

require_once '../Geral/conexao.php'; // Certifique-se que o caminho para sua classe Database (conexao.php) está correto

class FuncoesGerente {
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
            $query .= " WHERE p.status IN ('pendente', 'preparando', 'pronto', 'entregue', 'cancelado')"; 
            // Não há parâmetros para bind aqui, pois o IN é fixo
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
        
        $query = "UPDATE pedidos SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
             error_log("Erro na preparação da atualização de status: " . $conn->error);
             return false;
        }
        $stmt->bind_param("si", $novo_status, $pedido_id);
        $success = $stmt->execute();
        $stmt->close();
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
     * Gera um relatório consolidado de pedidos.
     * @return array Relatório contendo totais e detalhes de cancelamentos.
     */
    public static function gerarRelatorioPedidos(): array {
        $conn = Database::getInstance()->getConnection();
        $relatorio = [
            'total_pedidos' => 0,
            'total_pedidos_cancelados' => 0,
            'total_pedidos_finalizados' => 0,
            'detalhes_cancelamento_motivo' => [],
            'detalhes_cancelamento_pedidos' => [],
            'pedidos_por_status' => []
        ];

        $stmt = $conn->query("SELECT COUNT(*) AS total FROM pedidos");
        if ($stmt) {
            $relatorio['total_pedidos'] = $stmt->fetch_assoc()['total'];
        }

        $stmt = $conn->query("SELECT COUNT(*) AS total FROM pedidos WHERE status = 'cancelado'");
        if ($stmt) {
            $relatorio['total_pedidos_cancelados'] = $stmt->fetch_assoc()['total'];
        }

        $stmt = $conn->query("SELECT COUNT(*) AS total FROM pedidos WHERE status = 'pronto' OR status = 'entregue'");
        if ($stmt) {
            $relatorio['total_pedidos_finalizados'] = $stmt->fetch_assoc()['total'];
        }

        $stmt = $conn->query(
            "SELECT mc.motivo, COUNT(p.id) AS quantidade
             FROM pedidos p
             JOIN motivos_cancelamento mc ON p.motivo_cancelamento_id = mc.id
             WHERE p.status = 'cancelado'
             GROUP BY mc.motivo
             ORDER BY quantidade DESC"
        );
        if ($stmt) {
            while ($row = $stmt->fetch_assoc()) {
                $relatorio['detalhes_cancelamento_motivo'][$row['motivo']] = $row['quantidade'];
            }
        }

        $stmt = $conn->query(
            "SELECT p.id, p.data_pedido, c.nome AS cliente_nome, GROUP_CONCAT(pr.nome SEPARATOR ', ') AS produtos,
                    mc.motivo AS motivo_cancelamento, p.status_anterior
             FROM pedidos p
             JOIN clientes c ON p.cliente_id = c.id
             LEFT JOIN itenspedido ip ON p.id = ip.pedido_id
             LEFT JOIN produtos pr ON ip.produto_id = pr.id
             LEFT JOIN motivos_cancelamento mc ON p.motivo_cancelamento_id = mc.id
             WHERE p.status = 'cancelado'
             GROUP BY p.id, p.data_pedido, c.nome, mc.motivo, p.status_anterior
             ORDER BY p.data_pedido DESC
             LIMIT 10"
        );
        if ($stmt) {
            $relatorio['detalhes_cancelamento_pedidos'] = $stmt->fetch_all(MYSQLI_ASSOC);
        }

        $stmt = $conn->query("SELECT status, COUNT(*) AS count FROM pedidos GROUP BY status");
        if ($stmt) {
            while ($row = $stmt->fetch_assoc()) {
                $relatorio['pedidos_por_status'][$row['status']] = $row['count'];
            }
        }

        return $relatorio;
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

                // Aqui estava o erro: use itens_pedido ou ItensPedido consistentemente
                $stmt_item = $conn->prepare("INSERT INTO itenspedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
                if (!$stmt_item) throw new Exception("Erro ao preparar cadastro de item de pedido: " . $conn->error);
                $stmt_item->bind_param("iiid", $pedido_id, $produto_id, $quantidade, $preco_unitario);
                $stmt_item->execute();
                $stmt_item->close();
            }
            $conn->commit();
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
        return $success;
    }

    public static function editarItensPedido(int $pedidoId, array $novosItens): bool {
        $conn = Database::getInstance()->getConnection();
        $conn->begin_transaction();

        try {
            // 1. Remover todos os itens antigos do pedido
            // Aqui também: use itens_pedido ou ItensPedido consistentemente
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

                // E aqui: use itens_pedido ou ItensPedido consistentemente
                $stmt_item = $conn->prepare("INSERT INTO itenspedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
                if (!$stmt_item) throw new Exception("Erro ao preparar inserção de novo item para edição: " . $conn->error);
                $stmt_item->bind_param("iiid", $pedidoId, $produto_id, $quantidade, $preco_unitario);
                $stmt_item->execute();
                $stmt_item->close();
            }

            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Erro na edição de pedido: " . $e->getMessage());
            return false;
        }
    }
}
?>