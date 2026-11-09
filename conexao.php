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