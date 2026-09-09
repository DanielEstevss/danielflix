<?php

require_once('db/banco.php');

$sql = "SELECT * FROM filme ORDER BY titulo ASC";

$stmt = $pdo->query($sql);

$filmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Danielflix</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <header class="navbar">

        <div class="logo">
            DANIELFLIX
        </div>


        <div class="acoes">

            <div class="pesquisa">

                <input type="text" id="campoBusca" placeholder="Buscar filme...">

            </div>


            <button type="button" id="botaoAtualizar" class="botao-atualizar">
                ↻ ATUALIZAR BIBLIOTECA
            </button>

        </div>

    </header>


    <main>

        <section class="catalogo">

            <h1>Filmes</h1>


            <!-- ÁREA DE PROGRESSO -->
            <div id="painelSincronizacao" class="painel-sincronizacao">

                <div class="sincronizacao-conteudo">

                    <div class="sincronizacao-titulo">
                        Atualizando biblioteca...
                    </div>


                    <div id="filmeAtual" class="filme-atual">
                        Preparando sincronização...
                    </div>


                    <div class="barra-progresso">

                        <div id="barraProgresso" class="barra-progresso-preenchida"></div>

                    </div>


                    <div id="porcentagem" class="porcentagem">
                        0%
                    </div>


                    <div id="contador" class="contador">
                        0 / 0 filmes
                    </div>


                    <div class="estatisticas">

                        <div>
                            ✓
                            <strong id="quantidadeExistentes">
                                0
                            </strong>
                            já cadastrados
                        </div>


                        <div>
                            +
                            <strong id="quantidadeNovos">
                                0
                            </strong>
                            novos filmes
                        </div>


                        <div>
                            ⚠
                            <strong id="quantidadeNaoEncontrados">
                                0
                            </strong>
                            não encontrados
                        </div>

                    </div>

                </div>

            </div>


            <!-- MENSAGEM DE CONCLUSÃO -->
            <div id="mensagemConclusao" class="mensagem-conclusao">

                <div class="icone-conclusao">
                    ✓
                </div>

                <h2>
                    Biblioteca atualizada!
                </h2>

                <p id="resumoSincronizacao">
                    Sincronização concluída.
                </p>


                <button type="button" id="botaoContinuar" class="botao-continuar">
                    CONTINUAR
                </button>

            </div>


            <!-- LISTAGEM -->
            <div id="listaFilmes" class="grid-filmes">

                <?php foreach ($filmes as $filme): ?>

                    <div class="card-filme" data-titulo="<?= htmlspecialchars($filme['titulo']) ?>">

                        <a href="assistir.php?id=<?= (int)$filme['id'] ?>">

                            <div class="poster">

                                <?php if (!empty($filme['poster'])): ?>

                                    <img src="<?= htmlspecialchars($filme['poster']) ?>" alt="<?= htmlspecialchars($filme['titulo']) ?>">

                                <?php else: ?>

                                    <div class="poster-sem-imagem">
                                        Sem poster
                                    </div>

                                <?php endif; ?>

                            </div>


                            <div class="informacoes">

                                <h2>
                                    <?= htmlspecialchars($filme['titulo']) ?>
                                </h2>


                                <div class="detalhes">

                                    <?php if (!empty($filme['ano'])): ?>

                                        <span>
                                            <?= htmlspecialchars($filme['ano']) ?>
                                        </span>

                                    <?php endif; ?>


                                    <?php if (!empty($filme['nota'])): ?>

                                        <span>
                                            ⭐ <?= htmlspecialchars($filme['nota']) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <?php if (!empty($filme['genero'])): ?>

                                    <p>
                                        <?= htmlspecialchars($filme['genero']) ?>
                                    </p>

                                <?php endif; ?>

                            </div>

                        </a>

                    </div>

                <?php endforeach; ?>

            </div>


            <div id="semResultados" class="sem-resultados">
                Nenhum filme encontrado.
            </div>

        </section>

    </main>


    <script>

        // ELEMENTOS
        const campoBusca =
            document.getElementById('campoBusca');

        const filmes =
            document.querySelectorAll('.card-filme');

        const semResultados =
            document.getElementById('semResultados');

        const botaoAtualizar =
            document.getElementById('botaoAtualizar');

        const painelSincronizacao =
            document.getElementById('painelSincronizacao');

        const mensagemConclusao =
            document.getElementById('mensagemConclusao');

        const listaFilmes =
            document.getElementById('listaFilmes');

        const barraProgresso =
            document.getElementById('barraProgresso');

        const porcentagem =
            document.getElementById('porcentagem');

        const contador =
            document.getElementById('contador');

        const filmeAtual =
            document.getElementById('filmeAtual');

        const quantidadeExistentes =
            document.getElementById('quantidadeExistentes');

        const quantidadeNovos =
            document.getElementById('quantidadeNovos');

        const quantidadeNaoEncontrados =
            document.getElementById('quantidadeNaoEncontrados');

        const resumoSincronizacao =
            document.getElementById('resumoSincronizacao');

        const botaoContinuar =
            document.getElementById('botaoContinuar');



        // BUSCA DE FILMES
        campoBusca.addEventListener('input', function () {

            const busca = this.value
                .toLowerCase()
                .trim();

            let encontrados = 0;


            filmes.forEach(function (filme) {

                const titulo =
                    filme.dataset.titulo.toLowerCase();


                if (titulo.includes(busca)) {

                    filme.style.display = '';

                    encontrados++;

                } else {

                    filme.style.display = 'none';

                }

            });


            if (encontrados === 0) {

                semResultados.style.display = 'block';

            } else {

                semResultados.style.display = 'none';

            }

        });


       
        // ATUALIZAR BIBLIOTECA
        botaoAtualizar.addEventListener(
            'click',
            iniciarSincronizacao
        );


        async function iniciarSincronizacao()
        {
            // Desabilita o botão para impedir
            // duas sincronizações simultâneas
            
            botaoAtualizar.disabled = true;


            // Mostra o painel
        
            painelSincronizacao.style.display = 'block';

            mensagemConclusao.style.display = 'none';

            listaFilmes.style.display = 'none';


            
            // Reseta os valores
            
            barraProgresso.style.width = '0%';

            porcentagem.textContent = '0%';

            contador.textContent = '0 / 0 filmes';

            filmeAtual.textContent =
                'Preparando sincronização...';

            quantidadeExistentes.textContent = '0';

            quantidadeNovos.textContent = '0';

            quantidadeNaoEncontrados.textContent = '0';


            try {

                const resposta = await fetch(
                    'script/sincronizar.php'
                );


                if (!resposta.ok) {

                    throw new Error(
                        'Erro HTTP: ' + resposta.status
                    );

                }


                
                // Lê a resposta em tempo real

                const reader =
                    resposta.body.getReader();

                const decoder =
                    new TextDecoder('utf-8');

                let buffer = '';


                while (true) {

                    const {
                        value,
                        done
                    } = await reader.read();


                    if (done) {
                        break;
                    }


                    buffer +=
                        decoder.decode(
                            value,
                            {
                                stream: true
                            }
                        );


                    const linhas =
                        buffer.split('\n');


                    
                    // Guarda a última linha caso
                    // ela ainda não esteja completa
                    
                    buffer =
                        linhas.pop();


                    for (
                        const linha of linhas
                    ) {

                        if (!linha.trim()) {
                            continue;
                        }


                        try {

                            const dados =
                                JSON.parse(linha);

                            atualizarProgresso(
                                dados
                            );

                        } catch (erro) {

                            console.error(
                                'Resposta inválida:',
                                linha
                            );

                        }

                    }

                }


                
                // Processa eventual conteúdo restante
                
                if (buffer.trim()) {

                    try {

                        const dados =
                            JSON.parse(buffer);

                        atualizarProgresso(
                            dados
                        );

                    } catch (erro) {

                        console.error(
                            'Resposta inválida:',
                            buffer
                        );

                    }

                }


            } catch (erro) {

                console.error(erro);


                filmeAtual.textContent =
                    '❌ Erro ao atualizar a biblioteca.';


                botaoAtualizar.disabled = false;

            }

        }


        
        // ATUALIZA INTERFACE
        
        function atualizarProgresso(dados)
        {

            // Se for o início da sincronização
            
            if (dados.tipo === 'inicio') {

                contador.textContent =
                    '0 / ' +
                    dados.total +
                    ' filmes';

                return;
            }


            
            // Filme sendo processado
            
            if (dados.tipo === 'progresso') {

                const percentual =
                    dados.total > 0
                        ? Math.round(
                            (
                                dados.processados /
                                dados.total
                            ) * 100
                        )
                        : 0;


                barraProgresso.style.width =
                    percentual + '%';


                porcentagem.textContent =
                    percentual + '%';


                contador.textContent =
                    dados.processados +
                    ' / ' +
                    dados.total +
                    ' filmes';


                filmeAtual.textContent =
                    dados.titulo;


                quantidadeExistentes.textContent =
                    dados.existentes;


                quantidadeNovos.textContent =
                    dados.novos;


                quantidadeNaoEncontrados.textContent =
                    dados.nao_encontrados;

                return;
            }


            
            // Sincronização finalizada

            if (dados.tipo === 'fim') {

                barraProgresso.style.width =
                    '100%';

                porcentagem.textContent =
                    '100%';


                contador.textContent =
                    dados.total +
                    ' / ' +
                    dados.total +
                    ' filmes';


                quantidadeExistentes.textContent =
                    dados.existentes;


                quantidadeNovos.textContent =
                    dados.novos;


                quantidadeNaoEncontrados.textContent =
                    dados.nao_encontrados;


                painelSincronizacao.style.display =
                    'none';


                mensagemConclusao.style.display =
                    'block';


                resumoSincronizacao.textContent =
                    dados.novos +
                    ' novos filmes adicionados. ' +
                    dados.existentes +
                    ' filmes já estavam cadastrados.';


                botaoAtualizar.disabled = false;

            }

        }


        botaoContinuar.addEventListener(
            'click',
            function () {

                // Recarrega o index para mostrar
                // os novos filmes
                
                window.location.reload();

            }
        );

    </script>

</body>

</html>