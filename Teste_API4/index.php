<?php
// 1. Parâmetros iniciais
$year = 2020;
$limit = 10; // Número de questões por página
  
// 2. Pega o offset da URL antes de usá-lo
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// 3. Função que busca as questões da API
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

// 4. Chamada da API com offset correto
$response = getEnemQuestionsByYear($year, $limit, $offset);

// 5. Verificação da resposta
if ($response['status'] !== 200) {
    echo "Erro HTTP: {$response['status']}<br>";
    echo "Resposta: " . htmlspecialchars(json_encode($response['data'])) . "<br>";
    exit;
}

$body = $response['data'];

// 6. Cálculo de paginação
$totalQuestions = isset($body['total']) && is_numeric($body['total']) ? (int)$body['total'] : 90; // valor padrão
$totalPages = ceil($totalQuestions / $limit);
$currentPage = floor($offset / $limit) + 1;
?>

<?php
echo "<pre>";
echo "Offset atual: $offset\n";
echo "Total de questões: $totalQuestions\n";
echo "Limite por página: $limit\n";
echo "Total de páginas: $totalPages\n";
echo "Página atual: $currentPage\n";
echo "</pre>";
?>



<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questões ENEM <?php echo $year; ?></title>
    <style>
        /* ... seu CSS completo aqui (sem alterações) ... */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        h2 {
            text-align: center;
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .question {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .question h3 {
            color: #333;
            font-size: 18px;
        }

        .context, .enunciado {
            background-color: #f9f9f9;
            padding: 10px;
            border-left: 5px solid #4CAF50;
            margin-bottom: 10px;
        }

        .alternatives {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .alternative-btn {
            padding: 10px 20px;
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .alternative-btn:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }

        .alternative-btn.selected {
            background-color: #c3e6cb;
            border-color: #8ccf7e;
        }

        .alternative-btn:focus {
            outline: none;
        }

        img {
            max-width: 100%;
            border-radius: 5px;
            margin-top: 15px;
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            margin: 0 5px;
            border-radius: 5px;
        }

        .pagination a:hover {
            background-color: #45a049;
        }

        .pagination .disabled {
            background-color: #ddd;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Questões ENEM <?php echo $year; ?></h2>

        <?php foreach ($body['questions'] as $question): ?>
            <div class="question">
                <h3>#<?php echo $question['index']; ?> - <?php echo htmlspecialchars($question['title']); ?></h3>

                <!-- Contexto -->
                <?php if (!empty($question['context'])): ?>
                    <div class="context">
                        <strong>Contexto:</strong>
                        <p><?php echo nl2br(htmlspecialchars($question['context'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Enunciado -->
                <?php if (!empty($question['alternativesIntroduction'])): ?>
                    <div class="enunciado">
                        <strong>Enunciado:</strong>
                        <p><?php echo nl2br(htmlspecialchars($question['alternativesIntroduction'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Imagens -->
                <?php if (!empty($question['files'])): ?>
                    <div class="image-gallery">
                        <?php foreach ($question['files'] as $file): ?>
                            <img src="<?php echo htmlspecialchars($file); ?>" alt="Imagem da questão">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Alternativas como Botões -->
                <div class="alternatives">
                    <?php foreach ($question['alternatives'] as $alt): ?>
                        <button class="alternative-btn" data-letter="<?php echo $alt['letter']; ?>"
                                onclick="selectAlternative(this)">
                            <strong><?php echo $alt['letter']; ?>)</strong> <?php echo htmlspecialchars($alt['text']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Paginação -->
        <div class="pagination">
            <!-- Anterior -->
            <?php if ($currentPage > 1 ): ?>
                <a href="?offset=<?php echo max($offset - $limit, 0); ?>">&laquo; Anterior</a>
            <?php else: ?>
                <a class="disabled" href="#">Anterior</a>
            <?php endif; ?>

            <!-- Próximo -->
            <?php if ($currentPage < $totalPages): ?>
                <a href="?offset=<?php echo $offset + $limit; ?>">Próximo &raquo;</a>
            <?php else: ?>
                <a class="disabled" href="#">Próximo</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function selectAlternative(button) {
            const buttons = document.querySelectorAll('.alternative-btn');
            buttons.forEach(function(btn) {
                btn.classList.remove('selected');
            });
            button.classList.add('selected');
        }
    </script>
</body>
</html>
