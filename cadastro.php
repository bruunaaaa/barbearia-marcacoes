<?php
session_start();
$conn = new mysqli("localhost", "root", "", "crud_1");

$json = file_get_contents('prefixo.json'); 
$paises = json_decode($json, true); 


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $prefixo = $_POST['prefixo'];
    $telemovel = $_POST['telemovel'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $telemovel_completo = $prefixo.''.$telemovel;


    $stmt = $conn->prepare("SELECT id FROM barbeiros WHERE email = ?");
    $stmt->bind_param("s", $email); 
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO barbeiros (nome, telemovel, email, password) 
                     VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $telemovel_completo, $email, $hash);
        $stmt->execute();
        echo "<script>
        alert('Cadastro realizado com sucesso!');
        window.location.href = 'login.php';
        </script>";
    } else {
        echo "<script>
        alert('Email já registrado!');
        </script>";
    }
    $stmt->close();
}


?>
 <! DOCTYPE html>
 <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Cadastro</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    </head>
    <body style="font-family: 'Poppins', sans-serif;" class="bg-primary-subtle vh-100">

  <div class="container-fluid d-flex justify-content-center align-items-center h-100">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 600px;">
      <h2 class="text-center mb-4 fw-bold">Cadastro</h2>
      
      <form method="POST">

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Nome:</label>
          <div class="col-sm-9">
            <input type="text" name="nome" class="form-control" required>
          </div>
        </div>


        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Telemóvel:</label>
          <div class="col-sm-9">
            <div class="input-group">
              <select name="prefixo" class="form-select" required>
                <?php foreach ($paises as $pais): ?>
                  <option value="<?php echo htmlspecialchars($pais['dial_code']); ?>" 
                    <?php if ($pais['dial_code'] === '+351') echo 'selected'; ?>>
                    <?php echo htmlspecialchars($pais['code']); ?> <?php echo htmlspecialchars($pais['dial_code']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="number" name="telemovel" class="form-control" required>
            </div>
          </div>
        </div>


        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Email:</label>
          <div class="col-sm-9">
            <input type="email" name="email" class="form-control" required>
          </div>
        </div>

    
        <div class="row mb-4">
          <label class="col-sm-3 col-form-label">Senha:</label>
          <div class="col-sm-9">
            <input type="password" name="password" class="form-control" required>
          </div>
        </div>

        <div class="row">
          <div class="offset-sm-3 col-sm-9">
            <button type="submit" class="btn btn-primary w-100">Cadastrar</button>
          </div>
        </div>
      </form>
      <p class="text-center mt-3">
        Já tem conta? 
        <a href="login.php" class="text-decoration-none fw-semibold">Faça login!</a>
      </p>
    </div>
  </div>

</body>
</html>