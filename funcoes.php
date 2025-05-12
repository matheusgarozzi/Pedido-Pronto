<?php
require_once 'conexao.php'; // Importa a conexão

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
    $conn = conectar(); // abre nova conexão

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
            // Fecha o caixa e zera o saldo_inicial
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
    global $conn;
    $caixa = buscarStatusCaixa();

    if ($caixa && $caixa['status'] === 'aberto') {
        $sql = "UPDATE Caixa SET entradas = entradas + :valor, saldo_atual = saldo_atual + :valor WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':valor', $valor);
        $stmt->bindValue(':id', $caixa['id']);
        $stmt->execute();
    }
}

function registrarSaida($valor) {
    global $conn;
    $caixa = buscarStatusCaixa();

    if ($caixa && $caixa['status'] === 'aberto') {
        $sql = "UPDATE Caixa SET saidas = saidas + :valor, saldo_atual = saldo_atual - :valor WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':valor', $valor);
        $stmt->bindValue(':id', $caixa['id']);
        $stmt->execute();
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
        $stmt = $conn->prepare("INSERT INTO Pedidos (cliente_id) VALUES (?)");
        $stmt->bind_param('i', $cliente_id);
        $stmt->execute();
        $pedido_id = $conn->insert_id;
        $stmt->close();
        
        foreach ($itens as $item) {
            $stmt = $conn->prepare("INSERT INTO ItensPedido 
                                  (pedido_id, produto_id, quantidade, preco_unitario) 
                                  VALUES (?, ?, ?, ?)");
            
            $preco = $conn->query("SELECT preco FROM Produtos WHERE id = {$item['produto_id']}")->fetch_assoc()['preco'];
            
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
    registrarEntrada($total_pedido); // Substitua $total_pedido pelo valor calculado do pedido

}

function buscarPedidos() {
    $conn = conectar();
    $sql = "
        SELECT 
            p.id, 
            p.status, 
            p.data_pedido,
            c.nome AS cliente_nome,
            SUM(ip.quantidade * ip.preco_unitario) AS total,
            GROUP_CONCAT(pr.nome SEPARATOR ', ') AS produtos
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

function buscarProdutosAtivos() {
    $conn = conectar();
    $sql = "SELECT id, nome, descricao, preco, imagem FROM produtos WHERE ativo = 1";
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

function editarItensPedido($pedido_id, $novosItens) {
    $conn = conectar();

    try {
        // Apaga os itens antigos do pedido
        $stmt = $conn->prepare("DELETE FROM ItensPedido WHERE pedido_id = ?");
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        $stmt->close();

        // Insere os novos itens
        foreach ($novosItens as $item) {
            // Buscar o preço atualizado do produto
            $stmt = $conn->prepare("SELECT preco FROM Produtos WHERE id = ?");
            $stmt->bind_param('i', $item['produto_id']);
            $stmt->execute();
            $stmt->bind_result($preco);
            $stmt->fetch();
            $stmt->close();

            // Inserir novo item no pedido
            $stmt = $conn->prepare("INSERT INTO ItensPedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiid', $pedido_id, $item['produto_id'], $item['quantidade'], $preco);
            $stmt->execute();
            $stmt->close();
        }

        $conn->close();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao editar itens do pedido: " . $e->getMessage());
        $conn->close();
        return false;
    }
}



?>