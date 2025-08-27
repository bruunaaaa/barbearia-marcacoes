<?php
session_start();
$conn = new mysqli("localhost", "root", "", "crud_1");
$alert = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];


    $sql = "SELECT * FROM barbeiros WHERE email = '$email' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $barbeiro = $result->fetch_assoc();


        if (password_verify($password,  $barbeiro['password'])) {
            $_SESSION['barbeiro_id'] = $barbeiro['id'];
            $_SESSION['barbeiro_nome'] = $barbeiro['nome'];

            header("Location: barbeiro_marcacoes.php"); 
            exit();
        } else {
           $alert = '<div class="alert alert-danger text-center" role="alert">❌ Senha incorreta!</div>';
        }
    } else {
        $alert = '<div class="alert alert-danger text-center" role="alert">❌ Barbeiro não encontrado!</div>';
    }
}
?>