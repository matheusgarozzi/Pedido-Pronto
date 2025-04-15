<?php
$servername = "127.0.0.1";
$database = "RestauranteDB"; 
$username = "root"; 
$password = ""; 

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $senha = $_POST["senha"];
    $confirmarsenha = $_POST["confirmarsenha"];
    $nome = $_POST["nome"]; 
    $telefone = $_POST["telefone"];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Email inválido.";
    } elseif ($senha !== $confirmarsenha) {
        echo "As senhas não coincidem.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM Usuarios WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "Usuário já existe.";
        } else {
            $hash_senha = password_hash($senha, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO Usuarios (Nome, Telefone, Email, Senha) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nome, $telefone, $email, $hash_senha);

            if ($stmt->execute()) {
                echo "Cadastro realizado com sucesso!";
                header("Location: login.html");
                exit();
            } else {
                echo "Erro ao cadastrar usuário.";
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>
