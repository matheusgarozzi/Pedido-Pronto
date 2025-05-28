<?php
class Database {
    private static $instance = null;
    private $connection;
    
    // Configurações do banco de dados
    private const DB_HOST = "localhost";
    private const DB_USER = "root";
    private const DB_PASS = "";
    private const DB_NAME = "PedidoProntoDB";
    
    private function __construct() {
        try {
            // Cria a conexão
            $this->connection = new mysqli(
                self::DB_HOST,
                self::DB_USER,
                self::DB_PASS,
                self::DB_NAME
            );
            
            // Verifica erros de conexão
            if ($this->connection->connect_error) {
                throw new Exception("Falha na conexão: " . $this->connection->connect_error);
            }
            
            // Configurações adicionais
            $this->connection->set_charset("utf8mb4");
            $this->connection->query("SET time_zone = '-03:00'"); // Fuso horário do Brasil
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw $e; // Re-lança a exceção para tratamento externo
        }
    }
    
    public static function getInstance() {
        if (!self::$instance || !self::$instance->connection->ping()) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
        self::$instance = null;
    }
    
    // Previne clonagem da instância
    private function __clone() {}
    
    // Previne desserialização
    public function __wakeup() {
        throw new Exception("Não é possível desserializar uma conexão com o banco de dados");
    }
    
    // Destrutor
    public function __destruct() {
        $this->closeConnection();
    }
}

/**
 * Função de compatibilidade para seu código existente
 * @return mysqli
 */
function getConnection() {
    return Database::getInstance()->getConnection();
}

// Teste automático da conexão quando o arquivo é incluído
try {
    $testConn = Database::getInstance()->getConnection();
    if (!$testConn->ping()) {
        throw new Exception("Teste de conexão falhou");
    }
} catch (Exception $e) {
    die("ERRO CRÍTICO: " . $e->getMessage());
}
?>