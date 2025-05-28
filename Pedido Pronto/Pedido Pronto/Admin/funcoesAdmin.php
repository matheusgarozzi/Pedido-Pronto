<?php
require_once __DIR__ . '/../Geral/conexao.php';

class AdminFunctions {
    // =============================================
    // FUNÇÕES DE CONTROLE DO CAIXA
    // =============================================
    
    public static function obterUltimoCaixa() {
    try {
        $conn = Database::getInstance()->getConnection();
        $query = "SELECT * FROM Caixa ORDER BY id DESC LIMIT 1";
        $result = $conn->query($query);
        
        // Corrige o problema de fetch_assoc() em array
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return ['status' => 'fechado']; // Retorno padrão
        
    } catch (Exception $e) {
        error_log("Erro ao obter caixa: " . $e->getMessage());
        return ['status' => 'fechado']; // Retorno seguro
    }
}

    public static function abrirCaixa($valor_inicial) {
        try {
            $conn = Database::getInstance()->getConnection();
            
            $query = "INSERT INTO Caixa (status, data_abertura, saldo_inicial) 
                      VALUES ('aberto', NOW(), ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("d", $valor_inicial);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao abrir caixa: " . $stmt->error);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public static function fecharCaixa() {
        try {
            $conn = Database::getInstance()->getConnection();
            
            // Primeiro verifica se há caixa aberto
            $caixa = self::obterUltimoCaixa();
            if ($caixa['status'] !== 'aberto') {
                throw new Exception("Não há caixa aberto para fechar");
            }
            
            // Calcula o saldo total (exemplo - ajuste conforme sua lógica)
            $query = "UPDATE Caixa 
                      SET status = 'fechado', 
                          data_fechamento = NOW(),
                          saldo_final = (SELECT SUM(valor) FROM movimentacoes WHERE caixa_id = ?)
                      WHERE id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $caixa['id'], $caixa['id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao fechar caixa: " . $stmt->error);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    // =============================================
    // FUNÇÕES DE GERENCIAMENTO DE USUÁRIOS
    // =============================================
    
    public static function obterUsuarios($filtro = '') {
        try {
            $conn = Database::getInstance()->getConnection();
            
            $query = "SELECT id, username, nivel_acesso, ativo 
                      FROM Usuarios";
            
            // Adiciona filtro se fornecido
            if (!empty($filtro)) {
                $filtro = "%$filtro%";
                $query .= " WHERE username LIKE ? OR nivel_acesso LIKE ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $filtro, $filtro);
            } else {
                $stmt = $conn->prepare($query);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $usuarios = [];
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
            
            return $usuarios;
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public static function cadastrarUsuario($dados) {
        try {
            $conn = Database::getInstance()->getConnection();
            
            $query = "INSERT INTO Usuarios 
                      (username, password_hash, nivel_acesso, ativo) 
                      VALUES (?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            
            // Criptografa a senha
            $password_hash = password_hash($dados['senha'], PASSWORD_BCRYPT);
            
            $stmt->bind_param("sssi", 
                $dados['username'],
                $password_hash,
                $dados['nivel_acesso'],
                $dados['ativo']
            );
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
       public static function gerarRelatorio($tipo, $data_inicio, $data_fim) {
        // Implementação de relatórios (personalize conforme necessário)
    }
    
    public static function buscarEstatisticas() {
        // Implementação de estatísticas
    }
}

// Funções de compatibilidade (para não quebrar seu código existente)
function obterUltimoCaixa() {
    return AdminFunctions::obterUltimoCaixa();
}

function obterUsuarios() {
    return AdminFunctions::obterUsuarios();
}

function buscarPedidos() {
    return AdminFunctions::buscarPedidos();
}

function atualizarStatusPedido($pedido_id, $novo_status) {
    return AdminFunctions::atualizarStatusPedido($pedido_id, $novo_status);
}
?>