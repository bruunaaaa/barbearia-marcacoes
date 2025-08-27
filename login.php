<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body style="font-family: 'Poppins', sans-serif;">
  <div class="container-fluid d-flex justify-content-center align-items-center vh-100 bg-primary-subtle">
    <div class="card shadow-lg p-4" style="max-width: 500px; width: 100%;">
      <h2 class="text-center mb-4">Login</h2>
      
      <form method="POST" action="processa_login.php">
        <div class="row mb-3">
          <label for="email" class="col-sm-3 col-form-label">Email:</label>
          <div class="col-sm-9">
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
        </div>

        <div class="row mb-3">
          <label for="password" class="col-sm-3 col-form-label">Senha:</label>
          <div class="col-sm-9">
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
        </div>

        <div class="row">
          <div class="col-sm-9 offset-sm-3">
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
          </div>
        </div>
      </form>
      <p class="text-center mt-3">
        Ainda não tem conta? 
        <a href="cadastro.php" class="text-decoration-none fw-semibold">Cadastre-se aqui</a>
      </p>
    </div>
  </div>
</body>
</html>