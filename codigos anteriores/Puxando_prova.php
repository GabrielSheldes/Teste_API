<?php
function getEnemQuestionsByYear($year, $limit = 10, $offset = 0) {
    $url = "https://api.enem.dev/v1/exams/{$year}/questions?limit={$limit}&offset={$offset}";
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $status,
        'data' => json_decode($response, true)
    ];
}

$year = 2020;
$response = getEnemQuestionsByYear($year, 50, 0); // ajusta conforme necessário

if ($response['status'] !== 200) {
    echo "Erro HTTP: {$response['status']}<br>";
    echo "Resposta: " . htmlspecialchars(json_encode($response['data'])) . "<br>";
    exit;
}

$body = $response['data'];

echo "<h2>Questões ENEM {$year}</h2>";
foreach ($body['questions'] as $question) {
    echo "<hr>";
    echo "<p><strong>#{$question['index']} - {$question['title']}</strong></p>";

    // Exibe o contexto (texto-base)
    if (!empty($question['context'])) {
        echo "<p><strong>Contexto:</strong><br>" . nl2br(htmlspecialchars($question['context'])) . "</p>";
    }

    // Exibe a introdução das alternativas, que é a parte principal do enunciado
    if (!empty($question['alternativesIntroduction'])) {
        echo "<p><strong>Enunciado:</strong><br>" . nl2br(htmlspecialchars($question['alternativesIntroduction'])) . "</p>";
    }

    // Exibe imagens, se houver
    if (!empty($question['files'])) {
        foreach ($question['files'] as $file) {
            echo "<img src='" . htmlspecialchars($file) . "' alt='Imagem da questão' style='max-width: 300px;'><br>";
        }
    }

    // Exibe as alternativas
    echo "<ul>";
    foreach ($question['alternatives'] as $alt) {
        $correct = $alt['isCorrect'] ? " (correta)" : "";
        echo "<li><strong>{$alt['letter']})</strong> " . htmlspecialchars($alt['text']) . "{$correct}</li>";
    }
    echo "</ul>";
}
