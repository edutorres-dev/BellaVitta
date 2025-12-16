<?php
/*
* Arquivo: produtos.php
* 
* Descrição:
* Painel administrativo para gerenciamento de produtos (pizzas) do cardápio, exclusivo para usuários com nível 'master'.
* Permite adicionar, editar, visualizar e excluir produtos com seus respectivos preços e imagens.
* 
* Funcionalidades :
* -> Autenticação rigorosa com verificação de nível de acesso
* -> CRUD completo de produtos (Create, Read, Update, Delete)
* -> Upload e gerenciamento de imagens dos produtos
* -> Validação de dados e formatos
* -> Paginação de resultados
* -> Inicialização automática do cardápio com pizzas padrão
* 
* Fluxo de operação:
* 1) Verifica autenticação e nível de acesso
* 2) Processa formulários de adição/edição (POST)
* 3) Processa exclusões (POST)
* 4) Prepara dados para edição (GET)
* 5) Carrega lista de produtos com paginação
* 6) Inicializa cardápio padrão se vazio
* 7) Exibe interface administrativa
* 
* Segurança:
* -> Verificação de token de sessão
* -> Restrição de acesso apenas para nível 'master'
* -> Sanitização de todos os inputs
* -> Validação de arquivos de imagem
* -> Prepared statements para todas as queries
* -> Proteção contra SQL injection
* -> Verificação de tipos de arquivo no upload
* 
* Dependências:
* -> Bootstrap 5 para interface
* -> Font Awesome para ícones
* -> jQuery para interações client-side
* -> Arquivo config.php com configurações do banco
* -> Função auth() para verificação de autenticação
* -> Função trataPost() para sanitização
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2025 BellaVitta
* 

*/ 


/****************************************************************
 * CONFIGURAÇÕES INICIAIS E SEGURANÇA
****************************************************************/

// PUXA AS INFORMAÇÕES DO BANCO
require("assets/config/config.php");

// VERIFICANDO AUTENTICAÇÃO
$usuario = auth($_SESSION["TOKEN"]);

// SE NÃO ESTIVER AUTENTICADO OU NÃO FOR MASTER
if(!$usuario || $usuario['nivel_acesso'] !== 'master') {
    // REDIRECIONA PARA O LOGIN
    header("Location: login.php");
    exit; 
}

// ATIVA O RELATÓRIO DE ERROS 
error_reporting(E_ALL);
ini_set('display_errors', 1);

// VERIFICA SE A CONEXÃO FOI ESTABELECIDA COM A INSTÂNCIA PDO
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Erro crítico: Conexão com o banco de dados não estabelecida.");
}

/****************************************************************
 * CONFIGURAÇÃO DA PAGINAÇÃO
****************************************************************/
$itensPorPagina = 5; // 5 produtos por página
$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;


/****************************************************************
 PROCESSAMENTO DO FORMULÁRIO DE PRODUTOS
****************************************************************/

// SE EXISTE A POSTAGEM PARA ATUALIZAR OU INSERIR NOVO PRODUTO AO SISTEMA 
if (isset($_POST['salvar'])) {
    
    /************************************************************
     TRATAMENTO DOS DADOS 
    ************************************************************/

    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $nome = trataPost($_POST['nome']);
    $precoPequena = (float)str_replace(['R$', '.', ','], ['', '', '.'], trataPost($_POST['precoPequena']));
    $precoMedia = (float)str_replace(['R$', '.', ','], ['', '', '.'], trataPost($_POST['precoMedia']));
    $precoGrande = (float)str_replace(['R$', '.', ','], ['', '', '.'], trataPost($_POST['precoGrande']));

    /************************************************************
    * TRATAMENTO DO UPLOAD DA IMAGEM
    ************************************************************/
    $diretorio_imagens = "assets/img/cardapio/";
    $imagem = isset($_POST['imagem_atual']) ? $_POST['imagem_atual'] : '';
    
    // SE EXISITE A IMAGEM
    if (isset($_FILES['imagem_upload']) && $_FILES['imagem_upload']['error'] === UPLOAD_ERR_OK) {
        
        $nome_arquivo = uniqid() . '_' . basename($_FILES['imagem_upload']['name']);
        $caminho_completo = $diretorio_imagens . $nome_arquivo;
        
        // VERIFICA SE É UMA IMAGEM VÁLIDA
        $check = getimagesize($_FILES['imagem_upload']['tmp_name']);
        if ($check !== false) {
            // MOVE O ARQUIVO PARA O DIRETÓRIO DE IMAGENS
            if (move_uploaded_file($_FILES['imagem_upload']['tmp_name'], $caminho_completo)) {
                $imagem = $caminho_completo;
                
                // REMOVE A IMAGEM ANTIGA SE ESTIVER EDITANDO
                if ($id && isset($_POST['imagem_atual']) && $_POST['imagem_atual'] && file_exists($_POST['imagem_atual'])) {
                    unlink($_POST['imagem_atual']);
                }


                // CASO OCORRA ERRO EM MOVER O ARQUIVO 
            } else {

                // MOSTRA MENSAGEM DE ERRO AO USUARIO 
                $_SESSION['mensagem'] = "Erro ao fazer upload da imagem.";
                $_SESSION['tipo_mensagem'] = "danger";

                // REDIRECIONA O USUÁRIO
                header("Location: admin_produtos.php");
                exit;
            }


            // SE NÃO FOR UMA IMAGEM VÁLIDA 
        } else {

            // MOSTRA MENSAGEM DE ERRO AO USUÁRIO
            $_SESSION['mensagem'] = "O arquivo enviado não é uma imagem válida.";
            $_SESSION['tipo_mensagem'] = "danger";
            
            // RECARREGA A PAGINA  
            header("Location: admin_produtos.php");
            exit;
        }


    
       // CASO O CAMPO DA IMAGEM SEJA ENVIADO VAZIO
    } elseif (!$id && empty($_FILES['imagem_upload']['name'])) {

        // MOSTRA A MENSAGEM AO USUÁRIO 
        $_SESSION['mensagem'] = "Por favor, selecione uma imagem para o produto.";
        $_SESSION['tipo_mensagem'] = "danger";

        header("Location: admin_produtos.php");
        exit;
    }

    try {

        /************************************************************
         * PROCESSO PARA ATUALIZAR PRODUTO NO SISTEMA
        ************************************************************/

        // SE EXISTE A ID DO PRODUTO ( SE EXISTE O PRODUTO)
        if ($id) {

            // ATUALIZA ELE
            $sql = $pdo->prepare("UPDATE produtos SET nome = ?, imagem = ?, preco_pequena = ?, preco_media = ?, preco_grande = ? WHERE id = ?");
            
            // SE ATUALIZOU 
            if($sql->execute([$nome, $imagem, $precoPequena, $precoMedia, $precoGrande, $id])){

                // MOSTRA A MENSAGEM DE SUCESSO
                $_SESSION['mensagem'] = "Produto atualizado com sucesso!";
                $_SESSION['tipo_mensagem'] = "success";

                // REDIRECIONA O USUÁRIO
                header("Location: admin_produtos.php");
                exit;
            }
            

            /************************************************************
              CASO NAO EXISTA , INSERE PRODUTO NO SISTEMA
            ************************************************************/
        } else {

            // INSERIR NOVO PRODUTO
            $sql = $pdo->prepare("INSERT INTO produtos (nome, imagem, preco_pequena, preco_media, preco_grande) VALUES (?, ?, ?, ?, ?)");
            
            // SE INSERIU
            if($sql->execute([$nome, $imagem, $precoPequena, $precoMedia, $precoGrande])){

                // MOSTRA A MENSAGEM DE SUCESSO AO USUÁRIO 
                $_SESSION['mensagem'] = "Produto adicionado com sucesso!";
                $_SESSION['tipo_mensagem'] = "success";

                //REDIRECIONA O USUÁRIO 
                header("Location: admin_produtos.php");
                exit;
            }
        }

        //CASO TENHA OCORRIDO ALGUM ERRO NA HORA DE ATUALIZAR OU INSERIR  
    } catch (PDOException $e) {

        //MOSTRA A MENSAGEM DE ERRO AO USUÁRIO
        $_SESSION['mensagem'] = "Erro ao salvar produto: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
}


/****************************************************************
 * PROCESSO PARA EXCLUIR PRODUTOS
 ****************************************************************/

// SE EXISTE A POSTAGEM DE EXCLUSÃO DO PROUDTO
if(isset($_POST['excluir'])) {
    $id = (int)$_POST['id'];
    
    try {
        // OBTÉM O CAMINHO DA IMAGEM
        $sql = $pdo->prepare("SELECT imagem FROM produtos WHERE id = ?");
        $sql->execute([$id]);
        $produto = $sql->fetch();
        
        // REMOVE A IMAGEM DO SERVIDOR
        if ($produto && !empty($produto['imagem']) && file_exists($produto['imagem'])) {
            unlink($produto['imagem']);
        }
        
        // DELETA O PRODUTO
        $sql = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        
        // SE DELETOU
        if($sql->execute([$id])){
            // MOSTRA A MENSAGEM DE SUCESSO AO USUÁRIO 
            $_SESSION['mensagem'] = "Produto excluído com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";

            // REDIREICONA O USUARIO 
            header("Location: admin_produtos.php");
            exit;
        }
        
     
        // SE OCORREU ERRO AO EXCLUIR O PRODUTO 
    } catch (PDOException $e) {

        // MOSTRA A MENSAGEM DE ERRO AO USUÁRIO 
        $_SESSION['mensagem'] = "Erro ao excluir produto: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
}

// INICIALIZA VARIAVEIS (FLAGS)
$editando = false;
$produto_edit = null;



/****************************************************************
* PROCESSO DE PREPARAÇÃO PARA EDIÇÃO DE PRODUTO
****************************************************************/

// SE EXISTE A POSTAGEM PARA EDITAR O PRODUTO ( VIA GET)
if (isset($_GET['editar'])) {


    $id = (int)$_GET['editar'];
    
    try {

        //FAZ A CONSULTA DO PRODUTO PELO ID 
        $sql = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $sql->execute([$id]);
        
        // ARMAZENA O PRODUTO NA VARIAVEL
        $produto_edit = $sql->fetch();
        
        // SE O PRODUTO , PODE FAZER A EDIÇÃO
        if ($produto_edit) {
            $editando = true;
        }
        
        // CASO ELE NÃO ENCONTRE O PRODUTO
    } catch (PDOException $e) {
        // MOSTRA A MENSAGEM DE ERRO AO USUARIO 
        die("Erro ao buscar produto: " . $e->getMessage());
 
    }
}

/****************************************************************
* CARREGAMENTO DA LISTA DE PRODUTOS COM PAGINAÇÃO
****************************************************************/
try {
    // Query para contar o total de registros
    $sqlTotal = "SELECT COUNT(*) AS total FROM produtos";
    $stmtTotal = $pdo->query($sqlTotal);
    $totalRegistros = $stmtTotal->fetch()['total'];
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);

    // Query principal com paginação
    $sql = "SELECT * FROM produtos ORDER BY nome LIMIT $itensPorPagina OFFSET $offset";
    $stmt = $pdo->query($sql);
    $produtos = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Erro ao buscar produtos: " . $e->getMessage());
}

/****************************************************************
 * INICIALIZAÇÃO DO CARDÁPIO (INSERE AS PIZZAS PADRÃO CASO NÃO TENHA NENHUM PRODUTO CADASTRADO NO BANCO)
****************************************************************/

if (empty($produtos) && $paginaAtual == 1) {
    $pizzas_padrao = [
        ['Calabresa', 'assets/img/cardapio/calabresa.jpg', 30.00, 55.00, 75.00],
        ['Marguerita', 'assets/img/cardapio/marg.jpg', 32.00, 58.00, 78.00],
        ['Portuguesa', 'assets/img/cardapio/portuguesa.jpg', 32.00, 58.00, 78.00],
        ['Pepperoni', 'assets/img/cardapio/peperoni (2).jpg', 32.00, 58.00, 78.00],
        ['Frango com Catupiry', 'assets/img/cardapio/frango_catupry.jpg', 32.00, 58.00, 78.00],
        ['Prestígio', 'assets/img/cardapio/prestigio.jpg', 32.00, 58.00, 78.00],
        ['Chocolate com Morango', 'assets/img/cardapio/choco_morango.jpg', 32.00, 58.00, 78.00],
        ['Banana com Canela', 'assets/img/cardapio/banana.jpg', 32.00, 58.00, 78.00]
    ];
    
    try {
        // COMANDO PARA INSERIR OS PRODUTOS 
        $sql= $pdo->prepare("INSERT INTO produtos (nome, imagem, preco_pequena, preco_media, preco_grande) VALUES (?, ?, ?, ?, ?)");
        
        // PERCORE A ARRAY E FAZ A INSERÇÃO DE CADA PRODUTO 
        foreach ($pizzas_padrao as $pizza) {
            $sql->execute($pizza);
        }
        
        // RECARREGA OS PRODUTOS APOS INSERCAO 
        $sql = $pdo->query("SELECT * FROM produtos ORDER BY nome");
        $produtos = $sql->fetchAll();
        

        // SE OCORREU ERRO AO INSERIR AS PIZZAS PADRAO
    } catch (PDOException $e) {
        // MOSTRA A MENSAGEM DE ERRO AO USUARIO 
        die("Erro ao inserir pizzas padrão: " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- META-TAGS PARA CONFIGURAÇÕES DO SITE (COMPATIBILIDADE ,FORMATAÇÃO DO ESCOPO , ETC) -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- TITULO DA PAGINA -->
    <title> Produtos</title>

    <!-- REFERÊNCIA DO BOOTSTRAP CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- REFERÊNCIA DO FONT-AWESOME (BIBLIOTECA DE ICONES) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- FAVICON -->
    <link rel="icon" href="assets\img\favicon\favicon.ico" type="image/x-icon">

    <!-- REFERÊNCIA DO CSS EXTERNO -->
    <link href="assets/css/painel_admin.css" rel="stylesheet">

    <!-- CSS INTERNO PARA OS CARDS DOS PRODUTOS DENTRO DA TABELA -->
    <style>
        .card-img-top {
            height: 150px;
            object-fit: cover;
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .table-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
        }
    </style>

</head>
<body>
    <div class="wrapper">

       <!-- ==============================================
          SEÇÃO DO OFFCANVAS(MENU LATERAL)
        =============================================== -->
        
       
    
        <nav class="navbar navbar-dark bg-dark d-lg-none fixed-top">
            <div class="container-fluid">
                 <button class="btn d-lg-none position-fixed" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" style="left: 10px; top: 10px; z-index: 100; background-color: #FFD700; color: black;">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand ms-auto me-auto text-warning">Pizzaria Admin</span>
            </div>
        </nav>
        
     
        <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" style="width: 250px;">
            
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Pizzaria Admin</h5>
            </div>
            
            <!-- AREA DE SUBMENUS DO OFFACANVAS -->
            <div class="offcanvas-body p-0">

                <div class="mt-3">
                    <!-- SUBMENUS DO OFFCANVAS -->
                    <ul class="nav flex-column">

                        <!-- PEDIDOS -->
                        <li class="nav-item">
                            <a href="admin_pedidos.php" class="nav-link ">
                                <i class="fas fa-shopping-cart me-2"></i>
                                <span>Pedidos</span>
                            </a>
                        </li>

                        <!-- CLIENTES -->
                        <li class="nav-item">
                            <a href="admin_clientes.php" class="nav-link">
                                <i class="fas fa-users me-2"></i>
                                <span>Clientes</span>
                            </a>
                        </li>
                        

                        <!-- PRODUTOS  -->
                        <li class="nav-item">
                            <a href="admin_produtos.php" class="nav-link active">
                                <i class="fas fa-pizza-slice me-2"></i>
                                <span>Produtos</span>
                            </a>
                        </li>
                        

                        <!-- FINANCEIRO  -->
                        <li class="nav-item">
                            <a href="admin_financeiro.php" class="nav-link">
                                <i class="fas fa-dollar-sign me-2"></i>
                                <span>Financeiro</span>
                            </a>
                        </li>
                        
                        
                        <!-- SAIR(lOGOUT DO SISTEMA) -->
                        <li class="nav-item">
                            <a href="logout.php" class="nav-link text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                <span>Sair</span>
                            </a>
                        </li>


                    </ul>

                </div>

            </div>

        </div>


        
        <!-- ==============================================
            SEÇÃO DO CONTEÚDO PRINCIPAL DOS PRODUTOS
        =============================================== -->
        <div class="content-wrapper">

            <section class="content">

                <div class="container-fluid">
                    
                    <!-- MENSAGENS DE SUCESSO OU DE ERRO -->

                    <?php if (isset($_SESSION['mensagem'])): ?>
                        <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?> alert-dismissible fade show">
                            <?= $_SESSION['mensagem'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
                    <?php endif; ?>
                    


                    <!-- ==============================================
                     SEÇÃO DO FORMULARIO PARA ADICIONAR / EDITAR PRODUTO
                    =============================================== -->

                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title mb-0"><?= $editando ? 'Editar Produto' : 'Adicionar Novo Produto' ?></h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="form-container" enctype="multipart/form-data">
                                <?php if ($editando): ?>
                                    <input type="hidden" name="id" value="<?= $produto_edit['id'] ?>">
                                <?php endif; ?>
                                
                                <!--CAMPO NOME DA PIZZA-->
                                <div class="mb-3">
                                    <label for="nome" class="form-label text-white">Nome da Pizza</label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                    value="<?= $editando ? htmlspecialchars($produto_edit['nome']) : '' ?>" required>
                                </div>
                                
                                <!--CAMPO PARA IMAGEM-->
                                <div class="mb-3">
                                    <label for="imagem_upload" class="form-label text-white">Imagem do Produto</label>
                                    <input type="file" class="form-control" id="imagem_upload" name="imagem_upload" accept="image/*">
                                    <?php if ($editando): ?>
                                        <div class="mt-2">
                                            <img src="<?= htmlspecialchars($produto_edit['imagem']) ?>" alt="Preview" style="max-height: 100px;" class="img-thumbnail">
                                            <input type="hidden" name="imagem_atual" value="<?= htmlspecialchars($produto_edit['imagem']) ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- AREA DE CAMPOS PARA INSERIR OS VALORES DOS TAMAHOS DAS PIZZAS-->
                                <div class="row mb-3">

                                    <!-- CAMPO DE PREÇO DA PIZZA PEQUENA  -->
                                    <div class="col-12 col-md-4 mb-3 mb-md-0">
                                        <label for="precoPequena" class="form-label text-white">Preço Pequena</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control" id="precoPequena" name="precoPequena" 
                                                   value="<?= $editando ? number_format($produto_edit['preco_pequena'], 2, ',', '.') : '' ?>" required>
                                        </div>
                                    </div>

                                    <!-- CAMPO DE PREÇO DA PIZZA MÉDIA  -->
                                    <div class="col-12 col-md-4 mb-3 mb-md-0">
                                        <label for="precoMedia" class="form-label text-white">Preço Média</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control" id="precoMedia" name="precoMedia" 
                                                   value="<?= $editando ? number_format($produto_edit['preco_media'], 2, ',', '.') : '' ?>" required>
                                        </div>
                                    </div>
                                    
                                    <!-- CAMPO DE PREÇO DA PIZZA GRANDE  -->
                                    <div class="col-12 col-md-4">
                                        <label for="precoGrande" class="form-label text-white">Preço Grande</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control" id="precoGrande" name="precoGrande" 
                                                   value="<?= $editando ? number_format($produto_edit['preco_grande'], 2, ',', '.') : '' ?>" required>
                                        </div>
                                    </div>


                                </div>
                                
                                <!-- BOTÕES -->
                                <div class="d-flex justify-content-end">
                                    <?php if ($editando): ?>
                                        <a href="admin_produtos.php" class="btn btn-secondary me-2">Cancelar</a>
                                    <?php endif; ?>
                                    <button type="submit" name="salvar" class="btn btn-primary">
                                        <?= $editando ? 'Atualizar Produto' : 'Adicionar Produto' ?>
                                    </button>
                                </div>


                            </form>
                        </div>
                    </div>
                    
                    <!-- ==============================================
                     SEÇÃO DA TABELA DE PRODUTOS DINÂMICA
                    =============================================== -->

                    <div class="card">

                        <!-- TITULO  -->
                        <div class="card-header">
                            <h3 class="card-title mb-0">Lista de Produtos</h3>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <!--LINHAS DA TABELA(INDICE)-->
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Imagem</th>
                                            <th>Nome</th>
                                            <th>Pequena</th>
                                            <th>Média</th>
                                            <th>Grande</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    
                                    <!--CORPO DA TABELA(LINHAS)-->
                                    <tbody>
                                        <!--CASO NAO TENHA NENHUM PRODUTO NO BANCO -->
                                        <?php if (empty($produtos)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Nenhum produto cadastrado</td>
                                            </tr>
                                        <?php else: ?>
                                        
                                            <!--PERCORE OS DADOS DA TABELA PRODUTOS COMO ARRAY , EXIBINDO AS INFORMACOES E TRATANDO -->
                                            <?php foreach ($produtos as $produto): ?>
                                                <tr>
                                                    <td><img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" class="table-img rounded"></td>
                                                    <td><?= htmlspecialchars($produto['nome']) ?></td>
                                                    <td>R$ <?= number_format($produto['preco_pequena'], 2, ',', '.') ?></td>
                                                    <td>R$ <?= number_format($produto['preco_media'], 2, ',', '.') ?></td>
                                                    <td>R$ <?= number_format($produto['preco_grande'], 2, ',', '.') ?></td>
                                                    <td>
                                                        <!-- Botão para Editar Produto -->
                                                        <a href="admin_produtos.php?editar=<?= $produto['id'] ?>" class="btn btn-sm btn-warning me-1 mb-1">
                                                            <i class="fas fa-edit"></i>
                                                            <span class="d-none d-md-inline"> Editar</span>
                                                        </a>
                                                        
                                                        <!-- Botão para Remover Produto -->
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                                                            <button type="submit" name="excluir" class="btn btn-sm btn-danger me-1 mb-1 " 
                                                                    onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                                                <i class="fas fa-trash"></i>
                                                                <span class="d-none d-md-inline"> Excluir</span>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- ==============================================
                                SEÇÃO DE PAGINAÇÃO
                            =============================================== -->
                            <div class="card-footer">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center mb-0">
                                        <?php if ($paginaAtual > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $paginaAtual - 1])) ?>">Anterior</a>
                                            </li>
                                        <?php endif; ?>
                        
                                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                            <li class="page-item <?= $i === $paginaAtual ? 'active' : '' ?>">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                        
                                        <?php if ($paginaAtual < $totalPaginas): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $paginaAtual + 1])) ?>">Próxima</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>




                        </div>
                    </div>
                </div>
            </section>
        </div>
        
        <!-- ==============================================
            SEÇÃO DO RODAPÉ 
        =============================================== -->
       
        <footer class="main-footer">
            <strong>© <?= date('Y') ?> Pizzaria Admin</strong>
        </footer>


    </div>

    <!--REFERÊNCIAS DE JQUERY E BOOTSTRAP CSS-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // FECHAR OS ALERTAS DE MENSAGEM APOS 5 SEGUNDOS 
            setTimeout(() => $('.alert').alert('close'), 5000);
            
            // ATUALIZAR PREVIEW DA IMAGEM
            $('#imagem_upload').on('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // CRIA OU ATUALIZA A IMAGE DO PREVIEW NA TABELA
                        let preview = $('#imagem_upload').next().find('img');
                        if (preview.length === 0) {
                            preview = $('<img>', {
                                alt: 'Preview',
                                style: 'max-height: 100px;',
                                class: 'img-thumbnail mt-2'
                            });
                            $('#imagem_upload').after(preview);
                        }
                        preview.attr('src', e.target.result).show();
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // FORMATAR VALORES MONETÁRIOS 
            $('input[name="precoPequena"], input[name="precoMedia"], input[name="precoGrande"]').on('blur', function() {
                let value = $(this).val().replace(/[^\d,]/g, '').replace(',', '.');
                value = parseFloat(value || 0).toFixed(2);
                $(this).val(value.toString().replace('.', ','));
            });
        });
    </script>

    
</body>
</html>