<?php
require_once '../Geral/conexao.php';

class FuncoesGerente {
    // Função para buscar pedidos com status específico
    public static function buscarPedidos($status = null) {
        $conn = Database::getInstance()->getConnection();
        
        $query = "SELECT p.id, p.status, p.data_pedido, p.observacoes, 
                         c.nome AS cliente_nome,
                         GROUP_CONCAT(CONCAT(ip.quantidade, 'x ', pr.nome) SEPARATOR ', ') AS produtos,
                         SUM(ip.quantidade * ip.preco_unitario) AS total
                  FROM pedidos p
                  JOIN clientes c ON p.cliente_id = c.id
                  JOIN itenspedido ip ON p.id = ip.pedido_id
                  JOIN produtos pr ON ip.produto_id = pr.id";
        
        if ($status) {
            $query .= " WHERE p.status = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $status);
        } else {
            $query .= " WHERE p.status IN ('pendente', 'preparo')";
            $stmt = $conn->prepare($query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $pedidos = [];
        while ($row = $result->fetch_assoc()) {
            $pedidos[] = $row;
        }
        
        return $pedidos;
    }

    // Função para atualizar status do pedido
    public static function atualizarStatus($pedido_id, $novo_status) {
        $conn = Database::getInstance()->getConnection();
        
        $query = "UPDATE pedidos SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $novo_status, $pedido_id);
        
        return $stmt->execute();
    }

    // Função para cancelar pedido
    public static function cancelarPedido($pedido_id) {
        $conn = Database::getInstance()->getConnection();
        
        $query = "UPDATE pedidos SET status = 'cancelado' WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $pedido_id);
        
        return $stmt->execute();
    }

    // Função para buscar status do caixa
    public static function buscarStatusCaixa() {
        $conn = Database::getInstance()->getConnection();
        
        $query = "SELECT * FROM Caixa ORDER BY id DESC LIMIT 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return [
            'status' => 'fechado',
            'saldo_inicial' => 0,
            'entradas' => 0,
            'saidas' => 0,
            'saldo_atual' => 0
        ];
    }

    // Função para gerenciar caixa (abrir/fechar)
    public static function gerenciarCaixa($acao, $valor = 0) {
        $conn = Database::getInstance()->getConnection();
        $conn->begin_transaction();
        
        try {
            if ($acao === 'abrir') {
                // Verifica se já existe caixa aberto
                $caixaAberto = $conn->query("SELECT id FROM Caixa WHERE status = 'aberto'");
                if ($caixaAberto->num_rows > 0) {
                    throw new Exception("Já existe um caixa aberto");
                }
                
                $stmt = $conn->prepare("INSERT INTO Caixa 
                                      (status, saldo_inicial, saldo_atual, data_abertura) 
                                      VALUES ('aberto', ?, ?, NOW())");
                $stmt->bind_param("dd", $valor, $valor);
                $stmt->execute();
            } 
            elseif ($acao === 'fechar') {
                $stmt = $conn->prepare("UPDATE Caixa 
                                      SET status = 'fechado', 
                                          data_fechamento = NOW() 
                                      WHERE status = 'aberto'");
                $stmt->execute();
            }
            
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Erro ao gerenciar caixa: " . $e->getMessage());
            return false;
        }
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
}

// Funções de compatibilidade (opcional)
function buscarPedidos($status = null) {
    return FuncoesGerente::buscarPedidos($status);
}

function atualizarStatus($pedido_id, $novo_status) {
    return FuncoesGerente::atualizarStatus($pedido_id, $novo_status);
}

function buscarStatusCaixa() {
    return FuncoesGerente::buscarStatusCaixa();
}
?>