<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Seleção de Caderno - ENEM</title>
    <link rel="stylesheet" href="stylequestions.css">
</head>
<body>
<?php 
// Captura o ano da prova vindo de questoes.php
$year = isset($_GET['year']) ? (int)$_GET['year'] : 2020; 
?>
<div class="choice-page">
    <div class="container">
        <h2>Selecione o caderno da prova de <?= $year ?>:</h2>
        <div class="choice-buttons">
            <a href="questoes.php?caderno=1&year=<?= $year ?>" class="choice-btn">Caderno 1 - Azul</a>
            <a href="questoes.php?caderno=2&year=<?= $year ?>" class="choice-btn secondary">Caderno 2 - Amarelo</a>
        </div>
        <a href="index.php" class="back-btn">← Voltar para o início</a>
    </div>
</div>
</body>
</html>
