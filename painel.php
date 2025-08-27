<?php
session_start();
if(!isset($_SESSION['barbeiro_id'])){
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "crud_1");
$barbeiro_id = $_SESSION['barbeiro_id'];
$alert = '';



if(isset($_POST['add'])){
    $dia_semana = $_POST['dia_semana'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];
    $hora_inicio_almoco = $_POST['hora_inicio_almoco'] ?: null;
    $hora_fim_almoco = $_POST['hora_fim_almoco'] ?: null;

    $stmt = $conn->prepare("SELECT id FROM horarios_barbeiro WHERE barbeiro_id = ? AND dia_semana = ?");
    $stmt->bind_param("is", $barbeiro_id, $dia_semana);
    $stmt->execute();
    $stmt->store_result();

if($stmt->num_rows > 0){
    // Já existe um horário para esse dia
    echo "<script>alert('Erro: Você já definiu um horário para este dia!'); window.location.href='painel.php';</script>";
    exit();
}

$stmt->close();

    $stmt = $conn->prepare("INSERT INTO horarios_barbeiro 
        (barbeiro_id, dia_semana, hora_inicio, hora_fim, hora_inicio_almoço, hora_fim_almoço) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $barbeiro_id, $dia_semana, $hora_inicio, $hora_fim, $hora_inicio_almoco, $hora_fim_almoco);
    $stmt->execute();
    $stmt->close();

    $alert = '<div class="alert alert-success text-center">Horário adicionado com sucesso!</div>';
}

// --- DELETAR HORÁRIO ---
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM horarios_barbeiro WHERE id = ? AND barbeiro_id = ?");
    $stmt->bind_param("ii", $id, $barbeiro_id);
    $stmt->execute();
    $stmt->close();
    header("Location: painel.php");
    exit();
}

// --- ATUALIZAR HORÁRIO ---
if(isset($_POST['edit'])){
    $id = $_POST['id'];
    $dia_semana = $_POST['dia_semana'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];
    $hora_inicio_almoco = $_POST['hora_inicio_almoco'] ?: null;
    $hora_fim_almoco = $_POST['hora_fim_almoco'] ?: null;

    $stmt = $conn->prepare("SELECT id FROM horarios_barbeiro WHERE barbeiro_id = ? AND dia_semana = ? AND id != ?");
    $stmt->bind_param("isi", $barbeiro_id, $dia_semana, $id);
    $stmt->execute();
    $stmt->store_result();

if($stmt->num_rows > 0){
    echo "<script>alert('Erro: Já existe um horário para este dia!'); window.location.href='horarios.php';</script>";
    exit();
}
$stmt->close();

    $stmt = $conn->prepare("UPDATE horarios_barbeiro SET dia_semana=?, hora_inicio=?, hora_fim=?, hora_inicio_almoço=?, hora_fim_almoço=? WHERE id=? AND barbeiro_id=?");
    $stmt->bind_param("sssssii", $dia_semana, $hora_inicio, $hora_fim, $hora_inicio_almoco, $hora_fim_almoco, $id, $barbeiro_id);
    $stmt->execute();
    $stmt->close();
    $alert = '<div class="alert alert-success text-center">Horário atualizado com sucesso!</div>';
}

// Buscar horários existentes
$horarios = $conn->query("SELECT * FROM horarios_barbeiro WHERE barbeiro_id = $barbeiro_id ORDER BY FIELD(dia_semana,'segunda','terca','quarta','quinta','sexta','sabado','domingo')");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Horários do Barbeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body style="font-family: Poppins, sans-serif;" class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4 rounded-3 mx-auto" style="max-width:800px;">
        <div class="text-start mb-3">
        <a href="barbeiro_marcacoes.php" class="btn btn-secondary">&larr; Voltar para Minhas Marcações</a>
        </div>

        <h2 class="text-center mb-4">Definir Horários de Trabalho de <?= htmlspecialchars($_SESSION['barbeiro_nome']) ?></h2>
        

        <?php if($alert) echo $alert; ?>

        <form method="POST" class="row g-3 mb-4">
            <input type="hidden" name="add" value="1">
            <div class="col-md-3">
                <label class="form-label">Dia da Semana:</label>
                <select name="dia_semana" class="form-select" required>
                    <option value="segunda">Segunda</option>
                    <option value="terca">Terça</option>
                    <option value="quarta">Quarta</option>
                    <option value="quinta">Quinta</option>
                    <option value="sexta">Sexta</option>
                    <option value="sabado">Sábado</option>
                    <option value="domingo">Domingo</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Início:</label>
                <input type="time" name="hora_inicio" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fim:</label>
                <input type="time" name="hora_fim" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Início Almoço:</label>
                <input type="time" name="hora_inicio_almoco" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Fim Almoço:</label>
                <input type="time" name="hora_fim_almoco" class="form-control">
            </div>
            <div class="col-12 text-center">
        <button type="submit" class="btn btn-primary px-5">Adicionar</button>
      </div>
        </form>

        
        <h4 class="text-center mb-3">Horários Existentes</h4>
        <table class="table table-bordered text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th>Dia</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Almoço</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $horarios->fetch_assoc()): ?>
                    <tr>
                        <td><?= ucfirst($row['dia_semana']) ?></td>
                        <td><?= $row['hora_inicio'] ?></td>
                        <td><?= $row['hora_fim'] ?></td>
                        <td><?= $row['hora_inicio_almoço'] ? $row['hora_inicio_almoço'] . " - " . $row['hora_fim_almoço'] : "—" ?></td>
                        <td>
                            <a href="painel.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deseja realmente apagar este horário?')">Apagar</a>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Editar</button>
                        </td>
                    </tr>

                    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Editar Horário</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body row g-3">
                                        <input type="hidden" name="edit" value="1">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">

                                        <div class="col-6">
                                            <label class="form-label">Dia:</label>
                                            <select name="dia_semana" class="form-select" required>
                                                <?php
                                                $dias = ['segunda','terca','quarta','quinta','sexta','sabado','domingo'];
                                                foreach($dias as $d){
                                                    $sel = $row['dia_semana']==$d?'selected':'';
                                                    echo "<option value='$d' $sel>".ucfirst($d)."</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Início:</label>
                                            <input type="time" name="hora_inicio" class="form-control" value="<?= $row['hora_inicio'] ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Fim:</label>
                                            <input type="time" name="hora_fim" class="form-control" value="<?= $row['hora_fim'] ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Início Almoço:</label>
                                            <input type="time" name="hora_inicio_almoco" class="form-control" value="<?= $row['hora_inicio_almoço'] ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Fim Almoço:</label>
                                            <input type="time" name="hora_fim_almoco" class="form-control" value="<?= $row['hora_fim_almoço'] ?>">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-warning">Salvar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
