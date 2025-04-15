<?php
$servername = "127.0.0.1";
$database = "mydb"; 
$username = "root"; 
$password = ""; 

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected successfully";


// Processamento do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $senha = $_POST["senha"];
    $confirmarsenha = $_POST["confirmarsenha"];

    // Validação dos dados
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Email inválido.";
    } elseif ($senha !== $confirmarsenha) {
        echo "As senhas não coincidem.";
    } else {
        // Consulta para verificar se o usuário já existe
        $stmt = $conn->prepare("SELECT * FROM usuário WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "Usuário já existe.";
        } else {
            // Criptografa a senha
            $hash_senha = password_hash($senha, PASSWORD_BCRYPT);

            // Insere o usuário no banco de dados
            $stmt = $conn->prepare("INSERT INTO usuário (email, senha) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $hash_senha);

            if ($stmt->execute()) {
                echo "Cadastro realizado com sucesso!";
                header("Location: login.html"); // Redireciona automaticamente para login.html
                exit(); // Encerra o script para garantir que o redirecionamento funcione
            } else {
                echo "Erro ao cadastrar usuário.";
            }
            
            
               
            }
        }

        $stmt->close();
    }


$conn->close();
?>
