<?php
/*
* Arquivo: pedidos.php
* 
* Descrição:
* Painel administrativo para gerenciamento de pedidos da pizzaria, exclusivo para usuários com nível 'master'.
* Permite visualizar, filtrar e atualizar o status dos pedidos de forma eficiente.
* 
* Funcionalidades :
* -> Autenticação com verificação de nível de acesso
* -> Sistema completo de filtros (ID, status, data)
* -> Paginação de resultados para melhor performance
* -> Atualização em tempo real do status dos pedidos
* -> Visualização detalhada de todos os dados do pedido
* 
* Fluxo de operação:
* 1) Verifica autenticação e nível de acesso
* 2) Processa atualizações de status (se aplicável)
* 3) Aplica filtros conforme parâmetros GET
* 4) Calcula paginação
* 5) Exibe resultados em tabela dinâmica
* 
* Segurança:
* -> Verificação de token de sessão
* -> Restrição de acesso apenas para nível 'master'
* -> Prepared statements para todas as queries
* -> Sanitização de todos os inputs
* -> Validação de dados antes de processamento
* -> Proteção contra SQL injection
* 
* Dependências:
* -> Framework Bootstrap 5 para interface
* -> Biblioteca Font Awesome para ícones
* -> Biblioteca jQuery para interações client-side
* -> Arquivo config.php com configurações do banco
* -> Função auth() para verificação de autenticação
* -> Função trataPost() para sanitização
* 
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

// ATIVA RELATÓRIO DE ERROS (SOMENTE EM MODO DE DESENVOLVIEMNTO , NÃO EM PRODUÇÃO)
error_reporting(E_ALL);
ini_set('display_errors', 1);


/****************************************************************
 PROCESSO DE ATUALIZAÇÃO DE STATUS DE PEDIDOS (CONFIRMADO, ENTREGUE E CANCELADO)
****************************************************************/

// SE EXISTE A POSTAGEM DE STATUS
if (isset($_POST['status'])&& !empty($_POST['status']) ) {
    
    // TRATA O QUE VEM DO POST
    $pedidoId = trataPost($_POST['pedido_id']);
    $status = trataPost($_POST['status']);
    
    // VALIDA O STATUS 
        if (!preg_match('/^(confirmado|saiu para entrega|entregue|cancelado)$/i', $status)) {
        $erro_status = "Status inválido! Deve ser 'confirmado', 'saiu para entrega', 'entregue' ou 'cancelado'";
    }
    
    try {
        
        // VERIFICA SE TEM O PEDIDO
        $sql = $pdo->prepare("SELECT id FROM pedidos WHERE id = ?");
        $sql->execute([$pedidoId]);
        
        // SE NÃO ENCONTROU
        if (!$sql->fetch()) {
            // MOSTRA A MENSAGEM DE ERRO AO USUÁRIO
            throw new Exception("Pedido não encontrado!");
        }
        
        // SE O PEDIDDO EXISTIR NA ARRAY E ESTIVER OS STATUS , ATUALIZA NO BANCO
        if (in_array($status, ['confirmado', 'entregue', 'saiu para entrega','cancelado'] )) {  
            
            // ATUALIZA O STATUS LA NO SISTEMA
            $sql = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");

            // SE CONSEGIU ATUALIZAR O STATUS NO SISTEMA
            if($sql->execute([$status, $pedidoId])){
                
                // MOSTRA MENSAGEM DE SUCESSO
                $_SESSION['mensagem'] = "Status do pedido #$pedidoId atualizado para " . ucfirst($status) . ".";
                $_SESSION['tipo_mensagem'] = "success";
            }
        }
        
        
        
    } catch (Exception $e) {

        // MOSTRA MENSAGEM DE ERRO SE NAO CONSEGIU ATUALIZAR O STATUS DO PEDIDO 
        $_SESSION['mensagem'] = "Erro: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
    

}


/****************************************************************
*  INICIALIZAÇÃO DOS FILTROS E PAGINAÇÃO DE PEDIDOS
****************************************************************/

// INICIALIZAÇÃO DAS VARIÁVEIS DE FILTRO
$filtroStatus = '';
$filtroData = '';
$filtroId = '';
$sql = "SELECT * FROM pedidos WHERE 1=1"; // QUERY BASE
$filtro = []; // ARRAY PARA ARMAZENAR OS PARÂMETROS DOS FILTROS
$totalSql = ""; // QUERY PARA CONTAGEM TOTAL
$itensPorPagina = 10; // NÚMERO DE ITENS POR PÁGINA


/************************************************************
* CONSTRUÇÃO DOS FILTROS
************************************************************/

// SE A POSTAGEM QUE SERÁ ENVIADA PARA OS FILTROS EXISTIR 
if(isset($_GET['status']) || isset($_GET['data']) || isset($_GET['pedido_id'])) {

    // TRATA ESSES DADOS E ASSOCIA A CADA FILTRO
    $filtroStatus = isset($_GET['status']) ? trataPost($_GET['status']) : '';
    $filtroData = isset($_GET['data']) ? trataPost($_GET['data']) : '';
    $filtroId = isset($_GET['pedido_id']) ? trataPost($_GET['pedido_id']) : '';
    

    // SE O FILTROSTATUS EXISTIR E NAO FOR VAZIO
    if($filtroStatus && $filtroStatus !== '') {

        // CONSULTA NO BANCO O STATUS
        $sql .= " AND status = ?";
        
        // RECEBE O VALOR DA CONSULTA ( ENTEGUE, CANCELADO OU CONIRMADO) NA ARRAY 
        $filtro[] = strtolower($filtroStatus);
    }
    
    // SE O FILTRODATA EXISITR E NÃO FOR VAZIO
    if($filtroData) {
        // CONSUTLA A DATA NO BANCO
        $sql .= " AND DATE(data_pedido) = ?";
        // O VALOR DA CONSULTA É RECEBIDO NA ARAY
        $filtro[] = $filtroData;
    }
    
    
    // SE O FILTROID ESTIVER EXISTIR E NAO FOR VAZIO
    if($filtroId !== '') {
        // CONSULTA A ID NO BANCO
        $sql .= " AND id = ?";
        // RECEBE O VALOR DA CONSULTA NA ARRAY
        $filtro[] = $filtroId;
    }
   
}


/************************************************************
* CONFIGURAÇÃO DA PAGINAÇÃO
************************************************************/

$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina; // CÁLCULO DO OFFSET

//OFFSET $offset:  DETERMINA A PARTIR DE QUAL REGISTRO DEVE COMECÇAR A BUSCA CALCULANDO COM BASE NA PÁGINA ATUAL

// PREPARA A QUERY PARA CONTAGEM TOTAL DE REGISTROS
$totalSql = str_replace("SELECT *", "SELECT COUNT(*) AS total", $sql);

// ADICIONA ORDENAÇÃO E LIMITES À QUERY PRINCIPAL
$sql .= " ORDER BY data_pedido DESC LIMIT $itensPorPagina OFFSET $offset";



/************************************************************
* EXECUÇÃO DAS CONSULTAS
************************************************************/

try {
    
    // CONSULTA PARA OBTER O TOTAL DE REGISTROS
    $stmtTotal = $pdo->prepare($totalSql);
    $stmtTotal->execute($filtro);
    $totalRegistros = $stmtTotal->fetch()['total'];
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);

    // CONSULTA PARA OBTER OS REGISTROS DA PÁGINA ATUAL
    $stmt = $pdo->prepare($sql);
    $stmt->execute($filtro);
    $pedidos = $stmt->fetchAll();
    
    // SE OCORRER ERRO NAS CONSULTAS
} catch (PDOException $e) {

    // MOSTRA ERRO AO USUARIO
    die("Erro ao buscar pedidos: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- META-TAGS PARA CONFIGURACOES DO SITE : -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- TITULO DA PAGINA -->
    <title>Pedidos</title>

    <!-- FAVICON -->
    <link rel="icon" href="assets\img\favicon\favicon.ico" type="image/x-icon">
   
    <!-- REFERÊNCIA DO BOOTSTRAP CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- REFERÊNCIA DO FONT-AWESOME(BIBLIOTECA DE ICONES ) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- REFERÊNCIA DO CSS EXTERNO -->
    <link href="assets/css/painel_admin.css" rel="stylesheet">
      
</head>



<body>
    <div class="wrapper">
        
        <!-- ==============================================
            SEÇÃO DO OFFCANVAS (MENU LATERAL)
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
            
        <!-- TITULO DO MENU -->
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Pizzaria Admin</h5>
            </div>
            
            <!-- CORPO DO MENU -->
            <div class="offcanvas-body p-0">
                <div class="mt-3">
                    
                    <!-- SUBMENUS -->
                    <ul class="nav flex-column">
                        
                        <!-- PEDIDO -->
                        <li class="nav-item">
                            <a href="admin_pedidos.php" class="nav-link active">
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

                        <!-- PRODUTOS -->
                        <li class="nav-item">
                            <a href="admin_produtos.php" class="nav-link">
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
                        
                        
                        <!-- SAIR -->
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
    


        <div class="content-wrapper">
            <section class="content">

                <div class="container-fluid">
                    
                    <!-- ==============================================
                     SEÇÃO DE EXBIÇÃO DAS MENSAGENS DE ERRO E SUCESSO
                    =============================================== -->

                    <?php if (isset($_SESSION['mensagem'])): ?>
                        <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?> alert-dismissible fade show">
                            <?= $_SESSION['mensagem'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
                    <?php endif; ?>
                    
                    
                    
                    <!-- ==============================================
                        SEÇÃO DOS FILTROS
                    =============================================== -->

                    <div class="card mb-4">
                        
                        <div class="card-header">
                            <h3 class="card-title mb-0">Filtrar Pedidos</h3>
                        </div>
                        
        
                       <div class="card-body">
                            <form method="GET">
                                <div class="row align-items-end g-3"> 
                                    
                                <!--FILTRO PELA ID-->
                                    <div class="col-12 col-sm-6 col-md-3">
                                        <label for="pedido_id" class="form-label text-white mb-1">ID</label>
                                        <input type="number" class="form-control" name="pedido_id"  value="<?= htmlspecialchars($filtroId) ?>">
                                    </div>
                                    
                                    <!--FILTRO POR STATUS-->
                                    <div class="col-12 col-sm-6 col-md-3">
                                        <label for="status" class="form-label text-white mb-1">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="" <?= empty($filtroStatus) ? 'selected' : '' ?>>Todos</option>
                                            <option value="confirmado" <?= $filtroStatus === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
                                            <option value="saiu para entrega" <?= $filtroStatus === 'saiu para entrega' ? 'selected' : '' ?>>Saiu para Entrega</option>
                                            <option value="entregue" <?= $filtroStatus === 'entregue' ? 'selected' : '' ?>>Entregue</option>
                                        </select>
                                    </div>
                                    
                                    <!--FILTRO POR DATA-->
                                    <div class="col-12 col-sm-6 col-md-3">
                                        <label for="data" class="form-label text-white mb-1">Data</label>
                                        <input type="date" class="form-control" name="data" value="<?= $filtroData ?>">
                                    </div>
                                    
                                    <!--BOTÕES DE FILTRO-->
                                    <div class="col-12 col-sm-6 col-md-3">
                                        <div class="d-grid gap-2 d-md-flex" style="height: 100%;">
                                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-filter me-1"></i> Filtrar
                                            </button>
                                            <a href="pedidos.php" class="btn btn-secondary btn-sm flex-grow-1 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-broom me-1"></i> Limpar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        
                        
                    </div>


                    <!-- ==============================================
                        SEÇÃO DA TABELA DINÂMICA COM OS PEDIDOS
                    =============================================== -->

                    <div class="card">
                        <!-- TITULO DA TABELA -->
                        <div class="card-header">
                            <h3 class="card-title mb-0">Lista de Pedidos</h3>
                        </div>

                        <!-- AREA DA TABELA-->
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">

                                    <!-- COLUNAS DA TABELA -->
                                    <thead class="table-dark">
                                        
                                        <tr>
                                            <th>ID</th>
                                            <th>Cliente</th>
                                            <th>Pedido</th>
                                            <th>Contato</th>
                                            <th>Valor</th>
                                            <th>Data</th>
                                            <th>Pagamento</th>
                                            <th>Endereço</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>

                                    <!-- CORPO DA TABELA (LINHAS) -->
                                    <tbody>

                                        <!-- SE A TABELA PEDIDOS DO BANCO NÃO TIVER NENHUM PEDIDO -->
                                        <?php if (empty($pedidos)): ?>

                                            <!-- EXIBE UMA LINHA PARA O USUÁRIO COM A MENSAGEM -->
                                            <tr>
                                                <td colspan="8" class="text-center">Nenhum pedido encontrado</td>
                                            </tr>
                                            
                                            <!-- CASO CONTRÁRIO -->
                                        <?php else: ?>
                                                
                                            <!-- PERCORE A TABELA PEDIDOS DO BANCO E VAI EXIBINDO AS INFORMAÇÕES DOS PEDIDOS EM CADA LINHA -->
                                            <?php foreach ($pedidos as $pedido): ?>
                                                <tr>
                                                    <td>#<?= htmlspecialchars($pedido['id']) ?></td>
                                                    <td><?= htmlspecialchars($pedido['cliente']) ?></td>
                                                    <td><?= htmlspecialchars($pedido['pedido']) ?></td>
                                                    <td><?= $pedido['numero'] ?></td>
                                                    <td>R$ <?= $pedido['valor_total'] ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td>
                                                    <td><?= htmlspecialchars($pedido['pagamento']) ?></td>
                                                    <td><?= htmlspecialchars($pedido['endereco']) ?></td>
                                                   
                                                    <td>
                                                        <form method="post">
                                                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                                <option value="confirmado" <?= $pedido['status'] === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
                                                                <option value="saiu para entrega" <?= $pedido['status'] === 'saiu para entrega' ? 'selected' : '' ?>>Saiu para entrega</option>
                                                                <option value="entregue" <?= $pedido['status'] === 'entregue' ? 'selected' : '' ?>>Entregue</option>
                                                                <option value="cancelado" <?= $pedido['status'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                                            </select>
                                                        </form>
                                                    </td>
                                                    
                                                    
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        
                        
                        
                        
                        
                        <!-- ==============================================
                            SEÇÃO DA PAGINAÇÃO
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
                
                
            </section>
            
            
            
        </div>
        
        
         
        <!-- ==============================================
            RODAPÉ DA PAGINA
        =============================================== -->

        <footer class="main-footer">
            <strong>© <?= date('Y') ?> Pizzaria Admin</strong>
        </footer>


    </div>
    
    <!-- REFERÊNCIA DO JQUERY E BOOTSTRAP -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        
        $(document).ready(function() {
            // FECHAR ALERTA APÓS 5 SEGUNDOS
            setTimeout(() => $('.alert').alert('close'), 5000);
            
            // ATUALIZAR DATA NO RODAPÉ AUTOMATICAMENTE
            const yearSpan = document.querySelector('.main-footer strong');
            yearSpan.textContent = `© ${new Date().getFullYear()} Pizzaria Admin`;
        });
    </script>
</body>
</html>