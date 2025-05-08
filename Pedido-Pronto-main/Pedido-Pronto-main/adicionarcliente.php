<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Cliente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        input[type="tel"],
        textarea {
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
        input[type="tel"]:focus,
        textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        textarea {
            resize: vertical;
            height: 100px;
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
            input[type="tel"],
            textarea {
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

    <form method="POST" action="">
        <h1>Adicionar Cliente</h1>

        <?php
        $servername = "localhost";
        $username = "root";
        $password = "";
        $database = "PedidoProntoDB";

        $conn = new mysqli($servername, $username, $password, $database);

        if ($conn->connect_error) {
            die("Conexão falhou: " . $conn->connect_error);
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nome = $conn->real_escape_string($_POST['nome']);
            $telefone = $conn->real_escape_string($_POST['telefone']);
            $endereco = $conn->real_escape_string($_POST['endereco']);

            if (empty($nome)) {
                echo "<p class='error-message'>Nome é obrigatório.</p>";
            }

            if (!empty($nome)) {
                $sql = "INSERT INTO Clientes (nome, telefone, endereco) 
                        VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $nome, $telefone, $endereco);

                if ($stmt->execute()) {
                    header("Location: index.php");
                    exit;
                } else {
                    echo "<p class='error-message'>Erro ao salvar o cliente: " . $stmt->error . "</p>";
                }
                $stmt->close();
            }
        }
        $conn->close();
        ?>

        <label for="nome">Nome:</label>
        <input type="text" name="nome" id="nome" required>

        <label for="telefone">Telefone:</label>
        <input type="tel" name="telefone" id="telefone">

        <label for="endereco">Endereço:</label>
        <textarea name="endereco" id="endereco" rows="4"></textarea>

        <button type="submit">Salvar Cliente</button>
    </form>

</body>
</html>
