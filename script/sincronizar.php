<?php

require_once('../db/banco.php');
require_once('../functions/funcoes.php');


/*
|--------------------------------------------------------------------------
| Configuração da resposta
|--------------------------------------------------------------------------
*/

header('Content-Type: application/x-ndjson; charset=utf-8');

header('Cache-Control: no-cache');

header('X-Accel-Buffering: no');


/*
|--------------------------------------------------------------------------
| Função para enviar uma atualização
|--------------------------------------------------------------------------
*/

function enviarProgresso($dados)
{
    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
    );

    echo "\n";

    /*
    Força o PHP a enviar os dados
    imediatamente para o navegador.
    */

    if (ob_get_level() > 0) {
        ob_flush();
    }

    flush();
}


/*
|--------------------------------------------------------------------------
| Desativa buffers
|--------------------------------------------------------------------------
*/

while (ob_get_level() > 0) {
    ob_end_flush();
}


/*
|--------------------------------------------------------------------------
| Pasta dos filmes
|--------------------------------------------------------------------------
*/

$pastaFilmes = '/filmes/';


$extensoesPermitidas = [

    'mp4',

    'mkv',

    'avi',

    'mov',

    'webm'

];


/*
|--------------------------------------------------------------------------
| Verifica a pasta
|--------------------------------------------------------------------------
*/

if (!is_dir($pastaFilmes)) {

    enviarProgresso([

        'tipo' => 'erro',

        'mensagem' =>
            'A pasta de filmes não foi encontrada.'

    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Lista os arquivos
|--------------------------------------------------------------------------
*/

$arquivos = scandir($pastaFilmes);


$arquivosVideo = [];


foreach ($arquivos as $arquivo) {

    /*
    Ignora "." e ".."
    */

    if (
        $arquivo === '.' ||
        $arquivo === '..'
    ) {
        continue;
    }


    $caminhoCompleto =
        $pastaFilmes . $arquivo;


    /*
    Verifica se é arquivo.
    */

    if (!is_file($caminhoCompleto)) {
        continue;
    }


    /*
    Pega extensão.
    */

    $extensao = strtolower(
        pathinfo(
            $arquivo,
            PATHINFO_EXTENSION
        )
    );


    /*
    Verifica extensão.
    */

    if (
        !in_array(
            $extensao,
            $extensoesPermitidas
        )
    ) {
        continue;
    }


    $arquivosVideo[] = [

        'arquivo' =>
            $arquivo,

        'caminho' =>
            $caminhoCompleto

    ];
}


/*
|--------------------------------------------------------------------------
| Total
|--------------------------------------------------------------------------
*/

$total = count($arquivosVideo);


$processados = 0;

$novos = 0;

$existentes = 0;

$naoEncontrados = 0;


/*
|--------------------------------------------------------------------------
| Informa o início
|--------------------------------------------------------------------------
*/

enviarProgresso([

    'tipo' => 'inicio',

    'total' => $total

]);


/*
|--------------------------------------------------------------------------
| Processa os filmes
|--------------------------------------------------------------------------
*/

foreach ($arquivosVideo as $item) {

    $arquivo =
        $item['arquivo'];


    $caminhoCompleto =
        $item['caminho'];


    /*
    ================================================================
    Extrai título e ano
    ================================================================
    */

    $dadosFilme =
        extrairDadosFilme(
            $arquivo
        );


    $titulo =
        $dadosFilme['titulo'];


    $ano =
        $dadosFilme['ano'];


    /*
    ================================================================
    Verifica se já existe
    ================================================================
    */

    $sql =
        "SELECT id
         FROM filme
         WHERE arquivo = ?";


    $stmt =
        $pdo->prepare($sql);


    $stmt->execute([
        $caminhoCompleto
    ]);


    if ($stmt->fetch()) {

        $existentes++;

        $processados++;


        /*
        Envia progresso.
        */

        enviarProgresso([

            'tipo' =>
                'progresso',

            'titulo' =>
                $titulo,

            'processados' =>
                $processados,

            'total' =>
                $total,

            'novos' =>
                $novos,

            'existentes' =>
                $existentes,

            'nao_encontrados' =>
                $naoEncontrados,

            'status' =>
                'existente'

        ]);


        continue;
    }


    /*
    ================================================================
    Consulta TMDB
    ================================================================
    */

    $dadosTMDB =
        buscarFilmeTMDB(
            $titulo,
            $ano
        );


    /*
    ================================================================
    Encontrou no TMDB
    ================================================================
    */

    if ($dadosTMDB) {

        $tituloBanco =
            $dadosTMDB['titulo'];


        $genero =
            $dadosTMDB['genero'];


        $anoBanco =
            $dadosTMDB['ano'];


        $nota =
            $dadosTMDB['nota'];


        $poster =
            $dadosTMDB['poster'];


        $tmdb_id =
            $dadosTMDB['tmdb_id'];

    } else {

        /*
        ============================================================
        Não encontrou no TMDB
        ============================================================
        */

        $tituloBanco =
            $titulo;


        $anoBanco =
            $ano;


        $tmdb_id =
            null;


        $genero =
            null;


        $nota =
            null;


        $poster =
            null;


        $naoEncontrados++;
    }


    /*
    ================================================================
    Insere no banco
    ================================================================
    */

    $sql =
        "INSERT INTO filme
        (
            titulo,
            genero,
            ano,
            nota,
            poster,
            arquivo,
            tmdb_id
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)";


    $stmt =
        $pdo->prepare($sql);


    $stmt->execute([

        $tituloBanco,

        $genero,

        $anoBanco,

        $nota,

        $poster,

        $caminhoCompleto,

        $tmdb_id

    ]);


    $novos++;

    $processados++;


    /*
    ================================================================
    Envia progresso
    ================================================================
    */

    enviarProgresso([

        'tipo' =>
            'progresso',

        'titulo' =>
            $tituloBanco,

        'processados' =>
            $processados,

        'total' =>
            $total,

        'novos' =>
            $novos,

        'existentes' =>
            $existentes,

        'nao_encontrados' =>
            $naoEncontrados,

        'status' =>
            'novo'

    ]);

}


/*
|--------------------------------------------------------------------------
| Finalização
|--------------------------------------------------------------------------
*/

enviarProgresso([

    'tipo' =>
        'fim',

    'total' =>
        $total,

    'processados' =>
        $processados,

    'novos' =>
        $novos,

    'existentes' =>
        $existentes,

    'nao_encontrados' =>
        $naoEncontrados

]);