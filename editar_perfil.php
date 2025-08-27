<?php
// página embreve.php
session_start();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Em Breve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            text-align: center;
        }
        .container {
            max-width: 500px;
        }
        h1 {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        p {
            font-size: 1.25rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚧 Em Breve 🚧</h1>
        <p>Esta seção ainda está em desenvolvimento.<br>
           Volte mais tarde para conferir novidades!</p>
        <a href="barbeiro_marcacoes.php" class="btn btn-primary mt-3">Voltar ao Painel</a>
    </div>
</body>
</html>
