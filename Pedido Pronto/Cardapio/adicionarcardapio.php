<?php
// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$database = "PedidoProntoDB";

$conn = new mysqli($servername, $username, $password, $database);

// Checar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Upload da imagem
    $imagem = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $targetDir = "uploads/"; // pasta onde as imagens serão salvas
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true); // cria a pasta se não existir
        }

        $nomeImagem = basename($_FILES["imagem"]["name"]);
        $targetFile = $targetDir . uniqid() . "-" . $nomeImagem; // nome único
        if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $targetFile)) {
            $imagem = $targetFile;
        }
    }

    $sql = "INSERT INTO Produtos (nome, descricao, preco, ativo, imagem) 
            VALUES ('$nome', '$descricao', '$preco', '$ativo', '$imagem')";

if ($conn->query($sql) === TRUE) {
    header("Location: ../Gerente/index_gerente.php"); // redireciona após salvar
    exit; // encerra o script após redirecionar
} else {
    echo "<p style='color:red;'>Erro: " . $conn->error . "</p>";
}
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Produto</title>
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
    <label for="nome">Nome do Produto:</label>
    <input type="text" name="nome" id="nome" required>

    <label for="descricao">Descrição:</label>
    <textarea name="descricao" id="descricao" rows="4"></textarea>

    <label for="preco">Preço:</label>
    <input type="number" name="preco" id="preco" step="0.01" required>

    <label for="imagem">Imagem do Produto:</label>
    <input type="file" name="imagem" id="imagem" accept="image/*">

    <label for="ativo">Ativo:</label>
    <input type="checkbox" name="ativo" id="ativo" checked>

    <button type="submit">Salvar Produto</button>
</form>

</body>
</html>