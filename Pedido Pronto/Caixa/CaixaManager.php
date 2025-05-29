<?php

// CaixaManager.php - COM FORMULARIO DE PAGAMENTO

class CaixaManager {
    private $mysqli; // Agora é um objeto mysqli

    // Construtor: recebe a conexão mysqli
    public function __construct(mysqli $mysqli) { // Tipo agora é mysqli
        $this->mysqli = $mysqli;
    }

    // --- Métodos de Gestão de Caixa ---

    /**
     * Abre um novo caixa.
     * @param string $responsavel Nome do responsável pelo caixa.
     * @param float $saldoInicial Saldo inicial ao abrir o caixa.
     * @return array|bool Retorna os dados do caixa aberto ou um array de erro.
     */
    public function abrirCaixa(string $responsavel, float $saldoInicial = 0.00) {
        // Verifica se já existe um caixa aberto
        $stmt = $this->mysqli->prepare("SELECT * FROM caixa WHERE status = 'aberto'");
        if (!$stmt) {
            return ['error' => 'Erro na preparação da consulta (abrirCaixa): ' . $this->mysqli->error];
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return ['error' => 'Já existe um caixa aberto. Por favor, feche-o antes de abrir um novo.'];
        }
        $stmt->close();

        // Inicia a transação
        $this->mysqli->begin_transaction();

        try {
            $stmt = $this->mysqli->prepare(
                "INSERT INTO caixa (status, saldo_inicial, saldo_atual, data_abertura, responsavel)
                 VALUES ('aberto', ?, ?, NOW(), ?)"
            );
            if (!$stmt) {
                throw new Exception('Erro na preparação da inserção (abrirCaixa): ' . $this->mysqli->error);
            }
            $stmt->bind_param('dds', $saldoInicial, $saldoInicial, $responsavel); // 'd' para double/decimal, 's' para string
            $stmt->execute();

            $caixaId = $this->mysqli->insert_id;

            $this->mysqli->commit();

            return $this->getCaixaAtual(); // Retorna os dados do caixa recém-aberto

        } catch (Exception $e) { // Captura Exception, pois mysqli::prepare não lança PDOException
            $this->mysqli->rollback();
            return ['error' => 'Erro ao abrir o caixa: ' . $e->getMessage()];
        } finally {
            if (isset($stmt) && $stmt) {
                $stmt->close();
            }
        }
    }

    /**
     * Fecha o caixa atualmente aberto.
     * @param string $responsavel Nome do responsável (para confirmação).
     * @return array|bool Retorna os dados do caixa fechado ou um array de erro.
     */
    public function fecharCaixa(string $responsavel) {
        $caixaAtual = $this->getCaixaAtual();

        if (!$caixaAtual) {
            return ['error' => 'Não há caixa aberto para fechar.'];
        }

        // if ($caixaAtual['responsavel'] !== $responsavel) { // Descomente se quiser forçar o responsável a ser o mesmo
        //     return ['error' => 'O nome do responsável informado não corresponde ao responsável do caixa aberto.'];
        // }

        $this->mysqli->begin_transaction();

        try {
            $stmt = $this->mysqli->prepare(
                "UPDATE caixa SET status = 'fechado', data_fechamento = NOW() WHERE id = ?"
            );
            if (!$stmt) {
                throw new Exception('Erro na preparação da atualização (fecharCaixa): ' . $this->mysqli->error);
            }
            $stmt->bind_param('i', $caixaAtual['id']); // 'i' para integer
            $stmt->execute();

            $this->mysqli->commit();

            return $this->getCaixaById($caixaAtual['id']); // Retorna os dados do caixa fechado

        } catch (Exception $e) {
            $this->mysqli->rollback();
            return ['error' => 'Erro ao fechar o caixa: ' . $e->getMessage()];
        } finally {
            if (isset($stmt) && $stmt) {
                $stmt->close();
            }
        }
    }

    /**
     * Obtém os dados do caixa atualmente aberto.
     * @return array|false Retorna os dados do caixa ou false se não houver caixa aberto.
     */
    public function getCaixaAtual() {
        $stmt = $this->mysqli->prepare("SELECT * FROM caixa WHERE status = 'aberto' ORDER BY id DESC LIMIT 1");
        if (!$stmt) {
            error_log('Erro na preparação (getCaixaAtual): ' . $this->mysqli->error);
            return false;
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $caixa = $result->fetch_assoc();
        $stmt->close();
        return $caixa;
    }

    /**
     * Obtém os dados de um caixa específico pelo ID.
     * @param int $caixaId ID do caixa.
     * @return array|false Retorna os dados do caixa ou false se não encontrado.
     */
    public function getCaixaById(int $caixaId) {
        $stmt = $this->mysqli->prepare("SELECT * FROM caixa WHERE id = ?");
        if (!$stmt) {
            error_log('Erro na preparação (getCaixaById): ' . $this->mysqli->error);
            return false;
        }
        $stmt->bind_param('i', $caixaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $caixa = $result->fetch_assoc();
        $stmt->close();
        return $caixa;
    }

    /**
     * Calcula o valor total de um pedido específico.
     * @param int $pedidoId ID do pedido.
     * @return float O valor total do pedido.
     */
    private function calcularValorTotalPedido(int $pedidoId) {
        $stmt = $this->mysqli->prepare(
            "SELECT SUM(quantidade * preco_unitario) AS total_pedido
             FROM itenspedido
             WHERE pedido_id = ?"
        );
        if (!$stmt) {
            error_log('Erro na preparação (calcularValorTotalPedido): ' . $this->mysqli->error);
            return 0.00;
        }
        $stmt->bind_param('i', $pedidoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return (float) ($data['total_pedido'] ?? 0.00);
    }

    /**
     * Visualiza o valor total dos pedidos vendidos (com status 'pronto') durante o período de um caixa.
     * Os pedidos devem estar vinculados ao caixa.
     * @param int $caixaId ID do caixa para consultar.
     * @return float O valor total dos pedidos vendidos.
     */
    public function getTotalVendasCaixa(int $caixaId) {
        // Primeiro, obtenha todos os IDs de pedidos associados a este caixa que estão "pronto"
        $stmtPedidos = $this->mysqli->prepare(
            "SELECT id FROM pedidos WHERE caixa_id = ? AND status = 'pronto'"
        );
        if (!$stmtPedidos) {
            error_log('Erro na preparação (getTotalVendasCaixa - pedidos): ' . $this->mysqli->error);
            return 0.00;
        }
        $stmtPedidos->bind_param('i', $caixaId);
        $stmtPedidos->execute();
        $resultPedidos = $stmtPedidos->get_result();
        $pedidosIds = [];
        while ($row = $resultPedidos->fetch_assoc()) {
            $pedidosIds[] = $row['id'];
        }
        $stmtPedidos->close();

        $totalVendas = 0.00;
        foreach ($pedidosIds as $pedidoId) {
            $totalVendas += $this->calcularValorTotalPedido($pedidoId);
        }
        return $totalVendas;
    }

    /**
     * Adiciona um valor ao saldo atual do caixa.
     * Este método é chamado internamente quando um pedido é finalizado.
     * @param float $valor O valor a ser adicionado.
     * @return array|bool Retorna os dados do caixa atualizado ou um array de erro.
     */
    private function adicionarValorAoCaixa(float $valor) {
        $caixaAtual = $this->getCaixaAtual();

        if (!$caixaAtual) {
            return ['error' => 'Não há caixa aberto para adicionar valores.'];
        }

        $this->mysqli->begin_transaction();

        try {
            $novoSaldo = $caixaAtual['saldo_atual'] + $valor;

            $stmt = $this->mysqli->prepare(
                "UPDATE caixa SET saldo_atual = ? WHERE id = ?"
            );
            if (!$stmt) {
                throw new Exception('Erro na preparação da atualização (adicionarValorAoCaixa): ' . $this->mysqli->error);
            }
            $stmt->bind_param('di', $novoSaldo, $caixaAtual['id']); // 'd' para double/decimal, 'i' para integer
            $stmt->execute();

            $this->mysqli->commit();

            return $this->getCaixaById($caixaAtual['id']);

        } catch (Exception $e) {
            $this->mysqli->rollback();
            return ['error' => 'Erro ao adicionar valor ao caixa: ' . $e->getMessage()];
        } finally {
            if (isset($stmt) && $stmt) {
                $stmt->close();
            }
        }
    }

    /**
     * Finaliza um pedido (marca como 'pronto'), adiciona seu valor total ao caixa atual
     * e registra a forma de pagamento.
     * @param int $pedidoId ID do pedido a ser finalizado.
     * @param int $formaPagamentoId ID da forma de pagamento utilizada.
     * @return array|bool Retorna o status da operação ou um array de erro.
     */
    public function finalizarPedidoEAdicionarAoCaixa(int $pedidoId, int $formaPagamentoId) { // Adicionado $formaPagamentoId
        $caixaAtual = $this->getCaixaAtual();
        if (!$caixaAtual) {
            return ['error' => 'Não há caixa aberto para finalizar pedidos.'];
        }

        $this->mysqli->begin_transaction();

        try {
            // 1. Calcular o valor total do pedido
            $valorPedido = $this->calcularValorTotalPedido($pedidoId);
            if ($valorPedido <= 0) {
                $this->mysqli->rollback();
                return ['error' => 'Não foi possível calcular o valor do pedido ou o pedido não tem itens.'];
            }

            // 2. Atualizar o status do pedido para 'pronto', vincular ao caixa atual e registrar a forma de pagamento
            $stmt = $this->mysqli->prepare(
                "UPDATE pedidos SET status = 'pronto', caixa_id = ?, forma_pagamento_id = ? WHERE id = ? AND status != 'pronto'"
            );
            if (!$stmt) {
                throw new Exception('Erro na preparação da atualização do pedido: ' . $this->mysqli->error);
            }
            $stmt->bind_param('iii', $caixaAtual['id'], $formaPagamentoId, $pedidoId); // 'iii' para 3 inteiros
            $stmt->execute();

            if ($stmt->affected_rows == 0) { // Verifica se a atualização realmente ocorreu
                $this->mysqli->rollback();
                return ['error' => 'Pedido não encontrado ou já está com status "pronto".'];
            }
            $stmt->close(); // Fechar o stmt após o uso

            // 3. Adicionar o valor do pedido ao saldo atual do caixa
            $resultCaixa = $this->adicionarValorAoCaixa($valorPedido);

            if (isset($resultCaixa['error'])) {
                $this->mysqli->rollback();
                return ['error' => 'Erro ao atualizar pedido e caixa: ' . $resultCaixa['error']];
            }

            $this->mysqli->commit();
            return ['success' => 'Pedido finalizado e valor adicionado ao caixa com sucesso.', 'caixa' => $resultCaixa, 'valor_pedido' => $valorPedido];

        } catch (Exception $e) {
            $this->mysqli->rollback();
            return ['error' => 'Erro ao finalizar pedido e adicionar ao caixa: ' . $e->getMessage()];
        }
    }

    // --- Métodos de Utilidade ---

    /**
     * Retorna o histórico de caixas (abertos e fechados).
     * @return array Lista de caixas.
     */
    public function getHistoricoCaixas() {
        $result = $this->mysqli->query("SELECT * FROM caixa ORDER BY data_abertura DESC");
        if (!$result) {
            error_log('Erro na consulta (getHistoricoCaixas): ' . $this->mysqli->error);
            return [];
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtém todas as formas de pagamento ativas.
     * @return array Lista de formas de pagamento.
     */
    public function getFormasPagamento() {
        $stmt = $this->mysqli->prepare("SELECT id, nome FROM formas_pagamento WHERE ativo = TRUE ORDER BY nome ASC");
        if (!$stmt) {
            error_log('Erro na preparação (getFormasPagamento): ' . $this->mysqli->error);
            return [];
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $formasPagamento = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $formasPagamento;
    }
    /**
     * Obtém o total de vendas por forma de pagamento para um caixa específico.
     * @param int $caixaId ID do caixa.
     * @return array Associativo com o total de vendas por forma de pagamento.
     */

public function getTotalVendasPorFormaPagamento(int $caixaId) {
        $vendasPorForma = [];

        // Query para obter todos os pedidos 'pronto' para o caixa
        $stmtPedidos = $this->mysqli->prepare(
            "SELECT p.id AS pedido_id, p.forma_pagamento_id, fp.nome AS forma_pagamento_nome
             FROM pedidos p
             JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
             WHERE p.caixa_id = ? AND p.status = 'pronto'"
        );
        if (!$stmtPedidos) {
            error_log('Erro na preparação (getTotalVendasPorFormaPagamento - pedidos): ' . $this->mysqli->error);
            return [];
        }
        $stmtPedidos->bind_param('i', $caixaId);
        $stmtPedidos->execute();
        $resultPedidos = $stmtPedidos->get_result();

        // Inicializa os totais
        $formasPagamento = $this->getFormasPagamento();
        foreach ($formasPagamento as $forma) {
            $vendasPorForma[$forma['nome']] = 0.00;
        }

        // Soma os valores dos pedidos
        while ($pedido = $resultPedidos->fetch_assoc()) {
            $valorPedido = $this->calcularValorTotalPedido($pedido['pedido_id']);
            if (isset($vendasPorForma[$pedido['forma_pagamento_nome']])) {
                $vendasPorForma[$pedido['forma_pagamento_nome']] += $valorPedido;
            } else {
                // Caso haja uma forma de pagamento no pedido que não esteja ativa ou foi removida
                $vendasPorForma[$pedido['forma_pagamento_nome']] = $valorPedido;
            }
        }
        $stmtPedidos->close();

        return $vendasPorForma;
    }
}

// Remova as linhas de definição de DB_HOST, DB_NAME, DB_USER, DB_PASS e o bloco try-catch da conexão PDO ou mysqli direto.
// Essas configurações e a conexão agora vêm do seu 'conexao.php'.
?>