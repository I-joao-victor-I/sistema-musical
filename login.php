<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- HEAD: configurações da página. Nada daqui aparece dentro da página. -->
    <meta charset="UTF-8">
    <!-- UTF-8: libera os acentos (ç, á, é). -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- viewport: faz a página caber na tela do celular. -->
    <title>Login Sistema Musical</title>
    <!-- title: o nome que aparece na aba do navegador. -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <!-- rel="icon": o ícone da aba. Aqui é o emoji desenhado direto no código. -->
    <link rel="stylesheet" href="style.css">
    <!-- rel="stylesheet": liga o arquivo de estilo. Todas as telas usam o MESMO styles.css... -->
</head>

<!-- A classe login-page é usada no CSS para centralizar o cartão no meio da tela. -->
<body class="login-page">

    <!-- login-card: a caixa branca que guarda o login inteiro. -->
    <div class="login-card">
        <!-- login-header: título e subtítulo, centralizados pelo CSS. -->
        <div class="login-header">
            <h1>🎵 Sistema Musical</h1>
            <p>Entre com suas credenciais</p>
        </div>

        <!-- 
            FORM: o formulário de login.
            method="post": envia os dados escondidos (não aparecem no endereço).
            action="#": por enquanto não envia para lugar nenhum.
            Na Sprint 2 (back-end), o action vai mandar os dados para o PHP.
        -->
        <form method="post" action="#">
            <!-- form-group: junta um rótulo (label) e seu campo (input). -->
            <div class="form-group">
                <!-- for "usuario" é igual ao id do input: clicar no texto foca o campo. -->
                <label for="usuario">Usuário</label>
                <!--
                    type="text": campo de texto comum.
                    id="usuario": identificador único do campo.
                    name: o nome do dado que o PHP vai receber na Sprint 2.
                    required: o navegador não deixa enviar vazio.
                    autocomplete: o navegador pode sugerir o usuário salvo.
                    placeholder: a dica cinza dentro do campo.
                -->
                <input type="text" id="usuario" name="usuario" required autocomplete="username" placeholder="Seu usuário">
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <!-- type="password": esconde o que a pessoa digita. -->
                <input type="password" id="senha" name="senha" required autocomplete="current-password" placeholder="Sua senha">
            </div>

            <!-- type="submit": envia o formulário. A classe btn-primary deixa o botão azul. -->
            <button type="submit" class="btn-primary">Entrar</button>
        </form>

        <!-- login-hint: a dica com as credenciais de teste. <code> mostra em fonte de código. -->
        <div class="login-hint">
            <strong>Credenciais de teste:</strong><br>
            Usuário: <code>admin</code> &nbsp; &nbsp; Senha: <code>123</code>
            <!-- &nbsp; espaço que não quebra a linha. -->
        </div>
    </div>

</body>
</html>