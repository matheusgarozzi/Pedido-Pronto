<?php
// Cardapio/adicionarcardapio.php

// Inclui o arquivo de funções que contém as operações de CRUD para produtos
require_once '../Geral/funcoes.php'; // Ajuste o caminho conforme a localização do seu 'funcoes.php'

$produtoParaEditar = null;
$idProdutoEdicao = null;
$mensagemErro = '';

// Lógica para carregar dados do produto para edição (se um ID for passado via GET)
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $idProdutoEdicao = intval($_GET['editar']);
    $produtoParaEditar = buscarProdutoPorId($idProdutoEdicao); // Usa a nova função
    if (!$produtoParaEditar) {
        $mensagemErro = "Produto não encontrado para edição.";
        $idProdutoEdicao = null; // Reseta se não encontrar
    }
}

// Processa o formulário quando enviado (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    // Converte o preço para float, tratando vírgula como decimal se necessário
    $preco = floatval(str_replace(',', '.', $_POST['preco'])); 
    $gramas = $_POST['gramas']; // Captura o valor de 'gramas'
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $success = false;

    // Lógica de upload da imagem
    // Mantém a imagem existente se não for enviada uma nova ou se houver erro no upload da nova
    $imagem = $produtoParaEditar['imagem'] ?? null; 
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0 && $_FILES['imagem']['size'] > 0) {
        $targetDir = "uploads/"; // Pasta onde as imagens serão salvas (relativo a 'adicionarcardapio.php')
        // Cria a pasta se não existir
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $nomeImagem = basename($_FILES["imagem"]["name"]);
        $targetFile = $targetDir . uniqid() . "-" . $nomeImagem; // Gera um nome único para a imagem

        if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $targetFile)) {
            $imagem = $targetFile;
        } else {
            $mensagemErro = "Erro ao fazer upload da imagem. Tente novamente.";
        }
    }

    if (empty($mensagemErro)) { // Procede apenas se não houve erro no upload
        if ($idProdutoEdicao) { // Se estamos editando um produto existente
            // Passa 'gramas' para a função editarProduto
            $success = editarProduto($idProdutoEdicao, $nome, $descricao, $preco, $gramas, $ativo, $imagem); 
        } else { // Se estamos adicionando um novo produto
            // Passa 'gramas' para a função cadastrarProduto
            $success = cadastrarProduto($nome, $descricao, $preco, $gramas, $ativo, $imagem); 
        }

        if ($success) {
            // Redireciona para o painel do gerente (ou mostrarcardapio.php se preferir)
            header("Location: ../Gerente/index_gerente.php"); 
            exit;
        } else {
            $mensagemErro = "Erro ao salvar o produto no banco de dados. Verifique os logs do servidor.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= ($idProdutoEdicao ? 'Editar' : 'Adicionar') ?> Produto</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        form {
            background-color: #ffffff;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            width: 100%;
            box-sizing: border-box;
        }

        h1 {
            color: #1e293b;
            margin-bottom: 24px;
            text-align: center;
            font-weight: 600;
            font-size: 24px;
        }

        label {
            display: block;
            margin-top: 16px;
            color: #4b5563;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        input[type="file"]:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        textarea {
            resize: vertical;
            height: 100px;
        }

        input[type="checkbox"] {
            margin-top: 16px;
            margin-right: 8px;
            vertical-align: middle;
        }

        label[for="ativo"] {
            display: inline-block;
            margin-top: 16px;
            font-weight: 500;
            color: #4b5563;
            font-size: 14px;
        }

        button[type="submit"] {
            margin-top: 24px;
            padding: 12px 24px;
            background-color: #3b82f6;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.1s ease;
            width: 100%;
            box-sizing: border-box;
            display: block;
            text-align: center;
        }

        button[type="submit"]:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
        }

        button[type="submit"]:active {
            background-color: #1e40af;
            transform: translateY(0);
        }

        .error-message {
            color: #dc2626;
            font-size: 14px;
            margin-top: 8px;
            border: 1px solid #fecaca;
            background-color: #ffe5e5;
            padding: 10px;
            border-radius: 6px;
        }

        @media (max-width: 640px) {
            form {
                padding: 16px;
            }

            h1 {
                font-size: 20px;
            }

            label {
                font-size: 12px;
            }

            input[type="text"],
            input[type="number"],
            textarea,
            input[type="file"] {
                font-size: 14px;
                padding: 8px;
            }

            button[type="submit"] {
                font-size: 16px;
                padding: 10px 20px;
            }
        }
    </style>
</head>
<body>

<form method="POST" enctype="multipart/form-data">
    <h1><?= ($idProdutoEdicao ? 'Editar' : 'Adicionar') ?> Produto</h1>
    <?php if ($mensagemErro): ?>
        <div class="error-message"><?= htmlspecialchars($mensagemErro) ?></div>
    <?php endif; ?>

    <?php if ($idProdutoEdicao): ?>
        <input type="hidden" name="produto_id_edicao" value="<?= htmlspecialchars($idProdutoEdicao) ?>">
    <?php endif; ?>

    <label for="nome">Nome do Produto:</label>
    <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($produtoParaEditar['nome'] ?? '') ?>" required>

    <label for="descricao">Descrição:</label>
    <textarea name="descricao" id="descricao" rows="4"><?= htmlspecialchars($produtoParaEditar['descricao'] ?? '') ?></textarea>

    <label for="preco">Preço:</label>
    <input type="number" name="preco" id="preco" step="0.01" value="<?= htmlspecialchars($produtoParaEditar['preco'] ?? '') ?>" required>

    <label for="gramas">Gramas (ou Unidade de Medida):</label>
    <input type="text" name="gramas" id="gramas" value="<?= htmlspecialchars($produtoParaEditar['gramas'] ?? '') ?>">

    <?php if ($produtoParaEditar && $produtoParaEditar['imagem']): ?>
        <p style="margin-top: 15px;">Imagem atual: <img src="<?= htmlspecialchars($produtoParaEditar['imagem']) ?>" alt="Imagem atual" style="max-width: 100px; max-height: 100px; vertical-align: middle;"></p>
    <?php endif; ?>
    <label for="imagem">Nova Imagem do Produto (deixe em branco para manter a atual):</label>
    <input type="file" name="imagem" id="imagem" accept="image/*">

    <label for="ativo">Ativo:</label>
    <input type="checkbox" name="ativo" id="ativo" <?= (($produtoParaEditar['ativo'] ?? 1) == 1) ? 'checked' : '' ?>>

    <button type="submit">Salvar Produto</button>
</form>

</body>
</html>