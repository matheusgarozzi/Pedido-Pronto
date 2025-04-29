<?php
function conectar() {
    $host = 'localhost';
    $usuario = 'root';
    $senha = ''; // deixe vazio se não colocou senha no XAMPP
    $banco = 'PedidoProntoDB';

    $conn = new mysqli($host, $usuario, $senha, $banco);

    if ($conn->connect_error) {
        die('Erro de conexão: ' . $conn->connect_error);
    }

    return $conn;
}

function buscarStatusCaixa() {
    $conn = conectar();
    
    // Verifique se a tabela Caixa existe
    $tabelaExiste = $conn->query("SHOW TABLES LIKE 'Caixa'")->num_rows > 0;
    
    if (!$tabelaExiste) {
        return [
            'status' => 'fechado',
            'saldo_inicial' => 0,
            'entradas' => 0,
            'saidas' => 0,
            'saldo_atual' => 0
        ];
    }
    
    // Use a coluna correta (provavelmente 'id' em vez de 'caixa_id')
    $sql = "SELECT * FROM Caixa ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        // Retorna valores padrão se não houver registro
        return [
            'status' => 'fechado',
            'saldo_inicial' => 0,
            'entradas' => 0,
            'saidas' => 0,
            'saldo_atual' => 0
        ];
    }
}

function atualizarCaixa($acao, $valor = 0) {
    $conn = conectar();
    $valor = floatval($valor);
    $caixaAtual = buscarStatusCaixa();
    
    switch ($acao) {
        case 'abrir':
            $sql = "INSERT INTO caixa (status, saldo_inicial, saldo_atual, data_abertura) 
                    VALUES ('aberto', $valor, $valor, NOW())";
            break;
            
        case 'fechar':
            $sql = "UPDATE caixa SET 
                    status = 'fechado', 
                    data_fechamento = NOW() 
                    WHERE status = 'aberto'";
            break;
            
        case 'entrada':
            $novoSaldo = $caixaAtual['saldo_atual'] + $valor;
            $sql = "UPDATE caixa SET 
                    entradas = entradas + $valor,
                    saldo_atual = $novoSaldo
                    WHERE status = 'aberto'";
            break;
            
        case 'saida':
            $novoSaldo = $caixaAtual['saldo_atual'] - $valor;
            $sql = "UPDATE caixa SET 
                    saidas = saidas + $valor,
                    saldo_atual = $novoSaldo
                    WHERE status = 'aberto'";
            break;
            
        default:
            $conn->close();
            return false;
    }
    
    $result = $conn->query($sql);
    $conn->close();
    return $result;
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

function cadastrarProduto($nome, $preco) {
    $conn = conectar();
    $stmt = $conn->prepare("INSERT INTO produtos (nome, preco) VALUES (?, ?)");
    $stmt->bind_param('sd', $nome, $preco);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}

function cadastrarPedido($cliente_id, $itens = []) {
    $conn = conectar();
    $conn->begin_transaction();
    
    try {
        // 1. Cria o pedido
        $stmt = $conn->prepare("INSERT INTO Pedidos (cliente_id) VALUES (?)");
        $stmt->bind_param('i', $cliente_id);
        $stmt->execute();
        $pedido_id = $conn->insert_id;
        $stmt->close();
        
        // 2. Adiciona os itens do pedido
        foreach ($itens as $item) {
            $stmt = $conn->prepare("INSERT INTO ItensPedido 
                                  (pedido_id, produto_id, quantidade, preco_unitario) 
                                  VALUES (?, ?, ?, ?)");
            
            // Obtém o preço atual do produto
            $preco = $conn->query("SELECT preco FROM Produtos WHERE id = {$item['produto_id']}")
                     ->fetch_assoc()['preco'];
            
            $stmt->bind_param('iiid', $pedido_id, $item['produto_id'], 
                            $item['quantidade'], $preco);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        return $pedido_id;
    } catch (Exception $e) {
        $conn->rollback();
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
            c.nome AS cliente_nome,
            SUM(ip.quantidade * ip.preco_unitario) AS total_pedido,
            GROUP_CONCAT(pr.nome SEPARATOR ', ') AS produtos,
            SUM(ip.quantidade) AS quantidade_total
        FROM Pedidos p
        JOIN Clientes c ON p.cliente_id = c.id
        LEFT JOIN ItensPedido ip ON ip.pedido_id = p.id
        LEFT JOIN Produtos pr ON pr.id = ip.produto_id
        GROUP BY p.id, p.status, p.data_pedido, c.nome
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
    $result = $conn->query("SELECT id, nome FROM clientes ORDER BY nome");

    $clientes = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }
        $result->free();
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

function atualizarStatus($pedido_id, $status) {
    $conn = conectar();
    $stmt = $conn->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $pedido_id);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}

?>
