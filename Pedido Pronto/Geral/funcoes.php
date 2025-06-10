<?php
// Geral/funcoes.php (ou o caminho correto para seu funcoes.php principal)

require_once 'conexao.php'; // Certifique-se de que 'conexao.php' está no mesmo diretório ou o caminho está correto.

function conectar() {
    $host = 'localhost';
    $usuario = 'root';
    $senha = ''; 
    $banco = 'PedidoProntoDB';

    $conn = new mysqli($host, $usuario, $senha, $banco);

    if ($conn->connect_error) {
        die('Erro de conexão: ' . $conn->connect_error);
    }

    return $conn;
}

function buscarStatusCaixa() {
    $conn = conectar();
    $sql = "SELECT * FROM Caixa ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $dados = $result->fetch_assoc();
        $conn->close();
        return $dados;
    } else {
        $conn->close();
        return [
            'status' => 'fechado',
            'saldo_inicial' => 0,
            'entradas' => 0,
            'saidas' => 0,
            'saldo_atual' => 0
        ];
    }
}

function atualizarCaixa($acao, $valor) {
    $conn = conectar();

    if ($acao === 'abrir') {
        $stmt = $conn->prepare("INSERT INTO Caixa (status, saldo_inicial, saldo_atual, entradas, saidas, data_abertura, responsavel) 
                                VALUES ('aberto', ?, ?, 0, 0, NOW(), 'admin')");
        $stmt->bind_param('dd', $valor, $valor);
        $stmt->execute();
        $stmt->close();
    }

    if ($acao === 'fechar') {
        $caixa = buscarStatusCaixa();

        if ($caixa && $caixa['status'] === 'aberto') {
            $stmt = $conn->prepare("UPDATE Caixa 
                                    SET status = 'fechado', 
                                        data_fechamento = NOW(), 
                                        saldo_inicial = 0 
                                    WHERE id = ?");
            $stmt->bind_param('i', $caixa['id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->close();
}

function registrarEntrada($valor) {
    $conn = conectar();
    $caixa = buscarStatusCaixa();

    if ($caixa && $caixa['status'] === 'aberto') {
        $stmt = $conn->prepare("UPDATE Caixa SET entradas = entradas + ?, saldo_atual = saldo_atual + ? WHERE id = ?");
        $stmt->bind_param('ddi', $valor, $valor, $caixa['id']);
        $stmt->execute();
        $stmt->close();
    }
}

function registrarSaida($valor) {
    $conn = conectar();
    $caixa = buscarStatusCaixa();

    if ($caixa && $caixa['status'] === 'aberto') {
        $stmt = $conn->prepare("UPDATE Caixa SET saidas = saidas + ?, saldo_atual = saldo_atual - ? WHERE id = ?");
        $stmt->bind_param('ddi', $valor, $valor, $caixa['id']);
        $stmt->execute();
        $stmt->close();
    }
}

function cadastrarCliente($nome, $telefone, $endereco) {
    $conn = conectar();
    $stmt = $conn->prepare("INSERT INTO clientes (nome, telefone, endereco) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $nome, $telefone, $endereco);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}

// FUNÇÃO CADASTRARPRODUTO ATUALIZADA para aceitar 'gramas'
function cadastrarProduto($nome, $descricao, $preco, $gramas, $ativo, $imagem = null) {
    $conn = conectar();
    // Adicionado 'gramas' na query INSERT
    $stmt = $conn->prepare("INSERT INTO Produtos (nome, descricao, preco, gramas, ativo, imagem) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Erro ao preparar cadastro de produto: " . $conn->error);
        $conn->close();
        return false;
    }
    // Adicionado 's' para gramas (se for string), ou 'i' para int, 'd' para double
    // Assumi 's' para flexibilidade, já que na imagem a coluna 'gramas' não aparece explicitamente.
    // Se 'gramas' for um número, use 'i' ou 'd' conforme o tipo no BD.
    $stmt->bind_param("ssdsis", $nome, $descricao, $preco, $gramas, $ativo, $imagem); 
    $result = $stmt->execute();
    if (!$result) {
        error_log("Erro ao executar cadastro de produto: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
    return $result;
}


function buscarProdutoPorId($id) {
    $conn = conectar();

    $stmt = $conn->prepare("SELECT id, nome, descricao, preco, gramas, ativo, imagem, estoque FROM Produtos WHERE id = ?");
    if (!$stmt) {
        error_log("Erro ao preparar busca de produto por ID: " . $conn->error);
        $conn->close();
        return null;
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produto = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $produto;
}

// FUNÇÃO EDITARPRODUTO ATUALIZADA para aceitar 'gramas'
function editarProduto($id, $nome, $descricao, $preco, $gramas, $ativo, $imagem = null) {
    $conn = conectar();
    // Adicionado 'gramas = ?' na query UPDATE
    $sql = "UPDATE Produtos SET nome = ?, descricao = ?, preco = ?, gramas = ?, ativo = ?";
    // Adicionado 's' para gramas
    $types = "ssdsi"; 
    $params = [$nome, $descricao, $preco, $gramas, $ativo];

    if ($imagem !== null) { 
        $sql .= ", imagem = ?";
        $types .= "s";
        $params[] = $imagem;
    }

    $sql .= " WHERE id = ?";
    $types .= "i";
    $params[] = $id;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Erro ao preparar edição de produto: " . $conn->error);
        $conn->close();
        return false;
    }
    
    $stmt->bind_param($types, ...$params); 
    
    $result = $stmt->execute();
    if (!$result) {
        error_log("Erro ao executar edição de produto: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
    return $result;
}

// SUA FUNÇÃO CADASTRARPEDIDO (com o tempo_de_entrega)
function cadastrarPedido($cliente_id, $itens = [], $tempo_de_entrega = null) { 
    $conn = conectar(); 
    $conn->begin_transaction(); 

    error_log("DEBUG: cadastrarPedido - Tempo de entrega recebido (dentro da função): " . var_export($tempo_de_entrega, true)); 

    try {
        $stmt = $conn->prepare("INSERT INTO Pedidos (cliente_id, status, data_pedido, tempo_de_entrega) VALUES (?, 'pendente', NOW(), ?)"); 

        if ($stmt === false) { 
            error_log("ERRO FATAL: prepare do Pedidos falhou: " . $conn->error); 
            throw new Exception("Erro ao preparar a criação do pedido principal: " . $conn->error); 
        }

        error_log("DEBUG: cadastrarPedido - Binding params: cliente_id=" . $cliente_id . " (int), tempo_de_entrega=" . var_export($tempo_de_entrega, true) . " (int?)"); 
        
        $bind_result = $stmt->bind_param('ii', $cliente_id, $tempo_de_entrega); 
        if ($bind_result === false) { 
            error_log("ERRO FATAL: bind_param do Pedidos falhou: " . $stmt->error); 
            throw new Exception("Erro ao vincular parâmetros para o pedido principal: " . $stmt->error); 
        }

        if (!$stmt->execute()) { 
            error_log("ERRO FATAL: execute do Pedidos falhou: " . $stmt->sqlstate . " - " . $stmt->error); 
            throw new Exception("Erro ao criar pedido principal: " . $stmt->error); 
        }
        $pedido_id = $conn->insert_id; 
        $stmt->close(); 

        foreach ($itens as $item) { 
            $stmt_item = $conn->prepare("INSERT INTO ItensPedido
                                  (pedido_id, produto_id, quantidade, preco_unitario)
                                  VALUES (?, ?, ?, ?)"); 

            if ($stmt_item === false) { 
                error_log("ERRO FATAL: prepare do ItensPedido falhou: " . $conn->error); 
                throw new Exception("Erro ao preparar a criação do item do pedido: " . $conn->error); 
            }

            $preco = $conn->query("SELECT preco FROM Produtos WHERE id = {$item['produto_id']}")->fetch_assoc()['preco']; 

            $bind_item_result = $stmt_item->bind_param('iiid', $pedido_id, $item['produto_id'],
                            $item['quantidade'], $preco); 
            
            if ($bind_item_result === false) { 
                error_log("ERRO FATAL: bind_param do ItensPedido falhou: " . $stmt_item->error); 
                throw new Exception("Erro ao vincular parâmetros para o item do pedido: " . $stmt_item->error); 
            }

            if (!$stmt_item->execute()) { 
                error_log("ERRO FATAL ao inserir item do pedido: " . $stmt_item->sqlstate . " - " . $stmt_item->error); 
                throw new Exception("Erro ao adicionar item: " . $stmt_item->error); 
            }
            $stmt_item->close(); 
        }

        $conn->commit(); 
        return $pedido_id; 
    } catch (Exception $e) {
        if (isset($conn) && method_exists($conn, 'rollback')) { 
            $conn->rollback(); 
        }
        error_log("Falha na transação de pedido (Exception): " . $e->getMessage()); 
        return false; 
    } finally {
        $conn->close(); 
    }
}

function buscarPedidos() {
    $conn = conectar();
    $sql = "
        SELECT 
            p.id, 
            p.status, 
            p.data_pedido,
            p.tempo_de_entrega,
            c.nome AS cliente_nome,
            SUM(ip.quantidade * ip.preco_unitario) AS total,
            GROUP_CONCAT(pr.nome SEPARATOR ', ') AS produtos
        FROM Pedidos p
        JOIN Clientes c ON p.cliente_id = c.id
        LEFT JOIN ItensPedido ip ON ip.pedido_id = p.id
        LEFT JOIN Produtos pr ON pr.id = ip.produto_id
        GROUP BY p.id, p.status, p.data_pedido, p.data_pedido, p.tempo_de_entrega, c.nome
        ORDER BY p.data_pedido DESC
    ";
    
    $result = $conn->query($sql);
    $pedidos = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pedidos[] = $row;
        }
        $result->free();
    }
    
    $conn->close();
    return $pedidos;
}

function buscarClientes() {
    $conn = conectar();
    
    $sql = "SELECT id, nome, telefone, endereco, 
            DATE_FORMAT(data_cadastro, '%d/%m/%Y %H:%i') as data_cadastro 
            FROM clientes 
            ORDER BY data_cadastro DESC";
            
    $result = $conn->query($sql);
    $clientes = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }
        $result->free();
    } else {
        error_log("Erro ao buscar clientes: " . $conn->error);
    }
    
    $conn->close();
    return $clientes;
}

function buscarProdutos() {
    $conn = conectar();
    $result = $conn->query("SELECT id, nome, preco FROM produtos ORDER BY nome");

    $produtos = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $produtos[] = $row;
        }
        $result->free();
    }
    $conn->close();
    return $produtos;
}

// FUNÇÃO BUSCARPRODUTOSATIVOS ATUALIZADA para selecionar 'gramas'
function buscarProdutosAtivos() {
    $conn = conectar();
    // Adicionado 'gramas' na query SELECT
    $sql = "SELECT id, nome, descricao, preco, gramas, imagem, estoque FROM produtos WHERE ativo = 1";
    $result = $conn->query($sql);
    
    $produtos = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $produtos[] = $row;
        }
        $result->free();
    }
    
    $conn->close();
    return $produtos;
}

function atualizarStatus($pedido_id, $status) {
    $conn = conectar();
    $stmt = $conn->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $pedido_id);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}

function excluirCliente($id) {
    $conn = conectar();
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}

function excluirPedido($id) {
    $conn = conectar();

    $stmt = $conn->prepare("DELETE FROM ItensPedido WHERE pedido_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM Pedidos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $conn->close();
    return true;
}

function excluirProduto($id) {
    $conn = conectar();
    
    // Verifica se existem pedidos relacionados ao produto
    $result = $conn->query("SELECT COUNT(*) as count FROM ItensPedido WHERE produto_id = $id");
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        return ['success' => false, 'message' => 'Não é possível excluir o produto, pois ele está relacionado a pedidos.'];
    } else {
        // Exclui o produto
        $stmt = $conn->prepare("DELETE FROM Produtos WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Produto excluído com sucesso!'];
        } else {
            return ['success' => false, 'message' => 'Erro ao excluir o produto: ' . $stmt->error];
        }
    }
}

// Bloco POST para Excluir Produto (já existe e está correto)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Verifica se a ação é para excluir um produto
    if ($data && isset($data['acao']) && $data['acao'] === 'excluir_produto') {
        $id = intval($data['produto_id']);
        $response = excluirProduto($id); 
        echo json_encode($response);
        exit;
    }
}

function adicionarEstoque($produto_id, $quantidade) {
    $conn = conectar();
    $stmt = $conn->prepare("UPDATE produtos SET estoque = estoque + ? WHERE id = ?");
    if(!$stmt) {
        error_log("Erro no prepare: " . $conn->error);
        $conn->close();
        return false;
    }
    $stmt->bind_param("ii", $quantidade, $produto_id);
    $result = $stmt->execute();
    if(!$result) {
        error_log("Erro na execução: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
    return $result;
}
?>