<?php
include 'conexao.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    $data_contratacao = $_POST['data_contratacao'] ?? '';
    $salario_base = $_POST['salario_base'] ?? '';

    if (!empty($nome) && !empty($cpf) && !empty($cargo) && !empty($data_contratacao) && !empty($salario_base)) {
        $sql = "INSERT INTO Funcionarios (nome, cpf, telefone, email, cargo, data_contratacao, salario_base) 
                VALUES ('$nome', '$cpf', '$telefone', '$email', '$cargo', '$data_contratacao', '$salario_base')";

        if ($conn->query($sql) === TRUE) {
            echo "Funcionário cadastrado com sucesso!";
        } else {
            echo "Erro ao cadastrar: " . $conn->error;
        }
    } else {
        echo "Preencha todos os campos obrigatórios.";
    }
}
?>
