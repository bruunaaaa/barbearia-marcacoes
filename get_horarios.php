<?php
$conn = new mysqli("localhost", "root", "", "crud_1");
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);

if(isset($_POST['barbeiro'], $_POST['data'])){
    $barbeiro = $_POST['barbeiro'];
    $data = $_POST['data'];
    $id_marcacao = $_POST['id'] ?? null; // se vier do modal de edição

    // Converte a data em dia da semana em português
    $dias_semana = [
        'Sunday'=>'domingo','Monday'=>'segunda','Tuesday'=>'terca',
        'Wednesday'=>'quarta','Thursday'=>'quinta','Friday'=>'sexta','Saturday'=>'sabado'
    ];
    $dia_semana = $dias_semana[date('l', strtotime($data))];

    // Pega o horário do barbeiro para esse dia
    $stmt = $conn->prepare("
        SELECT hora_inicio, hora_fim, hora_inicio_almoço, hora_fim_almoço 
        FROM horarios_barbeiro 
        WHERE barbeiro_id=? AND dia_semana=?
    ");
    $stmt->bind_param("is", $barbeiro, $dia_semana);
    $stmt->execute();
    $res = $stmt->get_result();
    $horario = $res->fetch_assoc();
    $stmt->close();

    if(!$horario){
        echo "<option value=''>Sem horários disponíveis neste dia</option>";
        exit;
    }

    $hora_inicio = strtotime($horario['hora_inicio']);
    $hora_fim = strtotime($horario['hora_fim']);
    $almoco_inicio = $horario['hora_inicio_almoço'] ? strtotime($horario['hora_inicio_almoço']) : null;
    $almoco_fim = $horario['hora_fim_almoço'] ? strtotime($horario['hora_fim_almoço']) : null;

    // Pega as marcações já feitas neste dia
    if($id_marcacao){
        // exclui a marcação atual (para poder editar mantendo a mesma hora)
        $stmt2 = $conn->prepare("SELECT hora FROM marcacoes WHERE barbeiro_id=? AND data=? AND id<>?");
        $stmt2->bind_param("isi", $barbeiro, $data, $id_marcacao);
    } else {
        $stmt2 = $conn->prepare("SELECT hora FROM marcacoes WHERE barbeiro_id=? AND data=?");
        $stmt2->bind_param("is", $barbeiro, $data);
    }
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $ocupados = [];
    while($r = $res2->fetch_assoc()) {
        $ocupados[] = substr($r['hora'], 0, 5); // pega só HH:MM
    }
    $stmt2->close();

    // Cria opções de horários de 30 em 30 minutos
    $opcoes = '';
    $agora = strtotime(date('H:i'));
    $min_data = strtotime(date('Y-m-d')) == strtotime($data) ? $agora : 0;

$selectedHora = isset($_POST['selected']) ? $_POST['selected'] : '';

for($t = $hora_inicio; $t < $hora_fim; $t += 30*60){
    if(($almoco_inicio && $t >= $almoco_inicio && $t < $almoco_fim)) continue; // pula almoço
    $time_str = date('H:i', $t);
    if(in_array($time_str, $ocupados)) continue; // pula horários ocupados
    if($t < $min_data) continue; // não permite marcar no passado hoje

    $sel = $time_str == $selectedHora ? 'selected' : '';
    $opcoes .= "<option value='$time_str' $sel>$time_str</option>";
}


    echo $opcoes;
}
?>
