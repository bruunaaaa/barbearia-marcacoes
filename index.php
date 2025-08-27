<?php
$conn = new mysqli("localhost", "root", "", "crud_1");
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);

$alert = '';
$marcacoes = [];

// Buscar serviços e barbeiros
$servicos = $conn->query("SELECT * FROM servicos");
$servicos_array = [];
while($s = $servicos->fetch_assoc()) $servicos_array[] = $s;

$barbeiros = $conn->query("SELECT id, nome FROM barbeiros");
$barbeiros_array = [];
while($b = $barbeiros->fetch_assoc()) $barbeiros_array[] = $b;

// Prefixos telefónicos
$json = file_get_contents('prefixo.json');
$paises = json_decode($json, true);

// ================== NOVA MARCAÇÃO ==================
if(isset($_POST['marcar'])){
    $nome = $_POST['nome'];
    $servico = $_POST['servicos'];
    $barbeiro = $_POST['barbeiro'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];
    $prefixo = $_POST['prefixo'];
    $telemovel = $_POST['telemovel'];
    $telemovel_completo = $prefixo . $telemovel;

    $hoje = date('Y-m-d');
    $hora_atual = date('H:i');

    if($data < $hoje || ($data == $hoje && $hora <= $hora_atual)){
        $alert = '<div class="alert alert-danger mt-3 text-center">❌ Não é possível marcar para uma data ou hora passada.</div>';
    } else {
        $result = $conn->query("SELECT id FROM clientes WHERE telemovel='$telemovel_completo'");
        if($result->num_rows > 0){
            $cliente_id = $result->fetch_assoc()["id"];
        } else {
            $stmt = $conn->prepare("INSERT INTO clientes (nome, telemovel) VALUES (?, ?)");
            $stmt->bind_param("ss", $nome, $telemovel_completo);
            $stmt->execute();
            $cliente_id = $stmt->insert_id;
            $stmt->close();
        }

        $stmt = $conn->prepare("INSERT INTO marcacoes (cliente_id, servico_id, barbeiro_id, data, hora, status) VALUES (?, ?, ?, ?, ?, 'marcada')");
        $stmt->bind_param("iiiss", $cliente_id, $servico, $barbeiro, $data, $hora);
        $stmt->execute();
        $stmt->close();

        $alert = '<div class="alert alert-success mt-3 text-center">✅ Marcação efetuada com sucesso!</div>';
    }
}

// ================== BUSCAR MARCAÇÃO PARA EDITAR ==================
if(isset($_POST['telemovel_busca'])){
    $telemovel_busca = $_POST['telemovel_busca'];
    $stmt = $conn->prepare("
        SELECT m.id, c.nome, c.telemovel, s.nome_servico, m.data, m.hora, m.servico_id, m.barbeiro_id
        FROM marcacoes m
        JOIN clientes c ON m.cliente_id = c.id
        JOIN servicos s ON m.servico_id = s.id
        WHERE c.telemovel = ?
        ORDER BY m.data, m.hora
    ");
    $stmt->bind_param("s", $telemovel_busca);
    $stmt->execute();
    $result = $stmt->get_result();
    $marcacoes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ================== ATUALIZAR MARCAÇÃO ==================
if(isset($_POST['edit_id'])){
    $id = intval($_POST['edit_id']);
    $nova_data = $_POST['nova_data'];
    $nova_hora = $_POST['nova_hora'];
    $novo_servico = $_POST['novo_servico'];
    $novo_barbeiro = $_POST['novo_barbeiro'];

    $hoje = date('Y-m-d');
    $hora_atual = date('H:i');

    if($nova_data < $hoje || ($nova_data == $hoje && $nova_hora <= $hora_atual)){
        $alert = '<div class="alert alert-danger mt-3 text-center">❌ Não é possível marcar para uma data ou hora passada.</div>';
    } else {
        $stmt = $conn->prepare("UPDATE marcacoes SET data=?, hora=?, servico_id=?, barbeiro_id=? WHERE id=?");
        $stmt->bind_param("ssiii", $nova_data, $nova_hora, $novo_servico, $novo_barbeiro, $id);
        if($stmt->execute()){
            $alert = '<div class="alert alert-success mt-3 text-center">✅ Marcação atualizada com sucesso!</div>';
        } else {
            $alert = '<div class="alert alert-danger mt-3 text-center">❌ Erro ao atualizar marcação.</div>';
        }
        $stmt->close();
    }
}

// ================== ELIMINAR MARCAÇÃO ==================
if(isset($_POST['delete_id'])){
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM marcacoes WHERE id=?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()){
        $alert = '<div class="alert alert-success mt-3 text-center">✅ Marcação eliminada com sucesso!</div>';
    } else {
        $alert = '<div class="alert alert-danger mt-3 text-center">❌ Erro ao eliminar marcação.</div>';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Marcação - Barbearia</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">

<?= $alert ?>

<!-- ================== FORMULÁRIO NOVA MARCAÇÃO ================== -->
    <div class="card shadow p-4 rounded-3 mb-4">
        <h2 class="text-center mb-4">Nova Marcação</h2>
        <form method="POST" class="row g-3">
            <input type="hidden" name="marcar" value="1">
            <div class="col-md-6">
                <label>Nome:</label>
                <input type="text" name="nome" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label>Prefixo:</label>
                <select name="prefixo" class="form-select" required>
                    <?php foreach($paises as $pais): ?>
                        <option value="<?= $pais['dial_code'] ?>" <?= $pais['dial_code']==='+351'?'selected':'' ?>>
                            <?= $pais['code'] ?> <?= $pais['dial_code'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8">
                <label>Telemóvel:</label>
                <input type="text" name="telemovel" class="form-control" required>
            </div>
            <div class="col-12">
                <label>Serviço:</label>
                <select name="servicos" class="form-select" required>
                    <option value="">Escolha um serviço</option>
                    <?php foreach($servicos_array as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['nome_servico'] ?> - €<?= $s['preco'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label>Barbeiro:</label>
                <select name="barbeiro" class="form-select" required>
                    <option value="">Escolha um barbeiro</option>
                    <?php while($b = $barbeiros->fetch_assoc()): ?>
                        <option value="<?= $b['id'] ?>"><?= $b['nome'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label>Dia:</label>
                <input type="date" name="data" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-6">
                <label>Hora:</label>
                <select name="hora" id="hora" class="form-select" required>
                  <option value="">Escolha a hora</option>
                </select>            
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary px-5">Marcar</button>
            </div>
        </form>
    </div>


<!-- Editar Marcações -->
<div class="text-center mb-4">
<button class="btn btn-warning" data-bs-toggle="collapse" data-bs-target="#editarMarcacaoForm">Editar/Eliminar minha marcação</button>
</div>

<div class="collapse mb-4" id="editarMarcacaoForm">
<form method="POST" class="row g-3">
<div class="col-md-8">
<input type="text" name="telemovel_busca" class="form-control" placeholder="Digite seu telemóvel completo (+351xxxxxxx)" required>
</div>
<div class="col-md-4">
<button type="submit" class="btn btn-primary">Buscar</button>
</div>
</form>
</div>

<?php if(count($marcacoes) > 0): ?>
<h4 class="text-center mb-3">Suas marcações</h4>
<table class="table table-bordered text-center">
<thead class="table-light">
<tr>
<th>Nome</th>
<th>Telemóvel</th>
<th>Serviço</th>
<th>Barbeiro</th>
<th>Data</th>
<th>Hora</th>
<th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach($marcacoes as $m): ?>
<tr>
<td><?= htmlspecialchars($m['nome']) ?></td>
<td><?= htmlspecialchars($m['telemovel']) ?></td>
<td><?= htmlspecialchars($m['nome_servico']) ?></td>
<td>
<?php foreach($barbeiros_array as $b){ if($b['id']==$m['barbeiro_id']) echo htmlspecialchars($b['nome']); } ?>
</td>
<td><?= $m['data'] ?></td>
<td><?= $m['hora'] ?></td>
<td>
<button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarModal"
data-id="<?= $m['id'] ?>"
data-data="<?= $m['data'] ?>"
data-hora="<?= $m['hora'] ?>"
data-servico="<?= $m['servico_id'] ?>"
data-barbeiro="<?= $m['barbeiro_id'] ?>">
Editar
</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php elseif(isset($_POST['telemovel_busca'])): ?>
<div class="alert alert-danger text-center">❌ Nenhuma marcação encontrada para este telemóvel.</div>
<?php endif; ?>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editarModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST">
<div class="modal-header">
<h5 class="modal-title">Editar Marcação</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="hidden" name="edit_id" id="edit_id">
<div class="mb-3">
<label>Barbeiro:</label>
<select name="novo_barbeiro" id="novo_barbeiro" class="form-select" required>
<?php foreach($barbeiros_array as $b): ?>
<option value="<?= $b['id'] ?>"><?= $b['nome'] ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="mb-3">
<label>Data:</label>
<input type="date" name="nova_data" id="nova_data" class="form-control" required>
</div>
<div class="mb-3">
<label>Hora:</label>
<select name="nova_hora" id="nova_hora" class="form-select" required>
<option value="">Escolha a hora</option>
</select>
</div>
<div class="mb-3">
<label>Serviço:</label>
<select name="novo_servico" id="novo_servico" class="form-select" required>
<?php foreach($servicos_array as $s): ?>
<option value="<?= $s['id'] ?>"><?= $s['nome_servico'] ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
<div class="modal-footer">
<button type="submit" class="btn btn-primary">Salvar alterações</button>
<button type="submit" name="delete_id" value="" id="delete_button" class="btn btn-danger">Eliminar</button>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
</div>
</form>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>

// ================= MODAL DE EDIÇÃO =================

// Função para carregar horários do barbeiro
function carregarHoras(selectedHora = null){
    var barbeiro = document.getElementById('novo_barbeiro').value;
    var data = document.getElementById('nova_data').value;

    if(barbeiro && data){
        var body = 'barbeiro=' + encodeURIComponent(barbeiro) + 
                   '&data=' + encodeURIComponent(data);
        if(selectedHora) body += '&selected=' + encodeURIComponent(selectedHora);

        fetch('get_horarios.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: body
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('nova_hora').innerHTML = html;
        });
    }
}

// Quando abrir o modal de edição
var editarModal = document.getElementById('editarModal');
editarModal.addEventListener('show.bs.modal', function(event){
    var button = event.relatedTarget;

    // Preenche campos do modal
    document.getElementById('edit_id').value = button.getAttribute('data-id');
    document.getElementById('nova_data').value = button.getAttribute('data-data');
    document.getElementById('novo_servico').value = button.getAttribute('data-servico');
    document.getElementById('novo_barbeiro').value = button.getAttribute('data-barbeiro');

    // Carrega horários já com a hora selecionada
    carregarHoras(button.getAttribute('data-hora'));

    // Atualiza botão de delete se existir
    var deleteBtn = document.getElementById('delete_button');
    if(deleteBtn){
        deleteBtn.value = button.getAttribute('data-id');
    }
});

// Atualiza horários se mudar barbeiro ou data no modal
document.getElementById('novo_barbeiro').addEventListener('change', () => carregarHoras());
document.getElementById('nova_data').addEventListener('change', () => carregarHoras());


// ================= NOVA MARCAÇÃO =================

function carregarHorasNovo(){
    var barbeiro = document.querySelector('select[name="barbeiro"]').value;
    var data = document.querySelector('input[name="data"]').value;

    if(barbeiro && data){
        var body = 'barbeiro=' + encodeURIComponent(barbeiro) + 
                   '&data=' + encodeURIComponent(data);

        fetch('get_horarios.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: body
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('hora').innerHTML = html;
        });
    }
}

document.querySelector('select[name="barbeiro"]').addEventListener('change', carregarHorasNovo);
document.querySelector('input[name="data"]').addEventListener('change', carregarHorasNovo)


</script>

</body>
</html>
