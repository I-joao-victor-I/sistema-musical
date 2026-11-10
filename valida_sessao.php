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