<?php

require_once('db/banco.php');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Filme inválido.');
}


$sql = "SELECT * FROM filme WHERE id = ?";

$stmt = $pdo->prepare($sql);

$stmt->execute([$id]);

$filme = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$filme) {
    die('Filme não encontrado.');
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Assistir - <?= htmlspecialchars($filme['titulo']) ?>
    </title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <header class="navbar">

        <div class="logo">
            DANIELFLIX
        </div>

        <a href="index.php" class="voltar">
            ← Voltar
        </a>

    </header>


    <main class="pagina-player">

        <h1>
            <?= htmlspecialchars($filme['titulo']) ?>
        </h1>

        <div class="player">

            <video
                controls
                autoplay
                preload="metadata"
            >

                <source
                    src="<?= htmlspecialchars($filme['arquivo']) ?>"
                    type="video/mp4"
                >

                Seu navegador não suporta reprodução de vídeo.

            </video>

        </div>


        <div class="info-filme">

            <h2>
                <?= htmlspecialchars($filme['titulo']) ?>
            </h2>

            <div>

                <span>
                    <?= htmlspecialchars($filme['ano']) ?>
                </span>

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

    </main>

</body>

</html>