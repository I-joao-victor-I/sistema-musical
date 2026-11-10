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
function redimensionarESalvarImagem(array $arquivo, $largura = 300, $altura = 300)
{
    $dir          = "img/";
    $nome         = uniqid() . '_' . basename($arquivo["name"]); // nome único
    $caminho      = $dir . $nome;
    $tipo         = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));

    // Validações
    if (!getimagesize($arquivo["tmp_name"]))         return "Arquivo não é uma imagem válida.";
    if ($arquivo["size"] > 5000000)                  return "Imagem muito grande. Máximo: 5 MB.";
    if (!in_array($tipo, ["jpg", "jpeg", "png"]))       return "Apenas JPG e PNG são aceitos.";

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
            if ($imagem) {
                $sql .= ", imagem=?";
                $params[] = $imagem;
            }
            $sql .= " WHERE id=?";
            $params[] = intval($id);
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
                        <tr>
                            <td colspan="5" class="empty-cell">Nenhum artista cadastrado ainda.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>

</html>