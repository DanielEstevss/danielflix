<?php

require_once('../db/banco.php');

$pastaFilmes = '/filmes/';

$extensoesPermitidas = [
    'mp4',
    'mkv',
    'avi',
    'mov',
    'webm'
];

$arquivos = scandir($pastaFilmes);

foreach ($arquivos as $arquivo) {

    // Ignora "." e ".."
    if ($arquivo == '.' || $arquivo == '..') {
        continue;
    }

    $caminhoCompleto = $pastaFilmes . $arquivo;

    // Verifica se realmente é um arquivo
    if (!is_file($caminhoCompleto)) {
        continue;
    }

    // Pega a extensão
    $extensao = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));

    // Verifica se é um formato de vídeo permitido
    if (!in_array($extensao, $extensoesPermitidas)) {
        continue;
    }

    // Remove a extensão do nome
    $nomeFilme = pathinfo($arquivo, PATHINFO_FILENAME);

    echo "Encontrado: $nomeFilme <br>";

    // Verifica se já existe no banco
    $sql = "SELECT id FROM filme WHERE arquivo = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$caminhoCompleto]);

    $filmeExiste = $stmt->fetch();

    if ($filmeExiste) {

        echo "Já está cadastrado.<br><br>";

    } else {

        // Cadastra o novo filme
        $sql = "INSERT INTO filme (titulo, arquivo)
                VALUES (?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nomeFilme,
            $caminhoCompleto
        ]);

        echo "Filme cadastrado!<br><br>";
    }
}