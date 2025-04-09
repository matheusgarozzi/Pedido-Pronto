
CREATE TABLE Funcionarios (
    funcionario_id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    telefone VARCHAR(15),
    email VARCHAR(100),
    cargo VARCHAR(50) NOT NULL,
    data_contratacao DATE NOT NULL,
    salario_base DECIMAL(10,2) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE
);


CREATE TABLE Mesas (
    mesa_id INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(10) UNIQUE NOT NULL,
    capacidade INT NOT NULL,
    localizacao VARCHAR(50),
    disponivel BOOLEAN DEFAULT TRUE
);


CREATE TABLE Clientes (
    cliente_id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(15),
    email VARCHAR(100),
    data_cadastro DATE,
    observacoes TEXT
);


CREATE TABLE Categorias (
    categoria_id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    descricao TEXT
);

-
CREATE TABLE Produtos (
    produto_id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    categoria_id INT NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    custo DECIMAL(10,2),
    disponivel BOOLEAN DEFAULT TRUE,
    tempo_preparo INT,
    FOREIGN KEY (categoria_id) REFERENCES Categorias(categoria_id)
);


CREATE TABLE Pedidos (
    pedido_id INT PRIMARY KEY AUTO_INCREMENT,
    mesa_id INT NOT NULL,
    funcionario_id INT NOT NULL,
    cliente_id INT,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('aberto', 'em_preparo', 'pronto', 'entregue', 'pago', 'cancelado') DEFAULT 'aberto',
    observacoes TEXT,
    FOREIGN KEY (mesa_id) REFERENCES Mesas(mesa_id),
    FOREIGN KEY (funcionario_id) REFERENCES Funcionarios(funcionario_id),
    FOREIGN KEY (cliente_id) REFERENCES Clientes(cliente_id)
);


CREATE TABLE ItensPedido (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL,
    observacoes TEXT,
    status ENUM('solicitado', 'em_preparo', 'pronto', 'entregue', 'cancelado') DEFAULT 'solicitado',
    FOREIGN KEY (pedido_id) REFERENCES Pedidos(pedido_id),
    FOREIGN KEY (produto_id) REFERENCES Produtos(produto_id)
);


CREATE TABLE Pagamentos (
    pagamento_id INT PRIMARY KEY AUTO_INCREMENT,
    pedido_id INT NOT NULL,
    forma_pagamento ENUM('dinheiro', 'cartao_debito', 'cartao_credito', 'pix', 'outro') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    troco DECIMAL(10,2),
    FOREIGN KEY (pedido_id) REFERENCES Pedidos(pedido_id)
);


CREATE TABLE Despesas (
    despesa_id INT PRIMARY KEY AUTO_INCREMENT,
    tipo VARCHAR(50) NOT NULL,
    descricao TEXT,
    valor DECIMAL(10,2) NOT NULL,
    data DATE NOT NULL,
    responsavel_id INT,
    pago BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (responsavel_id) REFERENCES Funcionarios(funcionario_id)
);


CREATE TABLE Turnos (
    turno_id INT PRIMARY KEY AUTO_INCREMENT,
    funcionario_id INT NOT NULL,
    data DATE NOT NULL,
    hora_entrada TIME NOT NULL,
    hora_saida TIME,
    observacoes TEXT,
    FOREIGN KEY (funcionario_id) REFERENCES Funcionarios(funcionario_id)
);


CREATE TABLE Estoque (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    quantidade DECIMAL(10,3) NOT NULL,
    unidade_medida VARCHAR(10),
    estoque_minimo DECIMAL(10,3),
    custo_unitario DECIMAL(10,2),
    fornecedor VARCHAR(100)
);  