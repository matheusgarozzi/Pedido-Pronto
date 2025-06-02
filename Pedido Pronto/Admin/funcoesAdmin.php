<?php
require_once __DIR__ . '/../Geral/conexao.php';

class AdminFunctions {
    // =============================================
    // FUNÇÕES DE CONTROLE DO CAIXA (Apenas Leitura/Consulta)
    // =============================================
    
    public static function obterUltimoCaixa() {
        try {
            $conn = Database::getInstance()->getConnection();
            $query = "SELECT * FROM Caixa ORDER BY id DESC LIMIT 1";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            return ['status' => 'fechado', 'saldo_inicial' => 0, 'entradas' => 0, 'saidas' => 0, 'saldo_atual' => 0, 'id' => null]; // Retorno padrão com valores zero
            
        } catch (Exception $e) {
            error_log("Erro ao obter caixa: " . $e->getMessage());
            return ['status' => 'fechado', 'saldo_inicial' => 0, 'entradas' => 0, 'saidas' => 0, 'saldo_atual' => 0, 'id' => null]; // Retorno seguro
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
?>