<?php
$year = isset($_GET['year']) ? (int)$_GET['year'] : 2020;
$limit = 10;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

function getEnemQuestionsByYear($year, $limit = 10, $offset = 0) {
    $url = "https://api.enem.dev/v1/exams/{$year}/questions?limit={$limit}&offset={$offset}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'data' => json_decode($response, true)];
}

$response = getEnemQuestionsByYear($year, $limit, $offset);

if ($response['status'] !== 200) {
    echo "Erro HTTP: {$response['status']}<br>";
    echo "Resposta: " . htmlspecialchars(json_encode($response['data'])) . "<br>";
    exit;
}

$body = $response['data'];
$totalQuestions = isset($body['total']) && is_numeric($body['total']) ? (int)$body['total'] : 90;
$totalPages = ceil($totalQuestions / $limit);
$currentPage = floor($offset / $limit) + 1;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Questões ENEM <?php echo $year; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" />
    <style>
        /* Mesmos estilos que você já tinha */
        body{ 
            font-family: 'Roboto', sans-serif; 
            background-color: #f4f4f4; 
            color: #333; 
            margin: 0; 
            padding: 0; 
        }
        h2{ 
            text-align: center; 
            background-color: #4CAF50; 
            color: white; 
            padding: 20px; 
            margin: 0; 
        }
        .container{ 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .question{ 
            background-color: #fff; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            padding: 20px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
        }
        .question h3{ 
            font-size: 18px;
        }
        .context, .enunciado{ 
            background-color: #f9f9f9; 
            padding: 10px; 
            border-left: 5px solid #4CAF50; 
            margin-bottom: 10px; 
        }
        .alternatives{ 
            display: flex; 
            flex-direction: column; 
            gap: 10px; 
        }
        .alternative-btn{ 
            padding: 10px 20px; 
            background-color: #f4f4f4; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 16px; 
            cursor: pointer; 
            text-align: left; 
            transition;
            background-color 0.3s, color 0.3s; 
        }
        .alternative-btn:hover{ 
            background-color: #45a049; 
            color: white;
        }
        .alternative-btn.selected{ 
            background-color: #c3e6cb; 
            border-color: #8ccf7e; 
        }
        img{ 
            max-width: 100%; 
            margin-top: 15px; 
            border-radius: 5px; 
        }
        .pagination{ 
            text-align: center; 
            margin-top: 20px; 
        }
        .pagination a{ 
            padding: 10px 20px; 
            background-color: #4CAF50; 
            color: white; 
            text-decoration: none; 
            margin: 0 5px; 
            border-radius: 5px; 
        }
        .pagination a.disabled{ 
            background-color: #ccc; 
            pointer-events: none; 
        }
        .finalizar-btn{ 
            display: block; 
            margin: 30px auto 0; 
            padding: 15px 30px; 
            font-size: 16px; 
            background-color: #4CAF50; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            transition; 
            background-color 0.3s; 
        }
        .finalizar-btn:hover{ 
            background-color: #45a049; 
        }
        .respostas-container ul{ 
            list-style: none; 
            padding: 0; 
            max-width: 600px; 
            margin: 0 auto; 
        }
        .respostas-container li{ 
            background: #fff; 
            border: 1px solid #ddd; 
            margin-bottom: 10px; 
            padding: 10px; 
            border-radius: 5px; 
            font-size: 16px; 
        }
    </style>
</head>
<body>
    <div class="container" id="container">
        <h2>Questões ENEM <?php echo $year; ?></h2>

        <?php foreach ($body['questions'] as $question): 
            $correct = '';
            if (isset($question['correctAlternativeLetter'])) {
                $correct = $question['correctAlternativeLetter'];
            } elseif (isset($question['correctAlternative'])) {
                $correct = $question['correctAlternative'];
            } elseif (isset($question['correct'])) {
                $correct = $question['correct'];
            }
        ?>
            <div class="question">
                <h3>#<?php echo $question['index']; ?> - <?php echo htmlspecialchars($question['title']); ?></h3>

                <?php if (!empty($question['context'])): ?>
                    <div class="context">
                        <strong>Contexto:</strong>
                        <p><?php echo nl2br(htmlspecialchars($question['context'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($question['alternativesIntroduction'])): ?>
                    <div class="enunciado">
                        <strong>Enunciado:</strong>
                        <p><?php echo nl2br(htmlspecialchars($question['alternativesIntroduction'])); ?></p>
                    </div>
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
                <a href="?year=<?php echo $year; ?>&offset=<?php echo max($offset - $limit, 0); ?>">&laquo; Anterior</a>
            <?php else: ?>
                <a class="disabled" href="#">Anterior</a>
            <?php endif; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?year=<?php echo $year; ?>&offset=<?php echo $offset + $limit; ?>">Próximo &raquo;</a>
            <?php else: ?>
                <a class="disabled" href="#">Próximo</a>
            <?php endif; ?>
        </div>

        <button class="finalizar-btn" onclick="finalizarProva()">Finalizar Prova</button>
    </div>

    <script>
        function selectAlternative(button) {
            const questionDiv = button.closest('.question');
            const questionIndex = questionDiv.querySelector('h3').textContent.match(/#(\d+)/)[1];
            const questionId = "q" + questionIndex;

            // Remove seleção anterior
            const buttons = questionDiv.querySelectorAll('.alternative-btn');
            buttons.forEach(btn => btn.classList.remove('selected'));

            button.classList.add('selected');

            const selectedLetter = button.dataset.letter;
            const correctLetter = questionDiv.querySelector('.alternatives').dataset.correct;

            let respostas = JSON.parse(localStorage.getItem('respostas')) || {};
            respostas[questionId] = {
                selecionada: selectedLetter,
                correta: correctLetter
            };
            localStorage.setItem('respostas', JSON.stringify(respostas));
        }

        function finalizarProva() {
            let respostas = JSON.parse(localStorage.getItem('respostas')) || {};
            let resultado = "<h2>Resultado da Prova</h2><div class='respostas-container'><ul>";

            const ordenadas = Object.keys(respostas).sort(
                (a, b) => parseInt(a.replace('q', '')) - parseInt(b.replace('q', ''))
            );

            if (ordenadas.length === 0) {
                resultado += "<li>Você não respondeu nenhuma questão.</li>";
            } else {
                let acertos = 0;

                ordenadas.forEach(key => {
                    const r = respostas[key];
                    const correta = r.correta;
                    const marcada = r.selecionada;
                    const status = correta === marcada ? "✅ Acertou" : "❌ Errou";

                    if (correta === marcada) acertos++;

                    resultado += `<li>
                        <strong>Questão ${key.replace('q', '')}</strong><br>
                        Sua resposta: <strong>${marcada}</strong><br>
                        Gabarito: <strong>${correta}</strong><br>
                        Resultado: ${status}
                    </li>`;
                });

                resultado += `<li><strong>Total de acertos:</strong> ${acertos} de ${ordenadas.length}</li>`;
            }

            resultado += "</ul></div>";

            document.getElementById('container').innerHTML = resultado;
            // Limpa localStorage para nova prova
            localStorage.removeItem('respostas');
        }
    </script>
</body>
</html>
