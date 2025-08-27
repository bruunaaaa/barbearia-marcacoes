<?php
session_start();
$conn = new mysqli("localhost", "root", "", "crud_1");
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);


$barbeiro_id = $_SESSION['barbeiro_id']; 

// --- ATUALIZAR STATUS DE MARCAÇÃO ---
if(isset($_POST['acao'], $_POST['id'])){
    $acao = $_POST['acao'];
    $marcacao_id = intval($_POST['id']);

    if($acao == 'cancelar'){
        $stmt = $conn->prepare("UPDATE marcacoes SET status='cancelada' WHERE id=? AND barbeiro_id=?");
        $stmt->bind_param("ii", $marcacao_id, $barbeiro_id);
        $stmt->execute();
        $stmt->close();
    } elseif($acao == 'concluir'){
        $stmt = $conn->prepare("UPDATE marcacoes SET status='concluida' WHERE id=? AND barbeiro_id=?");
        $stmt->bind_param("ii", $marcacao_id, $barbeiro_id);
        $stmt->execute();
        $stmt->close();
    }
}

// --- EDITAR MARCAÇÃO VIA MODAL ---
if(isset($_POST['id_edit'])){
    $marcacao_id = intval($_POST['id_edit']);
    $nova_data = $_POST['data'];
    $nova_hora = $_POST['hora'];
    $novo_servico = $_POST['servico'];

    $stmt = $conn->prepare("
        UPDATE marcacoes 
        SET data=?, hora=?, servico_id=? 
        WHERE id=? AND barbeiro_id=?
    ");
    $stmt->bind_param("ssiii", $nova_data, $nova_hora, $novo_servico, $marcacao_id, $barbeiro_id);
    $stmt->execute();
    $stmt->close();
}

// --- BUSCAR MARCAÇÕES FUTURAS ---
$hoje = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT m.id, c.nome AS cliente, s.nome_servico, m.data, m.hora, m.status, m.servico_id
    FROM marcacoes m
    JOIN clientes c ON m.cliente_id=c.id
    JOIN servicos s ON m.servico_id=s.id
    WHERE m.barbeiro_id=? AND m.data>=?
    ORDER BY m.data, m.hora
");
$stmt->bind_param("is", $barbeiro_id, $hoje);
$stmt->execute();
$result = $stmt->get_result();
$marcacoes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- BUSCAR SERVIÇOS PARA MODAL ---
$servicos = $conn->query("SELECT * FROM servicos");
$servicos_array = [];
while($s = $servicos->fetch_assoc()) $servicos_array[] = $s;
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Painel do Barbeiro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Barbearia</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="painel.php">Horário de trabalho</a></li>
        <li class="nav-item"><a class="nav-link" href="editar_perfil.php">Editar perfil</a></li>
        <li class="nav-item"><a class="nav-link" href="estatisticas.php">Estatísticas</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
<h3>Minhas Marcações</h3>
<table class="table table-striped">
  <thead>
    <tr>
      <th>Cliente</th>
      <th>Serviço</th>
      <th>Data</th>
      <th>Hora</th>
      <th>Status</th>
      <th>Ações</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($marcacoes as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m['cliente']) ?></td>
        <td><?= htmlspecialchars($m['nome_servico']) ?></td>
        <td><?= $m['data'] ?></td>
        <td><?= $m['hora'] ?></td>
        <td><?= ucfirst($m['status']) ?></td>
        <td>
          <?php if($m['status']=='marcada'): ?>
            <!-- Botão modal de edição -->
            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarMarcacaoModal" 
                data-id="<?= $m['id'] ?>" data-data="<?= $m['data'] ?>" data-hora="<?= $m['hora'] ?>" 
                data-servico="<?= $m['servico_id'] ?>">Editar</button>

            <!-- Form POST Concluir -->
            <form method="POST" style="display:inline-block;">
                <input type="hidden" name="acao" value="concluir">
                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                <button type="submit" class="btn btn-sm btn-success">Concluir</button>
            </form>

            <!-- Form POST Cancelar -->
            <form method="POST" style="display:inline-block;">
                <input type="hidden" name="acao" value="cancelar">
                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Cancelar</button>
            </form>
          <?php else: ?>
            -
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- Modal de edição -->
<div class="modal fade" id="editarMarcacaoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="formEditarMarcacao">
        <div class="modal-header">
          <h5 class="modal-title">Editar Marcação</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_edit" id="marcacao_id">

          <div class="mb-3">
            <label>Data:</label>
            <input type="date" name="data" id="marcacao_data" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Hora:</label>
            <select name="hora" id="marcacao_hora" class="form-select" required></select>
          </div>

          <div class="mb-3">
            <label>Serviço:</label>
            <select name="servico" id="marcacao_servico" class="form-select" required>
              <?php foreach($servicos_array as $s): ?>
                <option value="<?= $s['id'] ?>"><?= $s['nome_servico'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Preencher modal ao abrir
var editarModal = document.getElementById('editarMarcacaoModal');
editarModal.addEventListener('show.bs.modal', function(event){
  var button = event.relatedTarget;
  var id = button.getAttribute('data-id');
  var data = button.getAttribute('data-data');
  var hora = button.getAttribute('data-hora');
  var servico = button.getAttribute('data-servico');

  document.getElementById('marcacao_id').value = id;
  document.getElementById('marcacao_data').value = data;
  document.getElementById('marcacao_servico').value = servico;

  // Carregar horários disponíveis via fetch
  fetch('get_horarios.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:`barbeiro=<?= $barbeiro_id ?>&data=${data}`
  })
  .then(res => res.text())
  .then(opcoes => {
      document.getElementById('marcacao_hora').innerHTML = opcoes;
      document.getElementById('marcacao_hora').value = hora;
  });
});
</script>

</body>
</html>
