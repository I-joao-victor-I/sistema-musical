<?php
/*
 * =============================================
 * ARQUIVO: index.php
 * FUNÇÃO: Menu principal do sistema
 * =============================================
 *
 * Primeira página após o login.
 * valida_sessao.php garante que apenas usuários
 * logados chegam até aqui.
 */
include('valida_sessao.php');
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Principal — Sistema Musical</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="container">

        <!-- Botão de sair alinhado à direita -->
        <div class="topbar">
            <a href="logout.php" class="btn-back">🚪 Sair</a>
        </div>

        <!-- Cabeçalho de boas-vindas -->
        <div class="page-header">
            <h1>🎵 Sistema Musical</h1>
            <!--
            $_SESSION['usuario'] contém o nome salvo no login.
            htmlspecialchars() protege contra XSS na exibição.
        -->
            <p>Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>! O que deseja fazer?</p>
        </div>

        <!-- Grade de cards do menu -->
        <div class="menu-grid">

            <div class="menu-card">
                <a href="cadastro_artista.php">
                    <div class="menu-card-icon">🎤</div>
                    <div class="menu-card-title">Gerenciar Artistas</div>
                    <div class="menu-card-desc">Cadastre e edite artistas</div>
                </a>
            </div>

            <div class="menu-card">
                <a href="cadastro_musica.php">
                    <div class="menu-card-icon">🎵</div>
                    <div class="menu-card-title">Cadastrar Músicas</div>
                    <div class="menu-card-desc">Adicione músicas ao sistema</div>
                </a>
            </div>

            <div class="menu-card">
                <a href="listagem_musicas.php">
                    <div class="menu-card-icon">🎼</div>
                    <div class="menu-card-title">Biblioteca Musical</div>
                    <div class="menu-card-desc">Visualize e filtre todas as músicas</div>
                </a>
            </div>

        </div>

    </div>
</body>

</html>