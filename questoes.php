<?php
$year = isset($_GET['year']) ? (int)$_GET['year'] : 2020;
$lang = isset($_GET['lang']) ? $_GET['lang'] : null;
$caderno = isset($_GET['caderno']) ? (int)$_GET['caderno'] : null;
$limit = 10;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

function getEnemExamByYear($year) {
    $url = "https://api.enem.dev/v1/exams/{$year}";
    return json_decode(file_get_contents($url), true);
}

function getEnemQuestionsByYear($year, $limit = 10, $offset = 0, $lang = null) {
    $url = "https://api.enem.dev/v1/exams/{$year}/questions?limit={$limit}&offset={$offset}";
    if ($lang) $url .= "&language={$lang}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status'=>$status, 'data'=>json_decode($response, true)];
}

$examData = getEnemExamByYear($year);
$hasLanguages = !empty($examData['languages']);

// Escolha do caderno
if (!$caderno) {
    header("Location: choice.php?year={$year}");
    exit;
}


// Escolha do idioma
if ($hasLanguages && !$lang) {
    echo "<!DOCTYPE html><html lang='pt-BR'>
    <head><meta charset='UTF-8'><title>Seleção de Idioma</title>
    <link rel='stylesheet' href='stylequestions.css'></head>
    <body>
    <div class='choice-page'>
    <div class='container'>
    <h2>Selecione o idioma da prova de {$year} (Caderno {$caderno}):</h2>";
    foreach ($examData['languages'] as $idioma) {
        $label = htmlspecialchars($idioma['label']);
        $value = htmlspecialchars($idioma['value']);
        echo "<a href='?year={$year}&lang={$value}&caderno={$caderno}' class='choice-btn'>{$label}</a>";
    }
    echo "<a href='?year={$year}&caderno={$caderno}' class='back-btn'>← Voltar</a>";
    echo "</div></div></body></html>";
    exit;
}

if (!$hasLanguages) $lang = null;
if ($caderno === 2) $offset += 90;

$response = getEnemQuestionsByYear($year, $limit, $offset, $lang);
if ($response['status'] !== 200) { echo "Erro HTTP: {$response['status']}"; exit; }

$body = $response['data'];
$totalPages = ceil(90 / $limit);
$currentPage = floor(($offset % 90) / $limit) + 1;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Questões ENEM <?php echo $year; ?> - Caderno <?php echo $caderno; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="stylequestions.css">
</head>
<body>
<div class="container" id="container">
    <h2>Questões ENEM <?php echo $year; ?> (Caderno <?php echo $caderno; ?>, Idioma: <?php echo $lang ? strtoupper($lang) : 'Padrão'; ?>)</h2>

    <?php foreach ($body['questions'] as $question):
        $correct = $question['correctAlternativeLetter'] ?? $question['correctAlternative'] ?? $question['correct'] ?? '';
    ?>
    <div class="question">
        <h3>#<?php echo $question['index']; ?> - <?php echo htmlspecialchars($question['title']); ?></h3>

        <?php if (!empty($question['context'])): ?>
            <div class="context"><strong>Contexto:</strong><p><?php echo nl2br(htmlspecialchars($question['context'])); ?></p></div>
        <?php endif; ?>

        <?php if (!empty($question['alternativesIntroduction'])): ?>
            <div class="enunciado"><strong>Enunciado:</strong><p><?php echo nl2br(htmlspecialchars($question['alternativesIntroduction'])); ?></p></div>
        <?php endif; ?>

        <?php if (!empty($question['files'])): ?>
            <div class="image-gallery">
                <?php foreach ($question['files'] as $file): ?>
                    <img src="<?php echo htmlspecialchars($file); ?>" alt="Imagem da questão" />
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="alternatives" data-correct="<?php echo htmlspecialchars($correct); ?>">
            <?php foreach ($question['alternatives'] as $alt): ?>
                <button class="alternative-btn" data-letter="<?php echo $alt['letter']; ?>" onclick="selectAlternative(this)">
                    <strong><?php echo $alt['letter']; ?>)</strong> <?php echo htmlspecialchars($alt['text']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="pagination">
        <?php if ($currentPage > 1): ?>
            <a href="?year=<?php echo $year; ?>&offset=<?php echo max(($offset - $limit), ($caderno === 2 ? 90 : 0)); ?>&lang=<?php echo $lang; ?>&caderno=<?php echo $caderno; ?>">&laquo; Anterior</a>
        <?php else: ?>
            <a class="disabled" href="#">Anterior</a>
        <?php endif; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="?year=<?php echo $year; ?>&offset=<?php echo ($offset + $limit); ?>&lang=<?php echo $lang; ?>&caderno=<?php echo $caderno; ?>">Próximo &raquo;</a>
        <?php else: ?>
            <a class="disabled" href="#">Próximo</a>
        <?php endif; ?>
    </div>

    <button class="finalizar-btn" onclick="finalizarProva()">Finalizar Prova</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function selectAlternative(button){
    const questionDiv = button.closest('.question');
    const questionIndex = questionDiv.querySelector('h3').textContent.match(/#(\d+)/)[1];
    const questionId = "q"+questionIndex;

    const buttons = questionDiv.querySelectorAll('.alternative-btn');
    buttons.forEach(btn=>btn.classList.remove('selected'));
    button.classList.add('selected');

    const selectedLetter = button.dataset.letter;
    const correctLetter = questionDiv.querySelector('.alternatives').dataset.correct;

    let respostas = JSON.parse(localStorage.getItem('respostas'))||{};
    respostas[questionId]={selecionada:selectedLetter,correta:correctLetter};
    localStorage.setItem('respostas', JSON.stringify(respostas));
}

function finalizarProva(){
    if(!confirm("Deseja realmente finalizar a prova?")) return;
    let respostas = JSON.parse(localStorage.getItem('respostas'))||{};
    let resultado="<h2>Resultado da Prova</h2><div class='respostas-container'><ul>";

    const ordenadas = Object.keys(respostas).sort((a,b)=>parseInt(a.replace('q',''))-parseInt(b.replace('q','')));
    if(ordenadas.length===0){ resultado+="<li>Você não respondeu nenhuma questão.</li>"; }
    else{
        let acertos=0;
        ordenadas.forEach(key=>{
            const r=respostas[key]; const correta=r.correta; const marcada=r.selecionada;
            const status = correta===marcada?"✅ Acertou":"❌ Errou";
            if(correta===marcada) acertos++;
            resultado+=`<li><strong>Questão ${key.replace('q','')}</strong><br>Sua resposta: <strong>${marcada}</strong><br>Gabarito: <strong>${correta}</strong><br>Resultado: ${status}</li>`;
        });
        let total = ordenadas.length, erros = total-acertos, pct = ((acertos/total)*100).toFixed(2);
        resultado+=`<li><strong>Total de acertos:</strong> ${acertos} de ${total} (${pct}%)</li>`;
        resultado+=`<div style="max-width:500px;margin:20px auto;"><canvas id="graficoResultado"></canvas></div>`;
        setTimeout(()=>{
            const ctx=document.getElementById('graficoResultado');
            new Chart(ctx,{type:'doughnut',data:{labels:['Acertos','Erros'],datasets:[{data:[acertos,erros],backgroundColor:['#4CAF50','#F44336']}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});
        },100);
    }
    resultado+="</ul></div>";
    document.getElementById('container').innerHTML=resultado;
    localStorage.removeItem('respostas');
}
</script>
</body>
</html>
