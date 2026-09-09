<?php

function extrairDadosFilme($arquivo)
{
    // Remove a extensão
    $nome = pathinfo($arquivo, PATHINFO_FILENAME);

    // Procura um ano entre parênteses
    if (preg_match('/\((\d{4})\)/', $nome, $resultado)) {

        $ano = (int) $resultado[1];

        // Remove o ano do título
        $titulo = preg_replace('/\s*\(\d{4}\)/', '', $nome);

    } else {

        $ano = null;
        $titulo = $nome;
    }

    return [
        'titulo' => trim($titulo),
        'ano' => $ano
    ];
}


// Remove acentos e deixa o texto
// mais fácil de comparar
     
function normalizarTitulo($texto)
{
    $texto = strtolower($texto);

    $texto = iconv(
        'UTF-8',
        'ASCII//TRANSLIT//IGNORE',
        $texto
    );

    $texto = preg_replace(
        '/[^a-z0-9\s]/',
        ' ',
        $texto
    );

    $texto = preg_replace(
        '/\s+/',
        ' ',
        $texto
    );

    return trim($texto);
}


function buscarFilmeTMDB($titulo, $ano = null)
{
    require_once(__DIR__ . '/../config/tmdb.php');


    
    // Lista de gêneros do TMDB
     
    $generos = [
        28 => 'Ação',
        12 => 'Aventura',
        16 => 'Animação',
        35 => 'Comédia',
        80 => 'Crime',
        99 => 'Documentário',
        18 => 'Drama',
        10751 => 'Família',
        14 => 'Fantasia',
        36 => 'História',
        27 => 'Terror',
        10402 => 'Música',
        9648 => 'Mistério',
        10749 => 'Romance',
        878 => 'Ficção científica',
        10770 => 'Cinema TV',
        53 => 'Thriller',
        10752 => 'Guerra',
        37 => 'Faroeste'
    ];

    
    // Função auxiliar para fazer requisições ao TMDB
     
    function requisicaoTMDB($url, $tmdbToken)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $tmdbToken,
            'accept: application/json'
        ]);

        $resposta = curl_exec($ch);

        if ($resposta === false) {
            curl_close($ch);
            return null;
        }

        $codigoHTTP = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($codigoHTTP !== 200) {
            return null;
        }

        return json_decode($resposta, true);
    }

    
    // Primeira busca:
    // título em português + ano
    
    $parametros = [
        'query' => $titulo,
        'language' => 'pt-BR',
        'include_adult' => 'false'
    ];

    if (!empty($ano)) {
        $parametros['year'] = $ano;
    }

    $url = 'https://api.themoviedb.org/3/search/movie?' .
        http_build_query($parametros);

    $dados = requisicaoTMDB($url, $tmdbToken);

    if (empty($dados['results'])) {

        
        //  Segunda tentativa:
        //  procura somente pelo título,
        //  sem limitar pelo ano.
         
        $parametros = [
            'query' => $titulo,
            'language' => 'pt-BR',
            'include_adult' => 'false'
        ];

        $url = 'https://api.themoviedb.org/3/search/movie?' .
            http_build_query($parametros);

        $dados = requisicaoTMDB($url, $tmdbToken);
    }

    if (empty($dados['results'])) {
        return null;
    }

    
    // Normaliza o título do arquivo
     
    $tituloNormalizado = normalizarTitulo($titulo);

    $filmeEscolhido = null;
    $maiorPontuacao = -1;

    
    // Analisa todos os resultados encontrados
     
    foreach ($dados['results'] as $filme) {

        $pontuacao = 0;

        $tituloTMDB = $filme['title'] ?? '';
        $tituloOriginal = $filme['original_title'] ?? '';

        $tituloTMDBNormalizado = normalizarTitulo($tituloTMDB);
        $tituloOriginalNormalizado = normalizarTitulo($tituloOriginal);

        
        // 1. Título exatamente igual
         
        if ($tituloNormalizado === $tituloTMDBNormalizado) {
            $pontuacao += 100;
        }

        
        // 2. Título original exatamente igual
         
        if ($tituloNormalizado === $tituloOriginalNormalizado) {
            $pontuacao += 90;
        }

        
        // 3. Similaridade entre os títulos
        
        similar_text(
            $tituloNormalizado,
            $tituloTMDBNormalizado,
            $similaridadePT
        );

        similar_text(
            $tituloNormalizado,
            $tituloOriginalNormalizado,
            $similaridadeOriginal
        );

        $pontuacao += $similaridadePT;
        $pontuacao += $similaridadeOriginal * 0.5;

        
        // 4. Confere o ano
        
        if (!empty($filme['release_date'])) {

            $anoFilme = (int) date(
                'Y',
                strtotime($filme['release_date'])
            );

            if (!empty($ano) && $anoFilme === (int) $ano) {
                $pontuacao += 100;
            }
        }

        
        // 5. Dá uma pequena vantagem para filmes
        // que possuem poster
        
        if (!empty($filme['poster_path'])) {
            $pontuacao += 5;
        }

        
        // Guarda o melhor resultado
         
        if ($pontuacao > $maiorPontuacao) {

            $maiorPontuacao = $pontuacao;

            $filmeEscolhido = $filme;
        }
    }

    if (!$filmeEscolhido) {
        return null;
    }

    
    // Converte os IDs dos gêneros
    // para seus respectivos nomes
     
    $generosFilme = [];

    if (!empty($filmeEscolhido['genre_ids'])) {

        foreach ($filmeEscolhido['genre_ids'] as $generoId) {

            if (isset($generos[$generoId])) {
                $generosFilme[] = $generos[$generoId];
            }
        }
    }

    $genero = implode(', ', $generosFilme);

    
    // Retorna os dados que vamos utilizar
    // no banco de dados
     
    return [

        'titulo' => $filmeEscolhido['title'] ?? null,

        'ano' => !empty($filmeEscolhido['release_date'])
            ? date('Y', strtotime($filmeEscolhido['release_date']))
            : null,

        'genero' => $genero,

        'nota' => isset($filmeEscolhido['vote_average'])
            ? round($filmeEscolhido['vote_average'], 1)
            : null,

        'poster' => !empty($filmeEscolhido['poster_path'])
            ? 'https://image.tmdb.org/t/p/w500'
                . $filmeEscolhido['poster_path']
            : null,

        'tmdb_id' => $filmeEscolhido['id'] ?? null
    ];
}