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