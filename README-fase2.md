# 🧠 PROJETO SISTEMA MUSICAL — FASE 2: Backend Completo (PHP + MySQL)

Nesta fase vamos transformar os arquivos `.html` estáticos da Fase 1 em páginas `.php` totalmente funcionais: login com sessão e senha criptografada, banco de dados MySQL, CRUD completo de artistas e músicas, upload/redimensionamento de imagens e uma biblioteca musical com filtros dinâmicos.

**Como este roteiro é organizado:** cada etapa acrescenta **uma capacidade nova** ao arquivo, nunca tudo de uma vez. Nas páginas de CRUD (artistas e músicas), que são as mais longas, você vai construir o arquivo em **camadas**: primeiro só listar os dados que já existem no banco, depois cadastrar novos, depois editar e excluir, e só por último o upload de imagem. A cada camada o arquivo `.php` fica maior, mas sempre funcional e testável sozinho.

> Pré-requisitos: um ambiente PHP + MySQL rodando localmente (XAMPP, WAMP, Laragon ou similar), com a extensão **GD** habilitada (necessária para redimensionar imagens) e o **phpMyAdmin** disponível para rodar o script SQL.

---

## 📂 Estrutura Final da Fase 2

```text
sistema-musical/
├── styles.css               (igual à Fase 1 — não muda)
├── bd.sql                   (script de criação do banco)
├── conexao.php              (conexão com o MySQL)
├── geraSenha.php            (utilitário para gerar hash de senha)
├── valida_sessao.php        (protege páginas de usuários não logados)
├── login.php                (autenticação)
├── logout.php               (encerra a sessão)
├── index.php                (menu principal, dinâmico)
├── cadastro_artista.php     (CRUD de artistas + upload de foto)
├── cadastro_musica.php      (CRUD de músicas + link do YouTube + capa)
├── listagem_musicas.php     (biblioteca com filtros)
└── img/                     (criada automaticamente por conexao.php)
```

### Passo 2.1 — Renomeando os arquivos da Fase 1

Dentro da pasta do projeto, renomeie cada `.html` para `.php` (o conteúdo interno será reescrito nas próximas etapas — por enquanto é só trocar a extensão):

```bash
mv login.html login.php
mv index.html index.php
mv cadastro_artista.html cadastro_artista.php
mv cadastro_musica.html cadastro_musica.php
mv listagem_musicas.html listagem_musicas.php

# Rodar este comando para fazer o acima (Obs: Usar terminal bash)
for f in *.html; do mv "$f" "${f%.html}.php"; done

# arquivos novos desta fase
touch bd.sql conexao.php geraSenha.php valida_sessao.php logout.php
```

`styles.css` continua exatamente igual — nenhuma alteração necessária, já que toda a Fase 1 foi construída pensando nisso.

---

## ETAPA 1 — Banco de Dados

Abra o phpMyAdmin (normalmente em `http://localhost/phpmyadmin`), vá em **SQL** e cole o conteúdo abaixo em `bd.sql`, depois execute:

```sql
-- Criar banco de dados com nome modificado
CREATE DATABASE sistema_musical_v2;

-- Usar o banco de dados
USE sistema_musical_v2;

-- Criar tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    senha VARCHAR(255) NOT NULL
);

-- Criar tabela de artistas (removido biografia, email e website)
CREATE TABLE artistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    genero VARCHAR(50),
    pais VARCHAR(50),
    imagem VARCHAR(255)
);

-- Criar tabela de músicas (modificada para incluir link do YouTube e remover campos)
CREATE TABLE musicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artista_id INT,
    titulo VARCHAR(100) NOT NULL,
    album VARCHAR(100),
    genero VARCHAR(50),
    youtube_link VARCHAR(255), -- Novo campo para link do YouTube
    letra TEXT,
    imagem_capa VARCHAR(255),
    FOREIGN KEY (artista_id) REFERENCES artistas(id)
);

-- Inserir usuário padrão "admin" e senha "123"
INSERT INTO usuarios (usuario, senha) VALUES ('admin', '$2y$12$zw1c2aDgt4HHnAzXc3g3dOV6Mn96Dm2UaSIketIhYe1xmtuaJG6qa');
```

> A senha `123` do usuário `admin` já está inserida como **hash bcrypt** (Caso  precise gere uma nova usando o arquivo abaixo. Nunca guarde senhas em texto puro no banco.

## Gerar senha segura (`geraSenha.php`)
```php

<?php
// Rodar no terminal php geraSenha.php
echo password_hash('123', PASSWORD_BCRYPT);

```

### ✅ Teste da Etapa 1
1. No phpMyAdmin, clique no banco `sistema_musical_v2` na coluna esquerda.
2. Confirme que existem 3 tabelas: `usuarios`, `artistas`, `musicas`.
3. Abra a tabela `usuarios` → deve haver 1 linha com `usuario = admin` e uma `senha` começando com `$2y$12$...`.

---

## ETAPA 2 — Conexão com o Banco (`conexao.php`)

Este arquivo cria a variável `$conn`, usada por todas as páginas que acessam o banco. Ele também garante que a pasta `img/` exista, para os uploads.

```php
<?php
/*
 * =============================================
 * ARQUIVO: conexao.php
 * FUNÇÃO: Conectar o PHP ao banco de dados MySQL
 * =============================================
 *
 * Este arquivo é incluído em todas as páginas que
 * precisam acessar o banco de dados. Ele cria a
 * variável $conn que usamos para fazer consultas SQL.
 */

// --- Configurações do banco de dados ---
// Altere essas variáveis conforme o seu ambiente
$servername = "localhost";       // Endereço do servidor MySQL (geralmente localhost)
$username   = "root";           // Usuário do MySQL
$password   = "Senai@118";      // Senha do MySQL
$dbname     = "sistema_musical_v2"; // Nome do banco de dados

// --- Criando a conexão ---
// mysqli = MySQL Improved: forma moderna de conectar PHP ao MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Verificando se a conexão funcionou ---
// connect_error retorna null se tudo ok, ou a mensagem de erro
if ($conn->connect_error) {
    // die() para tudo e mostra a mensagem. Útil durante o desenvolvimento.
    // Em produção, nunca exiba detalhes técnicos para o usuário!
    die("Falha na conexão: " . $conn->connect_error);
}

// --- Definindo o charset (conjunto de caracteres) ---
// utf8mb4 suporta acentos, emojis e caracteres especiais
$conn->set_charset("utf8mb4");

// --- Criando o diretório de imagens se não existir ---
// Isso garante que o upload de fotos sempre vai funcionar
if (!file_exists("img")) {
    mkdir("img", 0777, true); // 0777 = permissão total, true = cria subpastas
}
```

**Ajuste as credenciais** (`$username`, `$password`, `$dbname`) conforme o seu ambiente local antes de continuar.

### ✅ Teste da Etapa 2
Crie um arquivo temporário `teste_conexao.php` na raiz do projeto:

```php
<?php
include('conexao.php');
echo "Conectado com sucesso ao banco: " . $dbname;
```

Acesse `http://localhost/sistema-musical/teste_conexao.php` no navegador. Se aparecer a mensagem de sucesso (e a pasta `img/` for criada automaticamente na raiz do projeto), a conexão está funcionando. Depois, **apague este arquivo de teste**.

---

## ETAPA 3 — Testar Geração de Senha (`geraSenha.php`)

Utilitário de linha de comando para gerar o hash bcrypt de qualquer senha — útil se você quiser cadastrar outros usuários manualmente no banco.

```bash
php geraSenha.php
```

Deve imprimir uma string parecida com `$2y$12$........................................`. Esse é o formato correto de uma senha criptografada com bcrypt — é exatamente esse tipo de valor que já está salvo na tabela `usuarios` (Etapa 1).

---

## ETAPA 4 — Proteção de Páginas (`valida_sessao.php`)

Todo arquivo que exige login inclui este script no topo. Ele verifica se existe uma sessão ativa; se não houver, redireciona para `login.php`.

```php
<?php
/*
 * =============================================
 * ARQUIVO: valida_sessao.php
 * FUNÇÃO: Proteger páginas de usuários não logados
 * =============================================
 *
 * Este arquivo é incluído no topo de cada página
 * que requer login. Se o usuário não estiver logado,
 * ele é redirecionado para a página de login.
 *
 * Como funciona uma sessão PHP?
 * - session_start() inicia ou retoma uma sessão
 * - $_SESSION é um array que persiste entre páginas
 * - Quando o usuário faz login, salvamos dados nesse array
 * - Quando ele sai (logout), limpamos o array
 */

// Inicia (ou retoma) a sessão do usuário
session_start();

// Verifica se a variável 'usuario' existe na sessão
// isset() retorna true se a variável existe e não é null
if (!isset($_SESSION['usuario'])) {

    // Usuário não está logado → redireciona para o login
    // header('Location: ...') faz o redirecionamento HTTP
    header('Location: login.php');

    // exit() é OBRIGATÓRIO após o header de redirecionamento!
    // Sem ele, o PHP continuaria executando o resto do código.
    exit();
}
```

### ✅ Teste da Etapa 4
Ainda não há como testar sozinho (depende do login da próxima etapa) — mas já pode conferir a lógica: `session_start()` retoma a sessão, `isset($_SESSION['usuario'])` checa se o usuário logou, e `exit()` após o `header()` é obrigatório para interromper a execução do restante da página.

---

## ETAPA 5 — Login (`login.php`)

Substitua o conteúdo de `login.php` (que hoje é o HTML estático da Fase 1) pelo código completo abaixo. Repare que a estrutura visual é a mesma — só a lógica em PHP foi adicionada no topo e os valores foram conectados aos dados reais.

```php
<?php
/*
 * =============================================
 * ARQUIVO: login.php
 * FUNÇÃO: Autenticar o usuário no sistema
 * =============================================
 *
 * Fluxo desta página:
 * 1. Se já estiver logado → vai para index.php
 * 2. Se enviou o formulário (POST) → valida login
 * 3. Se login ok → salva sessão e vai para index.php
 * 4. Se login falhou → mostra mensagem de erro
 */

session_start();

// Se já está logado, redireciona direto para o menu
if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

include('conexao.php');

$mensagem      = '';
$tipo_mensagem = '';

// -----------------------------------------------
// PROCESSAMENTO DO FORMULÁRIO
// -----------------------------------------------
// $_SERVER['REQUEST_METHOD'] diz como a página foi chamada:
// 'GET'  = acesso normal  |  'POST' = formulário enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $usuario = $_POST['usuario'] ?? '';
    $senha   = $_POST['senha']   ?? '';

    if (empty($usuario) || empty($senha)) {
        $mensagem      = "Preencha todos os campos.";
        $tipo_mensagem = "error";
    } else {
        // Prepared statement: o '?' é substituído com segurança
        // evitando SQL Injection (ex: usuario = "' OR '1'='1")
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario); // "s" = tipo string
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Confere a senha digitada contra o hash bcrypt salvo no banco
            if (password_verify($senha, $user['senha'])) {
                // ✅ Login ok: salva dados na sessão
                $_SESSION['usuario']    = $usuario;
                $_SESSION['usuario_id'] = $user['id'];
                header('Location: index.php');
                exit();
            } else {
                $mensagem      = "Senha incorreta.";
                $tipo_mensagem = "error";
            }
        } else {
            $mensagem      = "Usuário não encontrado.";
            $tipo_mensagem = "error";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistema Musical</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <link rel="stylesheet" href="styles.css">
</head>

<body class="login-page">

    <div class="login-card">

        <div class="login-header">
            <h1>🎵 Sistema Musical</h1>
            <p>Entre com suas credenciais</p>
        </div>

        <!-- Mensagem de erro ou feedback de logout -->
        <?php if ($mensagem): ?>
            <div class="message <?php echo $tipo_mensagem; ?>">
                <?php echo $tipo_mensagem === 'error' ? '⚠️' : '✅'; ?>
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
            <div class="message success">✅ Você saiu do sistema.</div>
        <?php endif; ?>

        <!--
            method="post" → dados vão no corpo da requisição (não aparecem na URL)
            action=""     → envia para a própria página (login.php)
        -->
        <form method="post" action="">
            <div class="form-group">
                <label for="usuario">Usuário</label>
                <!--
                    htmlspecialchars() converte < > & em entidades HTML
                    evitando XSS (Cross-Site Scripting). Use sempre ao exibir
                    dados vindos do usuário.
                -->
                <input type="text"
                    id="usuario"
                    name="usuario"
                    value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>"
                    required
                    autocomplete="username"
                    placeholder="Seu usuário">
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password"
                    id="senha"
                    name="senha"
                    required
                    autocomplete="current-password"
                    placeholder="Sua senha">
            </div>

            <button type="submit" class="btn-primary">Entrar</button>
        </form>

        <!-- Dica visível apenas em ambiente de desenvolvimento -->
        <div class="login-hint">
            <strong>Credenciais de teste:</strong><br>
            Usuário: <code>admin</code> &nbsp;·&nbsp; Senha: <code>123</code>
        </div>

    </div>

</body>

</html>
```

### ✅ Teste da Etapa 5
1. Acesse `http://localhost/sistema-musical/login.php`.
2. Tente entrar com um usuário que não existe → deve aparecer "Usuário não encontrado.".
3. Entre com `admin` e uma senha errada → deve aparecer "Senha incorreta.".
4. Entre com `admin` / `123` → deve redirecionar para `index.php` que será finalizado na Etapa 7).
5. Tente acessar `login.php` de novo enquanto está logado → deve redirecionar sozinho para `index.php` (bloco `if (isset($_SESSION['usuario']))` no topo do arquivo).

---

## ETAPA 6 — Logout (`logout.php`)

```php
<?php
/*
 * =============================================
 * ARQUIVO: logout.php
 * FUNÇÃO: Encerrar a sessão do usuário
 * =============================================
 *
 * Quando o usuário clica em "Sair", este arquivo:
 * 1. Limpa todas as variáveis de sessão
 * 2. Destrói o cookie da sessão no navegador
 * 3. Destrói a sessão no servidor
 * 4. Redireciona para a página de login
 */

// Passo 1: Inicia a sessão para ter acesso ao $_SESSION
session_start();

// Passo 2: Esvazia o array de sessão (remove todos os dados)
$_SESSION = array();

// Passo 3: Remove o cookie da sessão do navegador do usuário
// Isso garante que o navegador também "esqueça" a sessão
if (isset($_COOKIE[session_name()])) {
    // setcookie com tempo no passado faz o navegador apagar o cookie
    setcookie(session_name(), '', time() - 42000, '/');
}

// Passo 4: Destroi a sessão no servidor
session_destroy();

// Passo 5: Redireciona para o login com parâmetro de sucesso
// O parâmetro ?logout=success pode ser usado para mostrar uma mensagem
header('Location: login.php?logout=success');
exit();
```

### ✅ Teste da Etapa 6
Depois de logado, acesse `http://localhost/sistema-musical/logout.php`. Você deve ser redirecionado para `login.php?logout=success`, com a mensagem "✅ Você saiu do sistema." aparecendo em verde. Tente voltar com o botão "Voltar" do navegador e recarregar a página do menu — deve mandar de volta para o login (sessão realmente destruída).

---

## ETAPA 7 — Menu Principal (`index.php`)

Substitua `index.php` pelo código abaixo. A única mudança real em relação à Fase 1 é a proteção com `valida_sessao.php` e o nome do usuário vindo da sessão em vez de fixo.

```php
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
```

### ✅ Teste da Etapa 7
1. Sem estar logado, tente acessar `http://localhost/sistema-musical/index.php` diretamente pela URL → deve redirecionar para `login.php` (graças ao `include('valida_sessao.php')`).
2. Faça login com `admin` / `123` → agora deve chegar ao menu, com "Olá, **admin**!" no topo (puxado de `$_SESSION['usuario']`, não mais fixo).
3. Clique nos 3 cards — por enquanto pode dar erro (retorno) até as próximas etapas serem concluídas.

---

## ETAPA 8 — CRUD de Artistas (`cadastro_artista.php`)

Esta é a página mais completa do sistema, então vamos construí-la em **4 camadas**, cada uma testável sozinha antes de avançar: primeiro só listar (R de CRUD), depois cadastrar (C), depois editar e excluir (U e D), e só por último o upload de foto.

### ETAPA 8a — Apenas listar os artistas (Read)

Comece só com a consulta ao banco e a tabela — sem formulário funcional ainda.

```php
<?php
/*
 * =============================================
 * ARQUIVO: cadastro_artista.php (versão 1 — só leitura)
 * FUNÇÃO: Listar os artistas cadastrados no banco
 * =============================================
 */
include('valida_sessao.php');
include('conexao.php');

// Busca todos os artistas, ordenados por nome
$artistas = $conn->query("SELECT * FROM artistas ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artistas — Sistema Musical</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">

    <div class="page-header-bar">
        <h2>🎤 Artistas</h2>
        <div class="header-actions">
            <a href="index.php" class="btn-back">⬅ Voltar ao Menu</a>
        </div>
    </div>

    <h3>📋 Artistas Cadastrados</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th class="col-foto">Foto</th>
                    <th>Nome</th>
                    <th>Gênero</th>
                    <th>País</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($artistas->num_rows > 0): ?>
                    <?php while ($row = $artistas->fetch_assoc()): ?>
                        <tr>
                            <td><div class="no-image">Sem<br>foto</div></td>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['genero']) ?: '—'; ?></td>
                            <td><?php echo htmlspecialchars($row['pais'])   ?: '—'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="empty-cell">Nenhum artista cadastrado ainda.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
```

#### ✅ Teste da Etapa 8a
1. Acesse `cadastro_artista.php` logado. A tabela deve estar **vazia** ("Nenhum artista cadastrado ainda."), pois ainda não existe formulário para inserir dados.
2. Para confirmar que a consulta funciona, insira manualmente uma linha na tabela `artistas` pelo phpMyAdmin e recarregue a página — o nome deve aparecer na listagem.

```sql

INSERT INTO artistas (nome, genero, pais, imagem)
VALUES (
    'Jota Quest',
    'Pop Rock',
    'Brasil',
    'jotaquest.jpg'
);

```

### ETAPA 8b — Cadastrar novos artistas (Create)

Agora acrescente o formulário e o bloco de `INSERT`, no topo do arquivo, antes da consulta de listagem.

```php
<?php
/*
 * =============================================
 * ARQUIVO: cadastro_artista.php (versão 2 — + cadastro)
 * =============================================
 */
include('valida_sessao.php');
include('conexao.php');

$mensagem = null;
$class    = null;

// -----------------------------------------------
// CREATE
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome   = $_POST['nome']   ?? '';
    $genero = $_POST['genero'] ?? '';
    $pais   = $_POST['pais']   ?? '';

    // Prepared statement: os "?" evitam SQL Injection
    $stmt = $conn->prepare("INSERT INTO artistas (nome, genero, pais) VALUES (?,?,?)");
    $stmt->bind_param("sss", $nome, $genero, $pais);
    $class    = $stmt->execute() ? "success" : "error";
    $mensagem = $class === "success" ? "Artista cadastrado com sucesso!" : "Erro: " . $stmt->error;
    $stmt->close();
}

$artistas = $conn->query("SELECT * FROM artistas ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Artista — Sistema Musical</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">

    <div class="page-header-bar">
        <h2>🎤 Novo Artista</h2>
        <div class="header-actions">
            <a href="index.php" class="btn-back">⬅ Voltar ao Menu</a>
        </div>
    </div>

    <!-- Feedback de operações -->
    <?php if (isset($mensagem)): ?>
        <div class="message <?php echo $class; ?>">
            <?php echo $class === 'success' ? '✅' : '⚠️'; ?> <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-grid">

            <div class="form-group">
                <label for="nome">Nome do Artista <span style="color:var(--red)">*</span></label>
                <input type="text" id="nome" name="nome"
                       required placeholder="Ex: Djavan, Marisa Monte…">
            </div>

            <div class="form-group">
                <label for="genero">Gênero Musical</label>
                <input type="text" id="genero" name="genero"
                       placeholder="Ex: MPB, Rock, Samba…">
            </div>

            <div class="form-group">
                <label for="pais">País</label>
                <input type="text" id="pais" name="pais"
                       placeholder="Ex: Brasil, EUA…">
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">➕ Cadastrar</button>
        </div>
    </form>

    <h3>📋 Artistas Cadastrados</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th class="col-foto">Foto</th>
                    <th>Nome</th>
                    <th>Gênero</th>
                    <th>País</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($artistas->num_rows > 0): ?>
                    <?php while ($row = $artistas->fetch_assoc()): ?>
                        <tr>
                            <td><div class="no-image">Sem<br>foto</div></td>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['genero']) ?: '—'; ?></td>
                            <td><?php echo htmlspecialchars($row['pais'])   ?: '—'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="empty-cell">Nenhum artista cadastrado ainda.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
```

#### ✅ Teste da Etapa 8b
1. Cadastre "Djavan", gênero "MPB", país "Brasil" → mensagem verde de sucesso e nova linha na tabela.
2. Tente cadastrar sem preencher "Nome" → o navegador bloqueia o envio (`required`).
3. Cadastre mais um artista ("Marisa Monte") e confirme que a listagem mostra os dois em ordem alfabética.

### ETAPA 8c — Editar e excluir artistas (Update e Delete)

Agora o formulário passa a servir tanto para cadastrar quanto para editar (um único form reaproveitado), e a tabela ganha uma coluna de ações.

```php
<?php
/*
 * =============================================
 * ARQUIVO: cadastro_artista.php (versão 3 — + editar/excluir)
 * =============================================
 */
include('valida_sessao.php');
include('conexao.php');

$mensagem = null;
$class    = null;

// -----------------------------------------------
// CREATE / UPDATE
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id     = $_POST['id']     ?? '';
    $nome   = $_POST['nome']   ?? '';
    $genero = $_POST['genero'] ?? '';
    $pais   = $_POST['pais']   ?? '';

    if ($id) {
        // UPDATE: o formulário trouxe um id oculto → estamos editando
        $stmt = $conn->prepare("UPDATE artistas SET nome=?, genero=?, pais=? WHERE id=?");
        $stmt->bind_param("sssi", $nome, $genero, $pais, $id);
        $mensagem = "Artista atualizado com sucesso!";
    } else {
        // INSERT: sem id → é um cadastro novo
        $stmt = $conn->prepare("INSERT INTO artistas (nome, genero, pais) VALUES (?,?,?)");
        $stmt->bind_param("sss", $nome, $genero, $pais);
        $mensagem = "Artista cadastrado com sucesso!";
    }
    $class = $stmt->execute() ? "success" : "error";
    if ($class === "error") $mensagem = "Erro: " . $stmt->error;
    $stmt->close();
}

// -----------------------------------------------
// DELETE
// -----------------------------------------------
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']); // intval() garante número inteiro (evita SQL Injection)

    // Regra de negócio: não excluir artista com músicas cadastradas
    $chk = $conn->prepare("SELECT COUNT(*) AS total FROM musicas WHERE artista_id = ?");
    $chk->bind_param("i", $id);
    $chk->execute();
    $total = $chk->get_result()->fetch_assoc()['total'];
    $chk->close();

    if ($total > 0) {
        $mensagem = "Não é possível excluir: artista possui músicas cadastradas.";
        $class    = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM artistas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $class    = $stmt->execute() ? "success" : "error";
        $mensagem = $class === "success" ? "Artista excluído!" : "Erro: " . $stmt->error;
        $stmt->close();
    }
}

// -----------------------------------------------
// READ (edição e listagem)
// -----------------------------------------------
$artista = null;
if (isset($_GET['edit_id'])) {
    $eid  = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM artistas WHERE id = ?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $artista = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$artistas = $conn->query("SELECT * FROM artistas ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $artista ? 'Editar Artista' : 'Novo Artista'; ?> — Sistema Musical</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">

    <div class="page-header-bar">
        <h2><?php echo $artista ? '✏️ Editar Artista' : '🎤 Novo Artista'; ?></h2>
        <div class="header-actions">
            <a href="index.php" class="btn-back">⬅ Voltar ao Menu</a>
        </div>
    </div>

    <?php if (isset($mensagem)): ?>
        <div class="message <?php echo $class; ?>">
            <?php echo $class === 'success' ? '✅' : '⚠️'; ?> <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <!-- Campo oculto: carrega o ID quando estamos editando -->
        <input type="hidden" name="id" value="<?php echo $artista['id'] ?? ''; ?>">

        <div class="form-grid">

            <div class="form-group">
                <label for="nome">Nome do Artista <span style="color:var(--red)">*</span></label>
                <input type="text" id="nome" name="nome"
                       value="<?php echo htmlspecialchars($artista['nome'] ?? ''); ?>"
                       required placeholder="Ex: Djavan, Marisa Monte…">
            </div>

            <div class="form-group">
                <label for="genero">Gênero Musical</label>
                <input type="text" id="genero" name="genero"
                       value="<?php echo htmlspecialchars($artista['genero'] ?? ''); ?>"
                       placeholder="Ex: MPB, Rock, Samba…">
            </div>

            <div class="form-group">
                <label for="pais">País</label>
                <input type="text" id="pais" name="pais"
                       value="<?php echo htmlspecialchars($artista['pais'] ?? ''); ?>"
                       placeholder="Ex: Brasil, EUA…">
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <?php echo $artista ? '💾 Atualizar' : '➕ Cadastrar'; ?>
            </button>
            <?php if ($artista): ?>
                <a href="cadastro_artista.php" class="btn-back">✖ Cancelar</a>
            <?php endif; ?>
        </div>
    </form>

    <h3>📋 Artistas Cadastrados</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th class="col-foto">Foto</th>
                    <th>Nome</th>
                    <th>Gênero</th>
                    <th>País</th>
                    <th class="col-acoes">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($artistas->num_rows > 0): ?>
                    <?php while ($row = $artistas->fetch_assoc()): ?>
                        <tr>
                            <td><div class="no-image">Sem<br>foto</div></td>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['genero']) ?: '—'; ?></td>
                            <td><?php echo htmlspecialchars($row['pais'])   ?: '—'; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?edit_id=<?php echo $row['id']; ?>" class="btn-edit">✏️ Editar</a>
                                    <a href="?delete_id=<?php echo $row['id']; ?>"
                                       onclick="return confirm('Excluir <?php echo addslashes($row['nome']); ?>?')"
                                       class="btn-delete">🗑️</a>
                                    <a href="cadastro_musica.php?artista_id=<?php echo $row['id']; ?>"
                                       class="btn-music">🎵 Músicas</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty-cell">Nenhum artista cadastrado ainda.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
```

#### ✅ Teste da Etapa 8c
| # | Ação | Resultado esperado |
|---|------|---------------------|
| 1 | Clicar em "✏️ Editar" em um artista | Formulário é preenchido com os dados atuais e o botão vira "💾 Atualizar" |
| 2 | Alterar o gênero e salvar | Mensagem "Artista atualizado com sucesso!" e a tabela reflete o novo valor |
| 3 | Cadastrar uma música para um artista (Etapa 9) e tentar excluí-lo | Mensagem de erro: "Não é possível excluir: artista possui músicas cadastradas." |
| 4 | Excluir um artista sem músicas | A linha some da tabela |

### ETAPA 8d — Upload e redimensionamento de foto

Por último, acrescentamos o campo de arquivo e a função que redimensiona a imagem antes de salvar (usa a extensão **GD**). O restante do arquivo continua igual à Etapa 8c — só os trechos abaixo são novos ou alterados.

```php
<?php
/*
 * =============================================
 * ARQUIVO: cadastro_artista.php (versão final — + upload de foto)
 * =============================================
 *
 * Operações:
 *   C → POST sem $_POST['id']    → INSERT (novo artista)
 *   R → query SELECT ao carregar → tabela no final da página
 *   U → POST com $_POST['id']    → UPDATE (editar artista)
 *   D → GET ?delete_id=X         → DELETE (excluir artista)
 */
include('valida_sessao.php');
include('conexao.php');

$mensagem = null;
$class    = null;

// -----------------------------------------------
// FUNÇÃO NOVA: Redimensionar e salvar imagem
// -----------------------------------------------
function redimensionarESalvarImagem(array $arquivo, $largura = 300, $altura = 300) {
    $dir          = "img/";
    $nome         = uniqid() . '_' . basename($arquivo["name"]); // nome único
    $caminho      = $dir . $nome;
    $tipo         = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));

    // Validações
    if (!getimagesize($arquivo["tmp_name"]))         return "Arquivo não é uma imagem válida.";
    if ($arquivo["size"] > 5000000)                  return "Imagem muito grande. Máximo: 5 MB.";
    if (!in_array($tipo, ["jpg","jpeg","png"]))       return "Apenas JPG e PNG são aceitos.";

    // Carrega, redimensiona e salva
    $original      = imagecreatefromstring(file_get_contents($arquivo["tmp_name"]));
    $w0 = imagesx($original);
    $h0 = imagesy($original);
    $ratio         = min($largura / $w0, $altura / $h0);
    $nw            = (int)($w0 * $ratio);
    $nh            = (int)($h0 * $ratio);
    $nova          = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($nova, $original, 0, 0, 0, 0, $nw, $nh, $w0, $h0);

    ($tipo === "png") ? imagepng($nova, $caminho) : imagejpeg($nova, $caminho, 90);

    imagedestroy($original);
    imagedestroy($nova);
    return $caminho;
}


// -----------------------------------------------
// CREATE / UPDATE (agora considerando a imagem)
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id     = $_POST['id']     ?? '';
    $nome   = $_POST['nome']   ?? '';
    $genero = $_POST['genero'] ?? '';
    $pais   = $_POST['pais']   ?? '';

    $imagem = "";
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $upload = redimensionarESalvarImagem($_FILES['imagem']);
        if (str_starts_with($upload, 'img/')) {
            $imagem = $upload;
        } else {
            $mensagem = $upload;
            $class    = "error";
        }
    }

    if (!isset($class)) {
        if ($id) {
            // UPDATE — só troca a imagem se uma nova foi enviada
            $sql    = "UPDATE artistas SET nome=?, genero=?, pais=?";
            $params = [$nome, $genero, $pais];
            if ($imagem) { $sql .= ", imagem=?"; $params[] = $imagem; }
            $sql .= " WHERE id=?"; $params[] = intval($id);
            $stmt = $conn->prepare($sql);
            $tipos = str_repeat('s', count($params) - 1) . 'i';
            $stmt->bind_param($tipos, ...$params);
            $mensagem = "Artista atualizado com sucesso!";
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO artistas (nome, genero, pais, imagem) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $nome, $genero, $pais, $imagem);
            $mensagem = "Artista cadastrado com sucesso!";
        }
        $class = $stmt->execute() ? "success" : "error";
        if ($class === "error") $mensagem = "Erro: " . $stmt->error;
        $stmt->close();
    }
}


// -----------------------------------------------
// DELETE (igual à Etapa 8c)
// -----------------------------------------------
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);

    $chk = $conn->prepare("SELECT COUNT(*) AS total FROM musicas WHERE artista_id = ?");
    $chk->bind_param("i", $id);
    $chk->execute();
    $total = $chk->get_result()->fetch_assoc()['total'];
    $chk->close();

    if ($total > 0) {
        $mensagem = "Não é possível excluir: artista possui músicas cadastradas.";
        $class    = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM artistas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $class    = $stmt->execute() ? "success" : "error";
        $mensagem = $class === "success" ? "Artista excluído!" : "Erro: " . $stmt->error;
        $stmt->close();
    }
}


// -----------------------------------------------
// READ (edição e listagem — igual à Etapa 8c)
// -----------------------------------------------
$artista = null;
if (isset($_GET['edit_id'])) {
    $eid  = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM artistas WHERE id = ?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $artista = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$artistas = $conn->query("SELECT * FROM artistas ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $artista ? 'Editar Artista' : 'Novo Artista'; ?> — Sistema Musical</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">

    <div class="page-header-bar">
        <h2><?php echo $artista ? '✏️ Editar Artista' : '🎤 Novo Artista'; ?></h2>
        <div class="header-actions">
            <a href="index.php" class="btn-back">⬅ Voltar ao Menu</a>
        </div>
    </div>

    <?php if (isset($mensagem)): ?>
        <div class="message <?php echo $class; ?>">
            <?php echo $class === 'success' ? '✅' : '⚠️'; ?> <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <!--
        FORMULÁRIO DE CADASTRO / EDIÇÃO
        enctype="multipart/form-data" é obrigatório para envio de arquivos
    -->
    <form method="post" action="" enctype="multipart/form-data">

        <input type="hidden" name="id" value="<?php echo $artista['id'] ?? ''; ?>">

        <div class="form-grid">

            <div class="form-group">
                <label for="nome">Nome do Artista <span style="color:var(--red)">*</span></label>
                <input type="text" id="nome" name="nome"
                       value="<?php echo htmlspecialchars($artista['nome'] ?? ''); ?>"
                       required placeholder="Ex: Djavan, Marisa Monte…">
            </div>

            <div class="form-group">
                <label for="genero">Gênero Musical</label>
                <input type="text" id="genero" name="genero"
                       value="<?php echo htmlspecialchars($artista['genero'] ?? ''); ?>"
                       placeholder="Ex: MPB, Rock, Samba…">
            </div>

            <div class="form-group">
                <label for="pais">País</label>
                <input type="text" id="pais" name="pais"
                       value="<?php echo htmlspecialchars($artista['pais'] ?? ''); ?>"
                       placeholder="Ex: Brasil, EUA…">
            </div>

            <div class="form-group">
                <label for="imagem">Foto do Artista</label>
                <input type="file" id="imagem" name="imagem" accept="image/*">
                <small>JPG ou PNG · Máximo 5 MB<?php echo $artista ? ' · Deixe vazio para manter a foto atual' : ''; ?></small>

                <?php if (!empty($artista['imagem'])): ?>
                    <div class="current-image">
                        <img src="<?php echo htmlspecialchars($artista['imagem']); ?>" alt="Foto atual">
                        <small>Foto atual</small>
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- fim .form-grid -->

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <?php echo $artista ? '💾 Atualizar' : '➕ Cadastrar'; ?>
            </button>
            <?php if ($artista): ?>
                <a href="cadastro_artista.php" class="btn-back">✖ Cancelar</a>
            <?php endif; ?>
        </div>
    </form>


    <!-- -----------------------------------------------
         TABELA DE ARTISTAS CADASTRADOS
    ----------------------------------------------- -->
    <h3>📋 Artistas Cadastrados</h3>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th class="col-foto">Foto</th>
                    <th>Nome</th>
                    <th>Gênero</th>
                    <th>País</th>
                    <th class="col-acoes">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($artistas->num_rows > 0): ?>
                    <?php while ($row = $artistas->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($row['imagem']): ?>
                                    <img src="<?php echo htmlspecialchars($row['imagem']); ?>"
                                         alt="<?php echo htmlspecialchars($row['nome']); ?>"
                                         class="table-thumbnail">
                                <?php else: ?>
                                    <div class="no-image">Sem<br>foto</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['genero']) ?: '—'; ?></td>
                            <td><?php echo htmlspecialchars($row['pais'])   ?: '—'; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?edit_id=<?php echo $row['id']; ?>" class="btn-edit">✏️ Editar</a>
                                    <a href="?delete_id=<?php echo $row['id']; ?>"
                                       onclick="return confirm('Excluir <?php echo addslashes($row['nome']); ?>?')"
                                       class="btn-delete">🗑️</a>
                                    <a href="cadastro_musica.php?artista_id=<?php echo $row['id']; ?>"
                                       class="btn-music">🎵 Músicas</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty-cell">Nenhum artista cadastrado ainda.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
```

#### ✅ Teste da Etapa 8d — CRUD completo com upload
| # | Ação | Como testar | Resultado esperado |
|---|------|--------------|---------------------|
| 1 | **Create com foto** | Cadastre um artista enviando uma imagem `.jpg` | A foto deve aparecer redimensionada (60×60) na coluna "Foto" da tabela |
| 2 | **Upload inválido** | Tente enviar um `.pdf` no campo de imagem | Mensagem de erro "Apenas JPG e PNG são aceitos." |
| 3 | **Editar sem trocar a foto** | Edite um artista com foto e deixe o campo de imagem vazio | A foto atual deve ser mantida (mensagem "Deixe vazio para manter a foto atual") |
| 4 | **Editar trocando a foto** | Envie uma nova imagem ao editar | A foto antiga é substituída pela nova na tabela |

---

## ETAPA 9 — CRUD de Músicas (`cadastro_musica.php`)

Cada música pertence a um artista (`artista_id` é chave estrangeira). Assim como o CRUD de artistas, vamos construir em camadas — mas agora um pouco mais rápido, já que a lógica de C/R/U/D é a mesma que você acabou de praticar. A novidade real aqui é o link do YouTube.

### ETAPA 9a — Listar músicas de um artista (Read) + validação do link do YouTube

```php
<?php
/*
 * =============================================
 * ARQUIVO: cadastro_musica.php (versão 1 — só leitura + função do YouTube)
 * =============================================
 */
include('valida_sessao.php');
include('conexao.php');

// -----------------------------------------------
// FUNÇÃO: Extrair ID do vídeo do YouTube
// -----------------------------------------------
// O YouTube aceita vários formatos de URL.
// Esta função extrai o ID de 11 caracteres de qualquer um.
//
// URLs suportadas:
//   https://www.youtube.com/watch?v=dQw4w9WgXcQ  → ID = dQw4w9WgXcQ
//   https://youtu.be/dQw4w9WgXcQ
//   https://www.youtube.com/embed/dQw4w9WgXcQ
function getYoutubeVideoId(string $url) {
    $patterns = [
        '/youtube\.com\/watch\?v=([^\&\?\/]+)/',
        '/youtube\.com\/embed\/([^\&\?\/]+)/',
        '/youtube\.com\/v\/([^\&\?\/]+)/',
        '/youtu\.be\/([^\&\?\/]+)/'
    ];
    foreach ($patterns as $p) {
        // preg_match() testa a expressão regular; $m[1] = primeiro grupo ()
        if (preg_match($p, $url, $m)) return $m[1];
    }
    return "";
}

// Filtra por um artista específico (via ?artista_id=X vindo de "🎵 Músicas")
$artistas          = $conn->query("SELECT id, nome FROM artistas ORDER BY nome");
$artista_id_filtro = isset($_GET['artista_id']) ? intval($_GET['artista_id']) : null;

$musicas = null;
if ($artista_id_filtro) {
    $stmt = $conn->prepare("
        SELECT m.*, a.nome AS artista_nome
        FROM musicas m
        JOIN artistas a ON m.artista_id = a.id
        WHERE m.artista_id = ?
        ORDER BY m.titulo
    ");
    $stmt->bind_param("i", $artista_id_filtro);
    $stmt->execute();
    $musicas = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Músicas — Sistema Musical</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">

    <div class="page-header-bar">
        <h2>🎵 Músicas</h2>
        <div class="header-actions">
            <a href="cadastro_artista.php" class="btn-back">⬅ Voltar para Artistas</a>
        </div>
    </div>

    <?php if ($musicas && $musicas->num_rows > 0): ?>
        <h3>🎵 Músicas Cadastradas</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Álbum</th>
                        <th class="col-youtube">YouTube</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $musicas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($row['album']) ?: '—'; ?></td>
                            <td>
                                <?php
                                $vid = getYoutubeVideoId($row['youtube_link'] ?? '');
                                if ($vid):
                                    $watch_url = "https://www.youtube.com/watch?v={$vid}";
                                ?>
                                    <a href="<?php echo $watch_url; ?>" target="_blank"
                                       rel="noopener noreferrer" class="btn-youtube">▶ Assistir</a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($artista_id_filtro): ?>
        <p class="text-muted">Nenhuma música cadastrada para este artista ainda.</p>
    <?php else: ?>
        <p class="text-muted">Acesse esta página a partir de "🎵 Músicas" na lista de artistas.</p>
    <?php endif; ?>

</div>
</body>
</html>
```

#### ✅ Teste da Etapa 9a
1. Na listagem de artistas, clique em "🎵 Músicas" de um artista → deve abrir esta página com a mensagem "Nenhuma música cadastrada para este artista ainda." (a tabela `musicas` ainda está vazia).
2. Insira manualmente uma linha em `musicas` pelo phpMyAdmin, com um `youtube_link` válido, e recarregue → o botão "▶ Assistir" deve aparecer e abrir o vídeo certo em nova aba.

```sql

INSERT INTO musicas (
    artista_id,
    titulo,
    album,
    genero,
    youtube_link,
    letra,
    imagem_capa
)
VALUES (
    1,
    'É Preciso (A Próxima Parada)',
    'De Volta ao Novo',
    'Pop Rock',
    'https://www.youtube.com/watch?v=PbdzSXrv-34',
    '',
    'e_preciso_a_proxima_parada.jpg'
);

```

### ETAPA 9b — Cadastrar músicas (Create) com validação do link

```php
<?php
/*
 * =============================================
 * ARQUIVO: cadastro_musica.php (versão 2 — + cadastro)
 * =============================================
 */
include('valida_sessao.php');
include('conexao.php');

function getYoutubeVideoId(string $url) {
    $patterns = [
        '/youtube\.com\/watch\?v=([^\&\?\/]+)/',
        '/youtube\.com\/embed\/([^\&\?\/]+)/',
        '/youtube\.com\/v\/([^\&\?\/]+)/',
        '/youtu\.be\/([^\&\?\/]+)/'
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) return $m[1];
    }
    return "";
}

// -----------------------------------------------
// CREATE
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $artista_id   = $_POST['artista_id']   ?? '';
    $titulo       = $_POST['titulo']       ?? '';
    $album        = $_POST['album']        ?? '';
    $genero       = $_POST['genero']       ?? '';
    $letra        = $_POST['letra']        ?? '';
    $youtube_link = $_POST['youtube_link'] ?? '';

    // Valida o link do YouTube antes de salvar
    if (empty(getYoutubeVideoId($youtube_link))) {
        $mensagem = "Insira um link válido do YouTube (ex: https://www.youtube.com/watch?v=...).";
        $class    = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO musicas (artista_id,titulo,album,genero,letra,youtube_link) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $artista_id, $titulo, $album, $genero, $letra, $youtube_link);
        $class    = $stmt->execute() ? "success" : "error";
        $mensagem = $class === "success" ? "Música cadastrada com sucesso!" : "Erro: " . $stmt->error;
        $stmt->close();
    }
}

$artistas          = $conn->query("SELECT id, nome FROM artistas ORDER BY nome");
$artista_id_filtro = isset($_GET['artista_id']) ? intval($_GET['artista_id']) : null;

$musicas = null;
if ($artista_id_filtro) {
    $stmt = $conn->prepare("
        SELECT m.*, a.nome AS artista_nome
        FROM musicas m
        JOIN artistas a ON m.artista_id = a.id
        WHERE m.artista_id = ?
        ORDER BY m.titulo
    ");
    $stmt->bind_param("i", $artista_id_filtro);
    $stmt->execute();
    $musicas = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Música — Sistema Musical</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">

    <div class="page-header-bar">
        <h2>🎵 Nova Música</h2>
        <div class="header-actions">
            <a href="cadastro_artista.php" class="btn-back">⬅ Voltar para Artistas</a>
        </div>
    </div>

    <?php if (isset($mensagem)): ?>
        <div class="message <?php echo $class; ?>">
            <?php echo $class === 'success' ? '✅' : '⚠️'; ?> <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-grid">

            <div class="form-group">
                <label for="artista_id">Artista <span style="color:var(--red)">*</span></label>
                <select name="artista_id" id="artista_id" required>
                    <option value="">Selecione um artista</option>
                    <?php while ($row = $artistas->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"
                            <?php echo (($_GET['artista_id'] ?? '') == $row['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="titulo">Título <span style="color:var(--red)">*</span></label>
                <input type="text" id="titulo" name="titulo"
                       required placeholder="Ex: Oceano, Aquarela…">
            </div>

            <div class="form-group">
                <label for="album">Álbum</label>
                <input type="text" id="album" name="album" placeholder="Ex: Luz (opcional)">
            </div>

            <div class="form-group">
                <label for="genero">Gênero</label>
                <input type="text" id="genero" name="genero" placeholder="Ex: MPB, Rock…">
            </div>

            <div class="form-group">
                <label for="youtube_link">Link do YouTube <span style="color:var(--red)">*</span></label>
                <input type="url" id="youtube_link" name="youtube_link"
                       required placeholder="https://www.youtube.com/watch?v=…">
                <small>Cole a URL do vídeo (não do canal ou playlist).</small>
            </div>

            <div class="form-group full">
                <label for="letra">Letra da Música</label>
                <textarea id="letra" name="letra" placeholder="Cole a letra aqui (opcional)…"></textarea>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">➕ Cadastrar</button>
        </div>
    </form>

    <?php if ($musicas && $musicas->num_rows > 0): ?>
        <h3>🎵 Músicas Cadastradas</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Álbum</th>
                        <th class="col-youtube">YouTube</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $musicas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($row['album']) ?: '—'; ?></td>
                            <td>
                                <?php
                                $vid = getYoutubeVideoId($row['youtube_link'] ?? '');
                                if ($vid):
                                    $watch_url = "https://www.youtube.com/watch?v={$vid}";
                                ?>
                                    <a href="<?php echo $watch_url; ?>" target="_blank"
                                       rel="noopener noreferrer" class="btn-youtube">▶ Assistir</a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
```

#### ✅ Teste da Etapa 9b
1. Tente cadastrar com `youtube_link = https://google.com` → mensagem de erro "Insira um link válido do YouTube...".
2. Cadastre "Oceano" para o artista "Djavan" com `https://www.youtube.com/watch?v=dQw4w9WgXcQ` → mensagem de sucesso e a música aparece na tabela.
3. Clique em "▶ Assistir" → deve abrir o vídeo em nova aba.

### ETAPA 9c — Editar e excluir músicas (Update e Delete)

```php
<?php
/*
 * =============================================
 * ARQUIVO: cadastro_musica.php (versão 3 — + editar/excluir)
 * =============================================
 */
include('valida_sessao.php');
include('conexao.php');

function getYoutubeVideoId(string $url) {
    $patterns = [
        '/youtube\.com\/watch\?v=([^\&\?\/]+)/',
        '/youtube\.com\/embed\/([^\&\?\/]+)/',
        '/youtube\.com\/v\/([^\&\?\/]+)/',
        '/youtu\.be\/([^\&\?\/]+)/'
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) return $m[1];
    }
    return "";
}

// -----------------------------------------------
// CREATE / UPDATE
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id           = $_POST['id']           ?? '';
    $artista_id   = $_POST['artista_id']   ?? '';
    $titulo       = $_POST['titulo']       ?? '';
    $album        = $_POST['album']        ?? '';
    $genero       = $_POST['genero']       ?? '';
    $letra        = $_POST['letra']        ?? '';
    $youtube_link = $_POST['youtube_link'] ?? '';

    if (empty(getYoutubeVideoId($youtube_link))) {
        $mensagem = "Insira um link válido do YouTube (ex: https://www.youtube.com/watch?v=...).";
        $class    = "error";
    } else {
        if ($id) {
            // UPDATE
            $stmt = $conn->prepare("UPDATE musicas SET artista_id=?, titulo=?, album=?, genero=?, letra=?, youtube_link=? WHERE id=?");
            $stmt->bind_param("ssssssi", $artista_id, $titulo, $album, $genero, $letra, $youtube_link, $id);
            $mensagem = "Música atualizada com sucesso!";
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO musicas (artista_id,titulo,album,genero,letra,youtube_link) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssssss", $artista_id, $titulo, $album, $genero, $letra, $youtube_link);
            $mensagem = "Música cadastrada com sucesso!";
        }
        $class = $stmt->execute() ? "success" : "error";
        if ($class === "error") $mensagem = "Erro: " . $stmt->error;
        $stmt->close();
    }
}

// -----------------------------------------------
// DELETE
// -----------------------------------------------
if (isset($_GET['delete_id'])) {
    $id   = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM musicas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $class    = $stmt->execute() ? "success" : "error";
    $mensagem = $class === "success" ? "Música excluída!" : "Erro: " . $stmt->error;
    $stmt->close();
}

// -----------------------------------------------
// READ (edição e listagem)
// -----------------------------------------------
$musica = null;
if (isset($_GET['edit_id'])) {
    $eid  = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM musicas WHERE id = ?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $musica = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$artistas          = $conn->query("SELECT id, nome FROM artistas ORDER BY nome");
$artista_id_filtro = isset($_GET['artista_id']) ? intval($_GET['artista_id'])
                   : ($musica['artista_id'] ?? null);

$musicas = null;
if ($artista_id_filtro) {
    $stmt = $conn->prepare("
        SELECT m.*, a.nome AS artista_nome
        FROM musicas m
        JOIN artistas a ON m.artista_id = a.id
        WHERE m.artista_id = ?
        ORDER BY m.titulo
    ");
    $stmt->bind_param("i", $artista_id_filtro);
    $stmt->execute();
    $musicas = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $musica ? 'Editar Música' : 'Nova Música'; ?> — Sistema Musical</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">

    <div class="page-header-bar">
        <h2><?php echo $musica ? '✏️ Editar Música' : '🎵 Nova Música'; ?></h2>
        <div class="header-actions">
            <a href="cadastro_artista.php" class="btn-back">⬅ Voltar para Artistas</a>
        </div>
    </div>

    <?php if (isset($mensagem)): ?>
        <div class="message <?php echo $class; ?>">
            <?php echo $class === 'success' ? '✅' : '⚠️'; ?> <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="id" value="<?php echo $musica['id'] ?? ''; ?>">

        <div class="form-grid">

            <div class="form-group">
                <label for="artista_id">Artista <span style="color:var(--red)">*</span></label>
                <select name="artista_id" id="artista_id" required>
                    <option value="">Selecione um artista</option>
                    <?php while ($row = $artistas->fetch_assoc()): ?>
                        <?php $sel = $musica['artista_id'] ?? ($_GET['artista_id'] ?? ''); ?>
                        <option value="<?php echo $row['id']; ?>"
                            <?php echo ($sel == $row['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="titulo">Título <span style="color:var(--red)">*</span></label>
                <input type="text" id="titulo" name="titulo"
                       value="<?php echo htmlspecialchars($musica['titulo'] ?? ''); ?>"
                       required placeholder="Ex: Oceano, Aquarela…">
            </div>

            <div class="form-group">
                <label for="album">Álbum</label>
                <input type="text" id="album" name="album"
                       value="<?php echo htmlspecialchars($musica['album'] ?? ''); ?>"
                       placeholder="Ex: Luz (opcional)">
            </div>

            <div class="form-group">
                <label for="genero">Gênero</label>
                <input type="text" id="genero" name="genero"
                       value="<?php echo htmlspecialchars($musica['genero'] ?? ''); ?>"
                       placeholder="Ex: MPB, Rock…">
            </div>

            <div class="form-group">
                <label for="youtube_link">Link do YouTube <span style="color:var(--red)">*</span></label>
                <input type="url" id="youtube_link" name="youtube_link"
                       value="<?php echo htmlspecialchars($musica['youtube_link'] ?? ''); ?>"
                       required placeholder="https://www.youtube.com/watch?v=…">
                <small>Cole a URL do vídeo (não do canal ou playlist).</small>
            </div>

            <div class="form-group full">
                <label for="letra">Letra da Música</label>
                <textarea id="letra" name="letra"
                          placeholder="Cole a letra aqui (opcional)…"><?php
                    echo htmlspecialchars($musica['letra'] ?? '');
                ?></textarea>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <?php echo $musica ? '💾 Atualizar' : '➕ Cadastrar'; ?>
            </button>
            <?php if ($musica): ?>
                <a href="cadastro_musica.php<?php echo $artista_id_filtro ? '?artista_id='.$artista_id_filtro : ''; ?>"
                   class="btn-back">✖ Cancelar</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($musicas && $musicas->num_rows > 0): ?>
        <h3>🎵 Músicas Cadastradas</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Álbum</th>
                        <th class="col-youtube">YouTube</th>
                        <th class="col-acoes">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $musicas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($row['album']) ?: '—'; ?></td>
                            <td>
                                <?php
                                $vid = getYoutubeVideoId($row['youtube_link'] ?? '');
                                if ($vid):
                                    $watch_url = "https://www.youtube.com/watch?v={$vid}";
                                ?>
                                    <a href="<?php echo $watch_url; ?>" target="_blank"
                                       rel="noopener noreferrer" class="btn-youtube">▶ Assistir</a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?artista_id=<?php echo $artista_id_filtro; ?>&edit_id=<?php echo $row['id']; ?>"
                                       class="btn-edit">✏️ Editar</a>
                                    <a href="?artista_id=<?php echo $artista_id_filtro; ?>&delete_id=<?php echo $row['id']; ?>"
                                       onclick="return confirm('Excluir <?php echo addslashes($row['titulo']); ?>?')"
                                       class="btn-delete">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
```

#### ✅ Teste da Etapa 9c
1. Edite uma música e troque o álbum → alteração deve refletir imediatamente na tabela.
2. Volte para `cadastro_artista.php` e clique em "🎵 Músicas" de um artista específico → o `<select>` já deve vir com esse artista selecionado.
3. Exclua uma música → a linha some da tabela.

### ETAPA 9d — Upload e redimensionamento da capa do álbum

Última camada: campo de arquivo para a capa, reaproveitando a mesma função de redimensionamento criada na Etapa 8d — e removendo o arquivo físico da capa quando a música é excluída.

```php
<?php
/*
 * =============================================
 * ARQUIVO: cadastro_musica.php (versão final — + capa do álbum)
 * =============================================
 *
 * Cada música pertence a um artista (artista_id é
 * chave estrangeira que referencia artistas.id).
 *
 * O link do YouTube é armazenado como URL completa
 * e convertido para o ID do vídeo ao exibir.
 */
include('valida_sessao.php');
include('conexao.php');

function getYoutubeVideoId(string $url) {
    $patterns = [
        '/youtube\.com\/watch\?v=([^\&\?\/]+)/',
        '/youtube\.com\/embed\/([^\&\?\/]+)/',
        '/youtube\.com\/v\/([^\&\?\/]+)/',
        '/youtu\.be\/([^\&\?\/]+)/'
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) return $m[1];
    }
    return "";
}

// Mesma função de redimensionamento já usada em cadastro_artista.php
function redimensionarESalvarImagem(array $arquivo, $largura = 300, $altura = 300) {
    $caminho = "img/" . uniqid() . '_' . basename($arquivo["name"]);
    $tipo    = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));

    if (!getimagesize($arquivo["tmp_name"]))   return "Arquivo não é uma imagem válida.";
    if ($arquivo["size"] > 5000000)            return "Imagem muito grande. Máximo: 5 MB.";
    if (!in_array($tipo, ["jpg","jpeg","png"])) return "Apenas JPG e PNG são aceitos.";

    $orig = imagecreatefromstring(file_get_contents($arquivo["tmp_name"]));
    $w0   = imagesx($orig);
    $h0   = imagesy($orig);
    $r    = min($largura / $w0, $altura / $h0);
    $nw   = (int)($w0 * $r);
    $nh   = (int)($h0 * $r);
    $nova = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($nova, $orig, 0, 0, 0, 0, $nw, $nh, $w0, $h0);
    ($tipo === "png") ? imagepng($nova, $caminho) : imagejpeg($nova, $caminho, 90);
    imagedestroy($orig);
    imagedestroy($nova);
    return $caminho;
}


// -----------------------------------------------
// CREATE / UPDATE
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id           = $_POST['id']           ?? '';
    $artista_id   = $_POST['artista_id']   ?? '';
    $titulo       = $_POST['titulo']       ?? '';
    $album        = $_POST['album']        ?? '';
    $genero       = $_POST['genero']       ?? '';
    $letra        = $_POST['letra']        ?? '';
    $youtube_link = $_POST['youtube_link'] ?? '';

    if (empty(getYoutubeVideoId($youtube_link))) {
        $mensagem = "Insira um link válido do YouTube (ex: https://www.youtube.com/watch?v=...).";
        $class    = "error";
    } else {
        $imagem_capa = "";
        if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] == 0) {
            $upload = redimensionarESalvarImagem($_FILES['imagem_capa']);
            if (str_starts_with($upload, 'img/')) {
                $imagem_capa = $upload;
            } else {
                $mensagem = $upload; $class = "error";
            }
        }

        if (!isset($class)) {
            if ($id) {
                // UPDATE — só troca a capa se uma nova foi enviada
                $sql    = "UPDATE musicas SET artista_id=?, titulo=?, album=?, genero=?, letra=?, youtube_link=?";
                $params = [$artista_id, $titulo, $album, $genero, $letra, $youtube_link];
                if ($imagem_capa) { $sql .= ", imagem_capa=?"; $params[] = $imagem_capa; }
                $sql .= " WHERE id=?"; $params[] = intval($id);
                $stmt  = $conn->prepare($sql);
                $tipos = str_repeat('s', count($params) - 1) . 'i';
                $stmt->bind_param($tipos, ...$params);
                $mensagem = "Música atualizada com sucesso!";
            } else {
                // INSERT
                $stmt = $conn->prepare("INSERT INTO musicas (artista_id,titulo,album,genero,letra,youtube_link,imagem_capa) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param("sssssss", $artista_id, $titulo, $album, $genero, $letra, $youtube_link, $imagem_capa);
                $mensagem = "Música cadastrada com sucesso!";
            }
            $class = $stmt->execute() ? "success" : "error";
            if ($class === "error") $mensagem = "Erro: " . $stmt->error;
            $stmt->close();
        }
    }
}


// -----------------------------------------------
// DELETE (agora também remove o arquivo da capa)
// -----------------------------------------------
if (isset($_GET['delete_id'])) {
    $id   = intval($_GET['delete_id']);
    $stmt = $conn->prepare("SELECT imagem_capa FROM musicas WHERE id = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $arq  = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Remove o arquivo físico da imagem, se existir
    if ($arq && !empty($arq['imagem_capa']) && file_exists($arq['imagem_capa'])) {
        unlink($arq['imagem_capa']); // unlink() = apagar arquivo do servidor
    }

    $stmt = $conn->prepare("DELETE FROM musicas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $class    = $stmt->execute() ? "success" : "error";
    $mensagem = $class === "success" ? "Música excluída!" : "Erro: " . $stmt->error;
    $stmt->close();
}


// -----------------------------------------------
// READ (edição e listagem)
// -----------------------------------------------
$musica = null;
if (isset($_GET['edit_id'])) {
    $eid  = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM musicas WHERE id = ?");
    $stmt->bind_param("i", $eid); $stmt->execute();
    $musica = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$artistas          = $conn->query("SELECT id, nome FROM artistas ORDER BY nome");
$artista_id_filtro = isset($_GET['artista_id']) ? intval($_GET['artista_id'])
                   : ($musica['artista_id'] ?? null);

$musicas = null;
if ($artista_id_filtro) {
    $stmt = $conn->prepare("
        SELECT m.*, a.nome AS artista_nome
        FROM musicas m
        JOIN artistas a ON m.artista_id = a.id
        WHERE m.artista_id = ?
        ORDER BY m.titulo
    ");
    $stmt->bind_param("i", $artista_id_filtro); $stmt->execute();
    $musicas = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $musica ? 'Editar Música' : 'Nova Música'; ?> — Sistema Musical</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">

    <div class="page-header-bar">
        <h2><?php echo $musica ? '✏️ Editar Música' : '🎵 Nova Música'; ?></h2>
        <div class="header-actions">
            <?php if ($artista_id_filtro): ?>
                <a href="cadastro_artista.php" class="btn-back">⬅ Voltar para Artistas</a>
            <?php else: ?>
                <a href="index.php" class="btn-back">⬅ Voltar ao Menu</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($mensagem)): ?>
        <div class="message <?php echo $class; ?>">
            <?php echo $class === 'success' ? '✅' : '⚠️'; ?> <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <!-- FORMULÁRIO -->
    <form method="post" action="" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $musica['id'] ?? ''; ?>">

        <div class="form-grid">

            <!-- Linha 1: Artista + Título -->
            <div class="form-group">
                <label for="artista_id">Artista <span style="color:var(--red)">*</span></label>
                <select name="artista_id" id="artista_id" required
                        <?php echo isset($_GET['artista_id']) ? 'disabled' : ''; ?>>
                    <option value="">Selecione um artista</option>
                    <?php while ($row = $artistas->fetch_assoc()): ?>
                        <?php $sel = $musica['artista_id'] ?? ($_GET['artista_id'] ?? ''); ?>
                        <option value="<?php echo $row['id']; ?>"
                            <?php echo ($sel == $row['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <?php if (isset($_GET['artista_id'])): ?>
                    <input type="hidden" name="artista_id" value="<?php echo intval($_GET['artista_id']); ?>">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="titulo">Título <span style="color:var(--red)">*</span></label>
                <input type="text" id="titulo" name="titulo"
                       value="<?php echo htmlspecialchars($musica['titulo'] ?? ''); ?>"
                       required placeholder="Ex: Oceano, Aquarela…">
            </div>

            <!-- Linha 2: Álbum + Gênero -->
            <div class="form-group">
                <label for="album">Álbum</label>
                <input type="text" id="album" name="album"
                       value="<?php echo htmlspecialchars($musica['album'] ?? ''); ?>"
                       placeholder="Ex: Luz (opcional)">
            </div>

            <div class="form-group">
                <label for="genero">Gênero</label>
                <input type="text" id="genero" name="genero"
                       value="<?php echo htmlspecialchars($musica['genero'] ?? ''); ?>"
                       placeholder="Ex: MPB, Rock…">
            </div>

            <!-- Linha 3: YouTube + Capa -->
            <div class="form-group">
                <label for="youtube_link">Link do YouTube <span style="color:var(--red)">*</span></label>
                <input type="url" id="youtube_link" name="youtube_link"
                       value="<?php echo htmlspecialchars($musica['youtube_link'] ?? ''); ?>"
                       required placeholder="https://www.youtube.com/watch?v=…">
                <small>Cole a URL do vídeo (não do canal ou playlist).</small>
            </div>

            <div class="form-group">
                <label for="imagem_capa">Capa do Álbum</label>
                <input type="file" id="imagem_capa" name="imagem_capa" accept="image/*">
                <small>JPG ou PNG · Máximo 5 MB<?php echo $musica ? ' · Deixe vazio para manter a capa atual' : ''; ?></small>
                <?php if (!empty($musica['imagem_capa'])): ?>
                    <div class="current-image">
                        <img src="<?php echo htmlspecialchars($musica['imagem_capa']); ?>" alt="Capa atual">
                        <small>Capa atual</small>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Linha 4: Letra (ocupa as 2 colunas) -->
            <div class="form-group full">
                <label for="letra">Letra da Música</label>
                <textarea id="letra" name="letra"
                          placeholder="Cole a letra aqui (opcional)…"><?php
                    echo htmlspecialchars($musica['letra'] ?? '');
                ?></textarea>
            </div>

        </div><!-- fim .form-grid -->

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <?php echo $musica ? '💾 Atualizar' : '➕ Cadastrar'; ?>
            </button>
            <?php if ($musica): ?>
                <a href="cadastro_musica.php<?php echo $artista_id_filtro ? '?artista_id='.$artista_id_filtro : ''; ?>"
                   class="btn-back">✖ Cancelar</a>
            <?php endif; ?>
        </div>
    </form>


    <!-- -----------------------------------------------
         TABELA DE MÚSICAS DO ARTISTA SELECIONADO
    ----------------------------------------------- -->
    <?php if ($musicas && $musicas->num_rows > 0): ?>
        <h3>🎵 Músicas Cadastradas</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="col-capa">Capa</th>
                        <th>Título</th>
                        <th>Álbum</th>
                        <th class="col-youtube">YouTube</th>
                        <th class="col-acoes">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $musicas->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($row['imagem_capa']): ?>
                                    <img src="<?php echo htmlspecialchars($row['imagem_capa']); ?>"
                                         alt="Capa" class="table-thumbnail" loading="lazy">
                                <?php else: ?>
                                    <div class="no-image">Sem<br>capa</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($row['album']) ?: '—'; ?></td>
                            <td>
                                <?php
                                $vid = getYoutubeVideoId($row['youtube_link'] ?? '');
                                if ($vid):
                                    $watch_url = "https://www.youtube.com/watch?v={$vid}";
                                ?>
                                    <a href="<?php echo $watch_url; ?>"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="btn-youtube">▶ Assistir</a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?artista_id=<?php echo $artista_id_filtro; ?>&edit_id=<?php echo $row['id']; ?>"
                                       class="btn-edit">✏️ Editar</a>
                                    <a href="?artista_id=<?php echo $artista_id_filtro; ?>&delete_id=<?php echo $row['id']; ?>"
                                       onclick="return confirm('Excluir <?php echo addslashes($row['titulo']); ?>?')"
                                       class="btn-delete">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
```

#### ✅ Teste da Etapa 9d
1. Cadastre uma música enviando uma capa `.jpg` → a capa deve aparecer redimensionada na tabela.
2. Edite essa música sem enviar nova capa → a capa atual deve ser mantida.
3. Exclua a música → a imagem física deve ser removida da pasta `img/` (função `unlink()`) e a linha some da tabela.

---

## ETAPA 10 — Biblioteca Musical (`listagem_musicas.php`)

Página final: lista **todas** as músicas de **todos** os artistas, com filtros combináveis por busca geral, artista e gênero, usando `WHERE` dinâmico com prepared statements. Como você já praticou toda a lógica de exclusão e exibição nas etapas anteriores, esta página já entra completa - a novidade real é a montagem dos filtros.

```php
<?php
/*
 * =============================================
 * ARQUIVO: listagem_musicas.php
 * FUNÇÃO: Biblioteca Musical — listar e filtrar
 * =============================================
 *
 * Exibe todas as músicas com filtros por:
 *   - Busca geral (título, álbum, artista)
 *   - Artista específico
 *   - Gênero musical
 *
 * O botão "Assistir" abre o YouTube em nova aba.
 */
include('valida_sessao.php');
include('conexao.php');

// Inicialização das variáveis
$mensagem = null;
$tipo_msg = null;

// -----------------------------------------------
// FUNÇÃO: Extrair ID do vídeo do YouTube
// -----------------------------------------------
function getYoutubeVideoId(string $url) {
    $patterns = [
        '/youtube\.com\/watch\?v=([^\&\?\/]+)/',
        '/youtube\.com\/embed\/([^\&\?\/]+)/',
        '/youtube\.com\/v\/([^\&\?\/]+)/',
        '/youtu\.be\/([^\&\?\/]+)/'
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $url, $m)) return $m[1];
    }
    return "";
}


// -----------------------------------------------
// MONTAGEM DOS FILTROS DINÂMICOS
// -----------------------------------------------
// Iniciamos com "1=1" (sempre verdadeiro) para poder
// concatenar ANDs sem precisar verificar se é o primeiro filtro.
$where  = "1=1";
$params = [];
$types  = "";

if (!empty($_GET['artista'])) {
    $where   .= " AND a.id = ?";
    $params[] = intval($_GET['artista']); // intval() → garante inteiro (segurança)
    $types   .= "i";
}

if (!empty($_GET['genero'])) {
    $where   .= " AND m.genero LIKE ?";
    $params[] = "%" . $_GET['genero'] . "%"; // % = qualquer texto antes/depois
    $types   .= "s";
}

if (!empty($_GET['busca'])) {
    // OR: busca em múltiplos campos ao mesmo tempo
    $where   .= " AND (m.titulo LIKE ? OR m.album LIKE ? OR a.nome LIKE ?)";
    $busca    = "%" . $_GET['busca'] . "%";
    $params[] = $busca; // para m.titulo
    $params[] = $busca; // para m.album
    $params[] = $busca; // para a.nome
    $types   .= "sss";
}


// Carrega os selects de filtro
$artistas = $conn->query("SELECT id, nome FROM artistas ORDER BY nome");
$generos  = $conn->query("SELECT DISTINCT genero FROM musicas WHERE genero != '' ORDER BY genero");


// -----------------------------------------------
// DELETE
// -----------------------------------------------
if (isset($_GET['delete_id'])) {
    $id   = intval($_GET['delete_id']);
    $stmt = $conn->prepare("SELECT imagem_capa FROM musicas WHERE id = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $arq  = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($arq && !empty($arq['imagem_capa']) && file_exists($arq['imagem_capa'])) {
        unlink($arq['imagem_capa']);
    }

    $stmt = $conn->prepare("DELETE FROM musicas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $tipo_msg = $stmt->execute() ? "success" : "error";
    $mensagem = $tipo_msg === "success" ? "Música excluída!" : "Erro: " . $stmt->error;
    $stmt->close();
}


// -----------------------------------------------
// CONSULTA PRINCIPAL COM FILTROS
// -----------------------------------------------
// JOIN une a tabela musicas com artistas para buscar o nome do artista
$sql = "SELECT m.*, a.nome AS artista_nome
        FROM musicas m
        JOIN artistas a ON m.artista_id = a.id
        WHERE $where
        ORDER BY a.nome, m.titulo";

if (!empty($params)) {
    // Com filtros → prepared statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params); // ... = spread operator (desempacota array)
    $stmt->execute();
    $musicas = $stmt->get_result();
} else {
    // Sem filtros → query simples
    $musicas = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca Musical — Sistema Musical</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27><text y=%27.9em%27 font-size=%2790%27>🎵</text></svg>">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">

    <div class="page-header-bar">
        <h2>🎼 Biblioteca Musical</h2>
        <div class="header-actions">
            <a href="index.php" class="btn-back">⬅ Voltar ao Menu</a>
            <a href="cadastro_musica.php" class="btn-primary">➕ Nova Música</a>
        </div>
    </div>

    <!-- -----------------------------------------------
         FILTROS
         method="get" → filtros ficam na URL, permitindo
         favoritar/compartilhar buscas específicas.
    ----------------------------------------------- -->
    <div class="filters">
        <form method="get" class="filter-form">
            <div class="filter-group">
                <div class="filter-item">
                    <label for="busca">Busca geral</label>
                    <input type="text" name="busca" id="busca"
                           value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>"
                           placeholder="Título, álbum ou artista…">
                </div>
                <div class="filter-item">
                    <label for="artista">Artista</label>
                    <select name="artista" id="artista">
                        <option value="">Todos os artistas</option>
                        <?php while ($a = $artistas->fetch_assoc()): ?>
                            <option value="<?php echo $a['id']; ?>"
                                <?php echo (isset($_GET['artista']) && $_GET['artista'] == $a['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a['nome']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="genero">Gênero</label>
                    <select name="genero" id="genero">
                        <option value="">Todos os gêneros</option>
                        <?php while ($g = $generos->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($g['genero']); ?>"
                                <?php echo (isset($_GET['genero']) && $_GET['genero'] == $g['genero']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g['genero']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">🔍 Filtrar</button>
                <?php if (!empty($_GET)): ?>
                    <a href="listagem_musicas.php" class="btn-back">✖ Limpar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Feedback de operações -->
    <?php if (isset($mensagem)): ?>
        <div class="message <?php echo $tipo_msg; ?>">
            <?php echo $tipo_msg === 'success' ? '✅' : '⚠️'; ?> <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <!-- TABELA DE MÚSICAS -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th class="col-capa">Capa</th>
                    <th>Título</th>
                    <th>Artista</th>
                    <th>Álbum</th>
                    <th>Gênero</th>
                    <th class="col-youtube">YouTube</th>
                    <th class="col-acoes">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($musicas->num_rows > 0): ?>
                    <?php while ($row = $musicas->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($row['imagem_capa']): ?>
                                    <img src="<?php echo htmlspecialchars($row['imagem_capa']); ?>"
                                         alt="Capa" class="table-thumbnail" loading="lazy">
                                <?php else: ?>
                                    <div class="no-image">Sem<br>capa</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($row['artista_nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['album'])  ?: '—'; ?></td>
                            <td><?php echo htmlspecialchars($row['genero']) ?: '—'; ?></td>
                            <td>
                                <?php
                                // Gera a URL de watch (abre a música diretamente, não o canal)
                                $vid = getYoutubeVideoId($row['youtube_link'] ?? '');
                                if ($vid):
                                    $watch = "https://www.youtube.com/watch?v={$vid}";
                                ?>
                                    <!--
                                        target="_blank"       → nova aba
                                        rel="noopener noreferrer" → segurança:
                                        impede que a nova aba acesse a janela atual
                                    -->
                                    <a href="<?php echo $watch; ?>"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="btn-youtube">▶ Assistir</a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="cadastro_musica.php?edit_id=<?php echo $row['id']; ?>"
                                       class="btn-edit">✏️ Editar</a>
                                    <!--
                                        http_build_query($_GET) preserva os filtros ativos
                                        na URL após excluir, para não perder a busca
                                    -->
                                    <a href="?delete_id=<?php echo $row['id']; ?><?php echo !empty($_GET) ? '&' . http_build_query($_GET) : ''; ?>"
                                       onclick="return confirm('Excluir <?php echo addslashes($row['titulo']); ?>?')"
                                       class="btn-delete">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-cell">
                            <?php echo !empty($_GET) ? 'Nenhuma música encontrada com esses filtros.' : 'Nenhuma música cadastrada ainda.'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>



</div>
</body>
</html>
```

### ✅ Teste da Etapa 10 — Filtros
| # | Filtro aplicado | Resultado esperado |
|---|-------------------|---------------------|
| 1 | Nenhum | Todas as músicas cadastradas, ordenadas por artista e depois por título |
| 2 | Busca = "Oceano" | Apenas músicas cujo título, álbum **ou** nome do artista contenha "Oceano" |
| 3 | Artista = "Djavan" | Só as músicas de Djavan |
| 4 | Gênero = "MPB" | Só músicas com gênero contendo "MPB" |
| 5 | Busca + Artista + Gênero combinados | Interseção dos 3 filtros (todos aplicados com `AND`) |
| 6 | Excluir uma música com filtro ativo na URL | Após excluir, os filtros continuam aplicados (graças a `http_build_query($_GET)` no link de exclusão) |
| 7 | Sem resultados | Mensagem "Nenhuma música encontrada com esses filtros." em vez de tabela vazia |

---

## ✅ Checklist Final da Fase 2 — Teste de Ponta a Ponta

Simule o uso real do sistema, do zero:

1. **Banco limpo** → rode `bd.sql` em um banco novo.
2. Acesse `login.php` e entre com `admin` / `123`.
3. Vá em "Gerenciar Artistas" e cadastre 2 artistas (um com foto, outro sem).
4. Entre em "🎵 Músicas" de um deles e cadastre 2 músicas, uma com capa e link do YouTube válido.
5. Vá em "Biblioteca Musical" e filtre por esse artista → deve aparecer só as músicas dele.
6. Edite uma música e confirme que a alteração persiste após recarregar.
7. Tente excluir o artista que tem músicas → deve ser bloqueado.
8. Exclua as músicas, depois exclua o artista → agora deve funcionar.
9. Clique em "🚪 Sair" e confirme que `index.php`, `cadastro_artista.php` etc. voltam a redirecionar para `login.php` quando acessados diretamente pela URL.

Se todos os itens acima passarem, o backend está **100% funcional** e equivalente ao sistema de referência.

### 🔒 Notas de segurança já aplicadas no código
- Senhas armazenadas com `password_hash()` / verificadas com `password_verify()` (bcrypt), nunca em texto puro.
- Todas as consultas com dados do usuário usam **prepared statements** (`bind_param`), prevenindo SQL Injection.
- `htmlspecialchars()` em toda saída de dados do usuário no HTML, prevenindo XSS.
- `intval()` em todo ID vindo da URL (`$_GET['delete_id']`, `$_GET['edit_id']`), prevenindo injeção via parâmetros.
- Validação de tipo e tamanho de arquivo antes de qualquer upload de imagem.
- `valida_sessao.php` incluído no topo de toda página que exige autenticação.
