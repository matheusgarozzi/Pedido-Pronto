<?php
// CaixaManager.php

require_once '../Gerente/funcoesGerente.php'; // Mantém a inclusão para registrar ações

class CaixaManager {
    private $mysqli;

    public function __construct(mysqli $mysqli) {
        $this->mysqli = $mysqli;
    }

    public function abrirCaixa(string $responsavel, float $saldoInicial = 0.00): array {
        $stmt = $this->mysqli->prepare("SELECT id FROM Caixa WHERE status = 'aberto'");
        if (!$stmt) {
            error_log('Erro na preparação da consulta (abrirCaixa): ' . $this->mysqli->error);
            return ['success' => false, 'message' => 'Erro interno ao verificar o caixa.'];
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Já existe um caixa aberto. Por favor, feche-o antes de abrir um novo.'];
        }
        $stmt->close();

        $this->mysqli->begin_transaction();

        try {
            $stmt = $this->mysqli->prepare(
                "INSERT INTO Caixa (status, saldo_inicial, saldo_atual, data_abertura, responsavel)
                 VALUES ('aberto', ?, ?, NOW(), ?)"
            );
            if (!$stmt) {
                throw new Exception('Erro na preparação da inserção (abrirCaixa): ' . $this->mysqli->error);
            }
            $stmt->bind_param('dds', $saldoInicial, $saldoInicial, $responsavel);
            $stmt->execute();

            $caixaId = $this->mysqli->insert_id;

            $this->mysqli->commit();
            $caixaAberto = $this->getCaixaById($caixaId);
            
            // Registra a ação
            FuncoesGerente::registrarAcao($responsavel, "abriu o caixa", "ID: {$caixaId}, Saldo inicial: R$ " . number_format($saldoInicial, 2, ',', '.'));

            return ['success' => true, 'message' => 'Caixa aberto com sucesso!', 'caixa' => $caixaAberto];

        } catch (Exception $e) {
            $this->mysqli->rollback();
            error_log('Erro ao abrir o caixa: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao abrir o caixa: ' . $e->getMessage()];
        } finally {
            if (isset($stmt) && $stmt) {
                $stmt->close();
            }
        }
    }

    public function fecharCaixa(string $responsavel): array {
        $caixaAtual = $this->getCaixaAtual();

        if (!$caixaAtual) {
            return ['success' => false, 'message' => 'Não há caixa aberto para fechar.'];
        }

        $this->mysqli->begin_transaction();

        try {
            $stmt = $this->mysqli->prepare(
                "UPDATE Caixa SET status = 'fechado', data_fechamento = NOW() WHERE id = ?"
            );
            if (!$stmt) {
                throw new Exception('Erro na preparação da atualização (fecharCaixa): ' . $this->mysqli->error);
            }
            $stmt->bind_param('i', $caixaAtual['id']);
            $stmt->execute();

            $this->mysqli->commit();
            $caixaFechado = $this->getCaixaById($caixaAtual['id']);
            
            // Registra a ação
            FuncoesGerente::registrarAcao($responsavel, "fechou o caixa", "ID: {$caixaAtual['id']}");

            return ['success' => true, 'message' => 'Caixa fechado com sucesso!', 'caixa' => $caixaFechado];

        } catch (Exception $e) {
            $this->mysqli->rollback();
            error_log('Erro ao fechar o caixa: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao fechar o caixa: ' . $e->getMessage()];
        } finally {
            if (isset($stmt) && $stmt) {
                $stmt->close();
            }
        }
    }

    public function getCaixaAtual() {
        $stmt = $this->mysqli->prepare("SELECT * FROM Caixa WHERE status = 'aberto' ORDER BY id DESC LIMIT 1");
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

    public function getCaixaById(int $caixaId) {
        $stmt = $this->mysqli->prepare("SELECT * FROM Caixa WHERE id = ?");
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

    private function calcularValorTotalPedido(int $pedidoId): float {
        $stmt = $this->mysqli->prepare(
            "SELECT SUM(quantidade * preco_unitario) AS total_pedido
             FROM ItensPedido
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

    public function getTotalVendasCaixa(int $caixaId): float {
        $totalVendas = 0.00;
        $stmtPedidos = $this->mysqli->prepare(
            "SELECT id FROM Pedidos WHERE caixa_id = ? AND (status = 'pronto' OR status = 'entregue')"
        );
        if (!$stmtPedidos) {
            error_log('Erro na preparação (getTotalVendasCaixa - Pedidos): ' . $this->mysqli->error);
            return 0.00;
        }
        $stmtPedidos->bind_param('i', $caixaId);
        $stmtPedidos->execute();
        $resultPedidos = $stmtPedidos->get_result();
        
        while ($row = $resultPedidos->fetch_assoc()) {
            $totalVendas += $this->calcularValorTotalPedido($row['id']);
        }
        $stmtPedidos->close();

        return $totalVendas;
    }

    private function adicionarValorAoCaixa(float $valor): array {
        $caixaAtual = $this->getCaixaAtual();

        if (!$caixaAtual) {
            return ['success' => false, 'message' => 'Não há caixa aberto para adicionar valores.'];
        }

        $this->mysqli->begin_transaction();

        try {
            $novoSaldo = $caixaAtual['saldo_atual'] + $valor;

            $stmt = $this->mysqli->prepare(
                "UPDATE Caixa SET saldo_atual = ? WHERE id = ?"
            );
            if (!$stmt) {
                throw new Exception('Erro na preparação da atualização (adicionarValorAoCaixa): ' . $this->mysqli->error);
            }
            $stmt->bind_param('di', $novoSaldo, $caixaAtual['id']);
            $stmt->execute();

            $this->mysqli->commit();
            $caixaAtualizado = $this->getCaixaById($caixaAtual['id']);
            return ['success' => true, 'message' => 'Valor adicionado ao caixa com sucesso.', 'caixa' => $caixaAtualizado];

        } catch (Exception $e) {
            $this->mysqli->rollback();
            error_log('Erro ao adicionar valor ao caixa: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao adicionar valor ao caixa: ' . $e->getMessage()];
        } finally {
            if (isset($stmt) && $stmt) {
                $stmt->close();
            }
        }
    }

    public function finalizarPedidoEAdicionarAoCaixa(int $pedidoId, int $formaPagamentoId): array {
        $caixaAtual = $this->getCaixaAtual();
        if (!$caixaAtual) {
            return ['success' => false, 'message' => 'Não há caixa aberto para finalizar pedidos.'];
        }

        $this->mysqli->begin_transaction();

        try {
            $valorPedido = $this->calcularValorTotalPedido($pedidoId);
            if ($valorPedido <= 0) {
                $this->mysqli->rollback();
                return ['success' => false, 'message' => 'Não foi possível calcular o valor do pedido ou o pedido não tem itens.'];
            }

            $stmt = $this->mysqli->prepare(
                "UPDATE Pedidos SET status = 'pronto', caixa_id = ?, forma_pagamento_id = ? WHERE id = ? AND status != 'pronto'"
            );
            if (!$stmt) {
                throw new Exception('Erro na preparação da atualização do pedido: ' . $this->mysqli->error);
            }
            $stmt->bind_param('iii', $caixaAtual['id'], $formaPagamentoId, $pedidoId);
            $stmt->execute();

            if ($stmt->affected_rows == 0) {
                $this->mysqli->rollback();
                return ['success' => false, 'message' => 'Pedido não encontrado ou já está com status "pronto".'];
            }
            $stmt->close();

            $resultCaixa = $this->adicionarValorAoCaixa($valorPedido);
            if ($resultCaixa['success'] === false) {
                $this->mysqli->rollback();
                return ['success' => false, 'message' => 'Erro ao atualizar pedido e caixa: ' . $resultCaixa['message']];
            }

            $this->mysqli->commit();
            // Registra a ação (com um responsável genérico se não houver um usuário logado específico)
            FuncoesGerente::registrarAcao("Sistema/Gerente", "finalizou o pedido #{$pedidoId}", "Valor: R$ " . number_format($valorPedido, 2, ',', '.') . ", Forma Pagamento ID: {$formaPagamentoId}");


            return ['success' => true, 'message' => 'Pedido finalizado e valor adicionado ao caixa com sucesso.', 'caixa' => $resultCaixa['caixa'], 'valor_pedido' => $valorPedido];

        } catch (Exception $e) {
            $this->mysqli->rollback();
            error_log('Erro ao finalizar pedido e adicionar ao caixa: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao finalizar pedido e adicionar ao caixa: ' . $e->getMessage()];
        }
    }

    public function marcarPedidoComoEntregueERegistrarPagamento(int $pedidoId, int $formaPagamentoId): array {
        $caixaAtual = $this->getCaixaAtual();
        if (!$caixaAtual) {
            return ['success' => false, 'message' => 'Não há caixa aberto para finalizar pedidos.'];
        }

        $this->mysqli->begin_transaction();

        try {
            $valorPedido = $this->calcularValorTotalPedido($pedidoId);
            if ($valorPedido <= 0) {
                $this->mysqli->rollback();
                return ['success' => false, 'message' => 'Não foi possível calcular o valor do pedido ou o pedido não tem itens.'];
            }

            $stmt = $this->mysqli->prepare(
                "UPDATE Pedidos SET status = 'entregue', caixa_id = ?, forma_pagamento_id = ? WHERE id = ? AND status = 'pronto'"
            );
            if (!$stmt) {
                throw new Exception('Erro na preparação da atualização do pedido para entregue: ' . $this->mysqli->error);
            }
            $stmt->bind_param('iii', $caixaAtual['id'], $formaPagamentoId, $pedidoId);
            $stmt->execute();

            if ($stmt->affected_rows == 0) {
                $this->mysqli->rollback();
                return ['success' => false, 'message' => 'Pedido não encontrado ou já está com status diferente de "pronto" (status atual: ' . ($this->getPedidoStatus($pedidoId) ?? 'N/A') . ').'];
            }
            $stmt->close();

            $resultCaixa = $this->adicionarValorAoCaixa($valorPedido);
            if ($resultCaixa['success'] === false) {
                $this->mysqli->rollback();
                return ['success' => false, 'message' => 'Erro ao adicionar valor ao caixa: ' . $resultCaixa['message']];
            }

            $this->mysqli->commit();
            // Registra a ação (com um responsável genérico se não houver um usuário logado específico)
            FuncoesGerente::registrarAcao("Sistema/Gerente", "marcou pedido #{$pedidoId} como entregue", "Valor: R$ " . number_format($valorPedido, 2, ',', '.') . ", Forma Pagamento ID: {$formaPagamentoId}");


            return ['success' => true, 'message' => 'Pedido marcado como entregue e valor adicionado ao caixa com sucesso.', 'caixa' => $resultCaixa['caixa'], 'valor_pedido' => $valorPedido];

        } catch (Exception $e) {
            $this->mysqli->rollback();
            error_log('Erro ao marcar pedido como entregue e adicionar ao caixa: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao marcar pedido como entregue e adicionar ao caixa: ' . $e->getMessage()];
        }
    }

    private function getPedidoStatus(int $pedidoId): ?string {
        $stmt = $this->mysqli->prepare("SELECT status FROM Pedidos WHERE id = ?");
        if (!$stmt) {
            error_log('Erro na preparação (getPedidoStatus): ' . $this->mysqli->error);
            return null;
        }
        $stmt->bind_param('i', $pedidoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $status = $result->fetch_assoc();
        $stmt->close();
        return $status['status'] ?? null;
    }

    public function getHistoricoCaixas(): array {
        $result = $this->mysqli->query("SELECT * FROM Caixa ORDER BY data_abertura DESC");
        if (!$result) {
            error_log('Erro na consulta (getHistoricoCaixas): ' . $this->mysqli->error);
            return [];
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getFormasPagamento(): array {
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
    
    public function getTotalVendasPorFormaPagamento(int $caixaId): array {
        $vendasPorForma = [];

        $formasPagamento = $this->getFormasPagamento();
        foreach ($formasPagamento as $forma) {
            $vendasPorForma[$forma['nome']] = 0.00;
        }

        $stmtPedidos = $this->mysqli->prepare(
            "SELECT p.id AS pedido_id, fp.nome AS forma_pagamento_nome
             FROM Pedidos p
             JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
             WHERE p.caixa_id = ? AND (p.status = 'pronto' OR p.status = 'entregue')"
        );
        if (!$stmtPedidos) {
            error_log('Erro na preparação (getTotalVendasPorFormaPagamento - Pedidos): ' . $this->mysqli->error);
            return $vendasPorForma;
        }
        $stmtPedidos->bind_param('i', $caixaId);
        $stmtPedidos->execute();
        $resultPedidos = $stmtPedidos->get_result();

        while ($pedido = $resultPedidos->fetch_assoc()) {
            $valorPedido = $this->calcularValorTotalPedido($pedido['pedido_id']);
            if (isset($vendasPorForma[$pedido['forma_pagamento_nome']])) {
                $vendasPorForma[$pedido['forma_pagamento_nome']] += $valorPedido;
            } else {
                $vendasPorForma[$pedido['forma_pagamento_nome']] = $valorPedido;
            }
        }
        $stmtPedidos->close();

        return $vendasPorForma;
    }
}