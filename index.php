<?php
$url = "https://api.enem.dev/v1/exams";
$provas = json_decode(file_get_contents($url)); // Retorna array de objetos
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Provas do ENEM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="styleindex.css?v=1">
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">Lista de Provas do ENEM</h1>

    <div class="row">
        <?php foreach ($provas as $prova): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            <a href="questoes.php?year=<?= $prova->year ?>" class="text-decoration-none">
                                <?= htmlspecialchars($prova->title) ?> (<?= $prova->year ?>)
                            </a>
                        </h4>

                        <h6>Disciplinas:</h6>
                        <ul>
                            <?php foreach ($prova->disciplines as $disciplina): ?>
                                <li><?= htmlspecialchars($disciplina->label) ?> (<?= $disciplina->value ?>)</li>
                            <?php endforeach; ?>
                        </ul>

                        <h6>Idiomas:</h6>
                        <ul>
                            <?php foreach ($prova->languages as $idioma): ?>
                                <li><?= htmlspecialchars($idioma->label) ?> (<?= $idioma->value ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
