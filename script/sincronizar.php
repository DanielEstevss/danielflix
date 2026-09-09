<?php

require_once('../db/banco.php');
require_once('../functions/funcoes.php');

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
    $extensao = strtolower(
        pathinfo($arquivo, PATHINFO_EXTENSION)
    );

    // Verifica se é um formato de vídeo permitido
    if (!in_array($extensao, $extensoesPermitidas)) {
        continue;
    }

    // Extrai título e ano
    $dadosFilme = extrairDadosFilme($arquivo);

    $titulo = $dadosFilme['titulo'];
    $ano = $dadosFilme['ano'];

    echo "Título: $titulo <br>";
    echo "Ano: $ano <br>";


    // Verifica se já existe no banco
    $sql = "SELECT id FROM filme WHERE arquivo = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$caminhoCompleto]);

     if ($stmt->fetch()) {
        echo "Já cadastrado.<br><br>";
        continue;
    }

    // Busca informações no TMDB
    echo "Buscando informações no TMDB...<br>";

    $dadosTMDB = buscarFilmeTMDB($titulo, $ano);

    if ($dadosTMDB) {

        echo "Filme encontrado no TMDB: "
            . $dadosTMDB['titulo']
            . "<br>";

        $tituloBanco = $dadosTMDB['titulo'];
        $genero = $dadosTMDB['genero'];
        $anoBanco = $dadosTMDB['ano'];
        $nota = $dadosTMDB['nota'];
        $poster = $dadosTMDB['poster'];
        $tmdb_id = $dadosTMDB['tmdb_id'];

    } else {

        echo "Filme não encontrado no TMDB.<br>";

        $tituloBanco = $titulo;
        $anoBanco = $ano;
        $tmdb_id = null;
        $genero = null;
        $nota = null;
        $poster = null;
    }

    // Cadastra o filme
    $sql = "INSERT INTO filme 
            (titulo, genero, ano, nota, poster, arquivo, tmdb_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $tituloBanco,
        $genero,
        $anoBanco,
        $nota,
        $poster,
        $caminhoCompleto,
        $tmdb_id
    ]);

    echo "Filme cadastrado com sucesso!<br><br>";
}

echo "Sincronização concluída!";
