<?php
// Função para buscar todas as questões do ENEM de um ano, com paginação
function getAllEnemQuestions($year) {
    $limit = 50; // Pode ajustar pra 100 se quiser
    $offset = 0;
    $allQuestions = [];

    do {
        $url = "https://api.enem.dev/v1/exams/{$year}/questions?limit={$limit}&offset={$offset}";
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200) {
            throw new Exception("Erro ao buscar questões: HTTP {$status}");
        }

        $data = json_decode($response, true);
        $questions = $data['questions'] ?? [];
        $metadata = $data['metadata'] ?? ['total' => 0];

        $allQuestions = array_merge($allQuestions, $questions);

        $offset += $limit;
        $total = $metadata['total'];

    } while ($offset < $total);

    return $allQuestions;
}

$year = 2020;

try {
    $questions = getAllEnemQuestions($year);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recebe as respostas do usuário
        $userAnswers = $_POST['answers'] ?? [];

        $correctCount = 0;
        echo "<h2>Resultado da prova ENEM {$year}</h2>";
        foreach ($questions as $question) {
            $qid = $question['index'];
            $userAnswer = $userAnswers[$qid] ?? null;
            $correctAnswer = $question['correctAlternative'];

            echo "<hr>";
            echo "<p><strong>Questão {$qid}:</strong> " . htmlspecialchars($question['title']) . "</p>";

            if ($userAnswer === $correctAnswer) {
                echo "<p style='color:green;'>Sua resposta: {$userAnswer} — Correta!</p>";
                $correctCount++;
            } else {
                $userAnswerText = $userAnswer ? htmlspecialchars($userAnswer) : "<em>Não respondida</em>";
                echo "<p style='color:red;'>Sua resposta: {$userAnswerText} — Incorreta. Resposta correta: {$correctAnswer}</p>";
            }
        }
        echo "<h3>Total de acertos: {$correctCount} / " . count($questions) . "</h3>";

    } else {
        // Exibe o formulário com todas as questões para o usuário responder
        echo "<h2>Prova ENEM {$year} - Responda as questões</h2>";
        echo "<form method='POST'>";
        foreach ($questions as $question) {
            echo "<fieldset style='margin-bottom:20px; padding:10px; border:1px solid #ccc;'>";
            echo "<legend><strong>Questão {$question['index']}</strong></legend>";

            if (!empty($question['context'])) {
                echo "<p><em>Contexto:</em><br>" . nl2br(htmlspecialchars($question['context'])) . "</p>";
            }
            if (!empty($question['alternativesIntroduction'])) {
                echo "<p><strong>Enunciado:</strong><br>" . nl2br(htmlspecialchars($question['alternativesIntroduction'])) . "</p>";
            }

                        // Exibe as imagens, se houver
            if (!empty($question['files'])) {
                echo "<div style='margin:10px 0;'>";
                foreach ($question['files'] as $file) {
                    echo "<img src='" . htmlspecialchars($file) . "' alt='Imagem da questão' style='max-width:100%; max-height:400px; display:block; margin-bottom:10px;'>";
                }
                echo "</div>";
            }


            foreach ($question['alternatives'] as $alt) {
                $id = "q{$question['index']}_{$alt['letter']}";
                echo "<div>";
                echo "<input type='radio' name='answers[{$question['index']}]' id='{$id}' value='{$alt['letter']}' required>";
                echo "<label for='{$id}'><strong>{$alt['letter']})</strong> " . htmlspecialchars($alt['text']) . "</label>";
                echo "</div>";
            }
            echo "</fieldset>";
        }
        echo "<button type='submit'>Enviar respostas</button>";
        echo "</form>";
    }

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
