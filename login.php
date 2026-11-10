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