<?php

/*
* Arquivo: financeiro.php
* 
* Descrição:
* Painel administrativo de análise financeira, exclusivo para usuários com nível 'master'.
* Apresenta dados consolidados de vendas através de gráficos e métricas, com filtros temporais avançados.
* 
* Funcionalidades principais:
* -> Autenticação com verificação de nível de acesso
* -> Sistema completo de filtros temporais (ano, mês, dia)
* -> Visualização de dados através de gráficos interativos
* -> Métricas financeiras e de volume de vendas
* -> Interface intuitiva e responsiva com atualização automática
* 
* Fluxo de operação:
* 1) Verifica autenticação e nível de acesso
* 2) Processa filtros temporais recebidos via GET
* 3) Constrói queries dinâmicas baseadas nos filtros
* 4) Executa consultas para diferentes visualizações:
*    - Formas de pagamento (gráfico de pizza)
*    - Sabores mais vendidos (gráfico de barras)
*    - Evolução temporal (gráfico de barras por período)
* 5) Calcula totais e métricas financeiras
* 6) Exibe resultados em gráficos e cards de resumo
* 
* Segurança:
* -> Verificação de token de sessão
* -> Restrição de acesso apenas para nível 'master'
* -> Sanitização de todos os inputs
* -> Prepared statements para todas as queries
* -> Proteção contra SQL injection
* -> Validação de parâmetros GET
* 
* Dependências:
* -> Framework Bootstrap 5 para interface
* -> Biblioteca Font Awesome para ícones
* -> Biblioteca Chart.js para visualização de dados
* -> Biblioteca jQuery para interações client-side
* -> Arquivo config.php com configurações do banco
* -> Função auth() para verificação de autenticação
* -> Função trataPost() para sanitização
* 
* Observações importantes:
* -> A atualização dos dados no gráfico só funciona quando o status do pedido for entregue
* -> A página se atualiza automaticamente a cada 5 minutos
* -> Gráficos são responsivos e se adaptam a dispositivos móveis
* -> Filtros são cumulativos (ano + mês + dia)
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2025 BellaVitta
*/ 



/****************************************************************
 * CONFIGURAÇÕES INICIAIS E SEGURANÇA
****************************************************************/

// PUXA AS CONFIGURAÇÕES DE CONEXÃO COM O BANCO DE DADOS
require("assets/config/config.php");

// VERIFICA A AUTENTICAÇÃO DO USUÁRIO VIA TOKEN DE SESSÃO
$usuario = auth($_SESSION["TOKEN"]);

// REDIRECIONA PARA LOGIN SE NÃO AUTENTICADO OU NÃO FOR MASTER
if(!$usuario || $usuario['nivel_acesso'] !== 'master') {
    header("Location: login.php");
    exit; 
}

// ATIVA A EXIBIÇÃO DE ERROS PARA FACILITAR A DEPURAÇÃO
// IMPORTANTE: DESATIVAR EM PRODUÇÃO
error_reporting(E_ALL);
ini_set('display_errors', 1);

// VERIFICA CONEXÃO
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Erro crítico: Conexão com o banco de dados não estabelecida.");
}


// VERIFICA SE EXISTEM FILTROS DE DATA ENVIADOS VIA GET
if(isset($_GET["ano"]) && isset($_GET["mes"]) && isset($_GET["dia"])) {
   
    // TRATA E ASSOCIA AOS FILTROS 
    $anoFiltro = (int)trataPost($_GET['ano']);
    $mesFiltro = (int)trataPost($_GET['mes']);
    $diaFiltro = (int)trataPost($_GET['dia']);
} else {
    // USA DATA ATUAL COMO PADRÃO SE NÃO HOUVER FILTROS
    $anoFiltro = (int)date('Y');
    $mesFiltro = null; // SEM FILTRO DE MÊS
    $diaFiltro = null; // SEM FILTRO DE DIA
}



/***************************************************************
 * CONSTRUÇÃO DA CLAUSULA WHERE PARA AS CONSULTAS SQL
**************************************************************/

// FILTRA APENAS PEDIDOS COM STATUS 'ENTREGUE'
$whereClause = "WHERE status = 'entregue'";

// ARRAY PARA ARMAZENAR PARÂMETROS DA CONSULTA SQL (PREVENÇÃO DE SQL INJECTION)
$params = [];

// ADICIONA FILTRO DE ANO SE ESPECIFICADO
if ($anoFiltro) {
    $whereClause .= " AND YEAR(data_pedido) = :ano";
    $params[':ano'] = $anoFiltro;
}

// ADICIONA FILTRO DE MÊS SE ESPECIFICADO
if ($mesFiltro) {
    $whereClause .= " AND MONTH(data_pedido) = :mes";
    $params[':mes'] = $mesFiltro;
}

// ADICIONA FILTRO DE DIA SE ESPECIFICADO
if ($diaFiltro) {
    $whereClause .= " AND DAY(data_pedido) = :dia";
    $params[':dia'] = $diaFiltro;
}

/***************************************************************
 * CONSULTA DE FORMAS DE PAGAMENTO
 **************************************************************/
try {
    // PREPARA E EXECUTA A CONSULTA
    $stmtPagamentos = $pdo->prepare("
    SELECT 
        pagamento as forma_pagamento,
        SUM(CAST(REPLACE(REPLACE(valor_total, 'R$ ', ''), ',', '.') AS DECIMAL(10,2))) as total 
    FROM pedidos 
    $whereClause
    GROUP BY pagamento
    ORDER BY total DESC
    ");
    
    $stmtPagamentos->execute($params);
    $dadosPagamentos = $stmtPagamentos->fetchAll(PDO::FETCH_ASSOC);
    
    // INICIALIZA ARRAYS PARA O GRÁFICO
    $labelsPagamentos = []; // RÓTULOS (NOMES DOS MÉTODOS)
    $dataPagamentos = [];   // VALORES (TOTAIS)
    
    // PALETA DE CORES PARA O GRÁFICO DE PAGAMENTOS
    $colorsPagamentos = [
        '#FFD700', '#FFC300', '#F4D03F', '#F9E076', // TONS DE DOURADO
        '#FF6B6B', '#FF8C42', '#E74C3C', '#EB984E', // TONS DE VERMELHO/LARANJA
        '#58D68D', '#52BE80', '#7DCEA0',           // TONS DE VERDE
        '#D35400', '#A04000', '#BA4A00',           // TONS DE MARROM
        '#9B59B6', '#8E44AD', '#BB8FCE'            // TONS DE ROXO
    ];
    
    // POPULA OS ARRAYS COM OS DADOS DA CONSULTA
    foreach ($dadosPagamentos as $index => $dado) {
        $labelsPagamentos[] = $dado['forma_pagamento'];
        $dataPagamentos[] = (float)$dado['total'];
    }
    
} catch (PDOException $e) {
    // REGISTRA ERRO E PREPARA MENSAGEM PARA O USUÁRIO
    $_SESSION['mensagem'] = "Erro ao buscar dados de pagamentos: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "danger";
}

/***************************************************************
 * CONSULTA DE SABORES DOS 10 MAIS VENDIDOS
 **************************************************************/

try {
    // PREPARA E EXECUTA A CONSULTA
    $sql = $pdo->prepare("
        SELECT 
            SUBSTRING_INDEX(SUBSTRING_INDEX(pedido, '(', 1), 'x ', -1) as sabor,
            SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(pedido, 'x ', 1), ' ', -1)) as quantidade,
            SUM(valor_total) as total
        FROM pedidos
        $whereClause
        GROUP BY sabor
        ORDER BY quantidade DESC
        LIMIT 10
    ");

    $sql->execute($params);
    $dadosSabores = $sql->fetchAll(PDO::FETCH_ASSOC);
    
    // INICIALIZA ARRAYS PARA O GRÁFICO
    $labelsSabores = []; // NOMES DOS SABORES
    $dataSabores = [];   // QUANTIDADES VENDIDAS

    // PALETA DE CORES PARA O GRÁFICO DE SABORES
    $colorsSabores = [
        '#FFD700', '#F4D03F', '#F9E076',         // TONS DE DOURADO
        '#E74C3C', '#FF6B6B', '#C0392B',         // TONS DE VERMELHO
        '#FF8C42', '#EB984E', '#D35400',         // TONS DE LARANJA
        '#58D68D', '#52BE80', '#27AE60',         // TONS DE VERDE
        '#A04000', '#784212', '#BA4A00',         // TONS DE MARROM
        '#9B59B6', '#8E44AD', '#6C3483'          // TONS DE ROXO
    ];    
    

    // POPULA OS ARRAYS COM OS DADOS DA CONSULTA
    foreach ($dadosSabores as $index => $dado) {
        $labelsSabores[] = trim($dado['sabor']);
        $dataSabores[] = (int)$dado['quantidade'];
    }
    
    
} catch (PDOException $e) {
    
    // REGISTRA ERRO E PREPARA MENSAGEM PARA O USUÁRIO
    $_SESSION['mensagem'] = "Erro ao buscar dados de sabores: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "danger";
}

/***************************************************************
 * CONSULTA DE TOTAL VENDIDO NO PERÍODO
 **************************************************************/
try {
    $sql = $pdo->prepare("
        SELECT SUM(CAST(REPLACE(REPLACE(valor_total, 'R$ ', ''), ',', '.') AS DECIMAL(10,2))) as total_vendido
        FROM pedidos
        $whereClause
    ");
    $sql->execute($params);
    $totalVendido = $sql->fetch(PDO::FETCH_ASSOC)['total_vendido'];
    
    // DEFINE VALOR PADRÃO CASO NÃO HAJA VENDAS
    if ($totalVendido === null) {
        $totalVendido = 0.00;
    }
    
} catch (PDOException $e) {
    // REGISTRA ERRO E PREPARA MENSAGEM PARA O USUÁRIO
    $_SESSION['mensagem'] = "Erro ao buscar total vendido: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "danger";
}

/***************************************************************
 * CONSULTA DE QUANTIDADE TOTAL DE ITENS VENDIDOS NO PERÍODO
 **************************************************************/
try {
    $sql = $pdo->prepare("
        SELECT SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(pedido, 'x ', 1), ' ', -1)) as total_itens
        FROM pedidos
        $whereClause
    ");
    $sql->execute($params);
    $totalItens = $sql->fetch(PDO::FETCH_ASSOC)['total_itens'];
    
    // DEFINE VALOR PADRÃO CASO NÃO HAJA ITENS
    if ($totalItens === null) {
        $totalItens = 0;
    }
    
} catch (PDOException $e) {
    // REGISTRA ERRO E PREPARA MENSAGEM PARA O USUÁRIO
    $_SESSION['mensagem'] = "Erro ao buscar total de itens: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "danger";
}



/***************************************************************
 * CONSULTAS PARA GRÁFICOS DE VENDAS POR MES OU DIA
 **************************************************************/

// INICIALIZA VARIÁVEIS PARA OS GRÁFICOS
$labelsMeses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
$dataProdutosMes = array_fill(0, 12, 0); // INICIALIZA COM ZEROS
$labelsDias = [];
$dataProdutosDia = [];
$diaTotal = 0;



/***************************************************************
 * CONSULTA DE VENDAS POR MÊS (GERAL NÃO ESPECIFICO)
 **************************************************************/
if (!$mesFiltro) {
    try {
        $sql = $pdo->prepare("
            SELECT 
                MONTH(data_pedido) as mes_numero,
                DATE_FORMAT(data_pedido, '%M') as mes_nome,
                SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(pedido, 'x ', 1), ' ', -1)) as total_itens
            FROM pedidos
            WHERE status = 'entregue' AND YEAR(data_pedido) = :ano
            GROUP BY mes_numero, mes_nome
            ORDER BY mes_numero ASC
        ");
        $sql->execute([':ano' => $anoFiltro]);
        $dadosProdutosMes = $sql->fetchAll(PDO::FETCH_ASSOC);
        
        // MAPEIA OS RESULTADOS PARA O ARRAY DE DADOS
        foreach ($dadosProdutosMes as $dado) {
            $mesIndex = $dado['mes_numero'] - 1; // AJUSTE PARA ÍNDICE 0-BASED
            $dataProdutosMes[$mesIndex] = (int)$dado['total_itens'];
        }
        
    } catch (PDOException $e) {
        
        // REGISTRA ERRO E PREPARA MENSAGEM PARA O USUÁRIO
        $_SESSION['mensagem'] = "Erro ao buscar dados de produtos por mês: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
}

/***************************************************************
 * CONSULTA DE VENDAS POR DIA QUANDO HÁ FILTRO DE MÊS APLICADO JUNTO
 **************************************************************/

if ($mesFiltro) {
    try {
        // CALCULA O NÚMERO DE DIAS NO MÊS ESPECIFICADO
        $numDias = cal_days_in_month(CAL_GREGORIAN, $mesFiltro, $anoFiltro);
        $labelsDias = range(1, $numDias); // CRIA ARRAY COM OS DIAS
        $dataProdutosDia = array_fill(0, $numDias, 0); // INICIALIZA COM ZEROS
        
        // PREPARA E EXECUTA A CONSULTA
        $sql = $pdo->prepare("
            SELECT 
                DAY(data_pedido) as dia,
                SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(pedido, 'x ', 1), ' ', -1)) as total_itens
            FROM pedidos
            WHERE status = 'entregue' AND YEAR(data_pedido) = :ano AND MONTH(data_pedido) = :mes
            GROUP BY dia
            ORDER BY dia ASC
        ");
        $sql->execute([':ano' => $anoFiltro, ':mes' => $mesFiltro]);
        $dadosProdutosDiaQuery = $sql->fetchAll(PDO::FETCH_ASSOC);
        
        // MAPEIA OS RESULTADOS PARA O ARRAY DE DADOS
        foreach ($dadosProdutosDiaQuery as $dado) {
            $diaIndex = $dado['dia'] - 1; // AJUSTE PARA ÍNDICE 0-BASED
            $dataProdutosDia[$diaIndex] = (int)$dado['total_itens'];
        }
        
        // CALCULA TOTAL PARA UM DIA ESPECÍFICO (SE FILTRADO)
        if ($diaFiltro) {
            $diaTotal = $dataProdutosDia[$diaFiltro - 1] ?? 0; // USA OPERADOR NULL COALESCING
        }
        
    } catch (PDOException $e) {
        // REGISTRA ERRO E PREPARA MENSAGEM PARA O USUÁRIO
        $_SESSION['mensagem'] = "Erro ao buscar dados de produtos por dia: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <!-- META-TAGS PARA CONFIGURAÇÕES DO SITE (COMPATIBILIDADE ,FORMATAÇÃO DO ESCOPO , ETC) -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- TÍTULO DA PAGINA -->
    <title>Financeiro</title>

    <!-- FAVICON -->
    <link rel="icon" href="assets\img\favicon\favicon.ico" type="image/x-icon">

    <!-- REFERÊNCIA DO BOOTSTRAP CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- REFERÊNCIA DO FONT-AWESOME(BIBLIOTECA PARA ICONES) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- REFERÊNCIA CSS TEXNERO -->
    <link href="assets/css/painel_admin.css" rel="stylesheet">

    <!-- REFERÊNCIA DA BIBLITOECA CHART.JS PARA CRIAÇÃO DOS GARFICOS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
    /* ESITLIZAÇÃO DOS BOXES DE FILTROS  */
    .chart-container {
        position: relative;
        height: 300px;
        margin-bottom: 30px;
    }

    .card-header {
        background-color: #343a40;
        color: white;
    }

    .total-box {
        background: #333;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
        text-align: center;
    }

    .total-box h5 {
        color: white;
    }

    .total-box .value {
        font-size: 24px;
        font-weight: bold;
        color: white;
    }

    .filter-container {
        background-color: #343a40;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        color: white;
    }

    .filter-container label {
        color: white;
    }

    .filter-container select,
    .filter-container button {
        margin-right: 10px;
    }
    </style>
</head>

<body>

    <div class="wrapper">


       <!-- ==============================================
         SEÇÃO OFFANCAVAS(MENU LATERAL)
        =============================================== -->

        <nav class="navbar navbar-dark bg-dark d-lg-none fixed-top">
            <div class="container-fluid">
                <button class="btn d-lg-none position-fixed" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas"
                    style="left: 10px; top: 10px; z-index: 100; background-color: #FFD700; color: black;">
                    <i class="fas fa-bars"></i>
                </button>
                <!-- TÍTULO PRÓXIMO DO BOTÃO -->
                <span class="navbar-brand ms-auto me-auto text-warning">Pizzaria Admin</span>
            </div>
        </nav>

        

        <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebarOffcanvas"
            aria-labelledby="sidebarOffcanvasLabel" style="width: 250px;">

            <!-- TÍTULO DO OFFCANVAS -->
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Pizzaria Admin</h5>
            </div>

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


                        <!-- PRODUTOS -->
                        <li class="nav-item">
                            <a href="admin_produtos.php" class="nav-link">
                                <i class="fas fa-pizza-slice me-2"></i>
                                <span>Produtos</span>
                            </a>
                        </li>


                        <!-- FINANCEIRO -->
                        <li class="nav-item">
                            <a href="admin_financeiro.php" class="nav-link active">
                                <i class="fas fa-dollar-sign me-2"></i>
                                <span>Financeiro</span>
                            </a>
                        </li>



                        <!-- BOTÃO DE SAIR -->
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


        <!--SEÇÃO PRINCIPAL-->
        <div class="content-wrapper">
            <section class="content">


                <div class="container-fluid">

                    <!-- ==============================================
                    SEÇÃO DE EXIBIÇÃO DAS MENSAGENS DE SUCESSO E ERRO
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

                    <div class="filter-container">
                        <form method="get" action="" class="row">


                            <!--FILTRO DE ANO-->
                            <div class="col-md-3 mb-3">
                                <label for="ano" class="form-label">Ano:</label>
                                <select class="form-select" id="ano" name="ano">
                                    <?php 
                                    $anoAtual = date('Y');
                                    for ($i = $anoAtual; $i >= $anoAtual - 5; $i--) {
                                        $selected = ($i == $anoFiltro) ? 'selected' : '';
                                        echo "<option value='$i' $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>



                            <!--FILTRO DE MÊS-->
                            <div class="col-md-3 mb-3">
                                <label for="mes" class="form-label">Mês:</label>
                                <select class="form-select" id="mes" name="mes">
                                    <option value="">Todos os meses</option>
                                    <?php 
                                    $meses = [
                                        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 
                                        4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 
                                        7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 
                                        10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                    ];
                                    foreach ($meses as $num => $nome) {
                                        $selected = ($num == $mesFiltro) ? 'selected' : '';
                                        echo "<option value='$num' $selected>$nome</option>";
                                    }
                                    ?>
                                </select>
                            </div>



                            <!--FILTRO DE DIAS-->
                            <div class="col-md-3 mb-3">
                                <label for="dia" class="form-label ">Dia:</label>
                                <select class="form-select" id="dia" name="dia">
                                    <option value="">Todos os dias</option>
                                    <?php 
                                    $numDias = $mesFiltro ? cal_days_in_month(CAL_GREGORIAN, $mesFiltro, $anoFiltro) : 31;
                                    for ($i = 1; $i <= $numDias; $i++) {
                                        $selected = ($i == $diaFiltro) ? 'selected' : '';
                                        echo "<option value='$i' $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- BOTÕES PARA FILTRAR E LIMPAR FILTRO -->
                            <div class="col-md-3 d-flex align-items-end mb-3">
                                <div class="d-grid gap-2 d-md-flex w-100">
                                    <button type="submit" class="btn btn-primary flex-grow-1">Filtrar</button>
                                    <?php if ($anoFiltro || $mesFiltro || $diaFiltro): ?>
                                    <a href="financeiro.php" class="btn btn-secondary flex-grow-1">Limpar</a>
                                    <?php endif; ?>
                                </div>
                            </div>



                        </form>
                    </div>

                    <!-- ==============================================
                     SEÇÃO DOS FILTROS BOXES (TOTAL VENDIDODS E ITENS VENDIDOS)
                    =============================================== -->

                    <div class="row mb-4">

                        <!-- BOX DO VALOR TOTAL VENDIDO-->
                        <div class="col-md-6">
                            <div class="total-box">

                                <!-- TÍTULO DO BOX -->
                                <h5>Total Vendido</h5>

                                <!-- AREA ONDE SERÁ ATUALIZADO O VALOR DINAMICAMENTE DO QUE FOI VENDIDO -->
                                <div class="value">
                                    R$ <?= number_format($totalVendido, 2, ',', '.') ?>
                                </div>

                                <!-- DESCRIÇÃO -->
                                <small>(apenas de pedidos entregues)</small>

                            </div>
                        </div>

                        <!-- BOX DO TOTAL DE ITENS VENDIDOS-->
                        <div class="col-md-6">
                            <div class="total-box">

                                <!-- TÍTULO DO BOX -->
                                <h5>Total de Itens Vendidos</h5>

                                <!-- AREA ONDE SERÁ ATUALIZADO O VALOR DINAMICAMENTE QTD VENDIDA -->
                                <div class="value">
                                    <?= number_format($totalItens, 0, ',', '.') ?> un.
                                </div>

                                <!-- DESCRIÇÃO -->
                                <small>(apenas de pedidos entregues)</small>


                            </div>
                        </div>


                    </div>


                    <!-- ==============================================
                     SEÇÃO DE EXIBIÇÃO DOS GRÁFICOS( PAGAMENTO , SABORES E DE TEMPO)
                    =============================================== -->

                    <div class="row">

                        <!--GRÁFICO FORMAS DE PAGAMENTO-->
                        <div class="col-md-6">
                            <div class="card">
                                <!-- TÍTULO DO GRÁFICO -->
                                <div class="card-header">
                                    <h3 class="card-title mb-0">Vendas por Forma de Pagamento</h3>
                                </div>

                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="pagamentosChart">

                                            <!-- GRÁFICO DE FORMAS DE PAGAMENTO SERÁ INSERIDO AQUI VIA JAVASCRIPT -->

                                        </canvas>
                                    </div>
                                </div>


                            </div>
                        </div>

                        <!--GRÁFICO DE SABORES MAIS VENDIDOS-->
                        <div class="col-md-6">
                            <div class="card">

                                <!-- TITULO DO GRAFICO -->
                                <div class="card-header">
                                    <h3 class="card-title mb-0">Sabores Mais Vendidos</h3>
                                </div>

                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="saboresChart">
                                            <!-- GRÁFICO DE SABORES MAIS VENDIDOS  SERÁ INSERIDO AQUI VIA JAVASCRIPT -->
                                        </canvas>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!--GRÁFICO COMPLETO DE PRODUTOS POR TEMPO (MES, DIA, ANO)-->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <!-- AREA DE TÍTULO DINAMICA(ATUALIZA A QTD DE PRODUTOS PELOS FILTROS) -->
                                    <h3 class="card-title mb-0">
                                        <?php 
                                        if ($diaFiltro) {
                                            echo "Produtos Vendidos no Dia " . str_pad($diaFiltro, 2, '0', STR_PAD_LEFT) . "/" . str_pad($mesFiltro, 2, '0', STR_PAD_LEFT) . "/$anoFiltro";
                                        } elseif ($mesFiltro) {
                                            echo "Produtos Vendidos em " . $meses[$mesFiltro] . " de $anoFiltro";
                                        } else {
                                            echo "Produtos Vendidos em $anoFiltro";
                                        }
                                        ?>
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height: 400px;">
                                        <?php if ($diaFiltro): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> Visualização diária - total de
                                            <?= number_format($diaTotal, 0, ',', '.') ?> itens vendidos neste dia.
                                        </div>
                                        <?php endif; ?>
                                        <canvas id="produtosTempoChart">

                                            <!--GRÁFICO COMPLETO DE PRODUTOS POR TEMPO (MES, DIA, ANO) SERÁ INSERIDO AQUI VIA JAVASCRIPT-->

                                        </canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ==============================================
            RODAPÉ DA PÁGINA
        =============================================== -->
        <footer class="main-footer">
            <strong>© <?= date('Y') ?> Pizzaria Admin</strong>
        </footer>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    /****************************************************************
     * CRIAÇÃO DOS GRÁFICOS COM JAVASCRIPT
     ****************************************************************/

    $(document).ready(function() {

        // GRÁFICO DE FORMAS DE PAGAMENTO
        // INICIALIZAÇÃO DO GRÁFICO DE PAGAMENTOS
        const pagamentosCtx = document.getElementById('pagamentosChart');
        if (pagamentosCtx) {
            try {
                // OBTEM OS DADOS DO PHP VIA JSON
                const pagamentosData = <?= json_encode($dataPagamentos) ?>;
                const pagamentosLabels = <?= json_encode($labelsPagamentos) ?>;

                // VERIFICA SE EXISTEM DADOS PARA EXIBIR
                if (pagamentosData.length > 0 && pagamentosLabels.length > 0) {
                    // CRIA O GRÁFICO DE PIZZA (PIE CHART)
                    const pagamentosChart = new Chart(pagamentosCtx, {
                        type: 'pie',
                        data: {
                            labels: pagamentosLabels,
                            datasets: [{
                                data: pagamentosData,
                                backgroundColor: <?= json_encode($colorsPagamentos) ?>,
                                borderWidth: 1,
                                borderColor: '#343a40'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a +
                                                b, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: R$ ${value.toFixed(2)} (${percentage}%)`;
                                        }
                                    }
                                },
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: 'white',
                                        font: {
                                            size: 12
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    // EXIBE MENSAGEM QUANDO NÃO HÁ DADOS
                    pagamentosCtx.parentNode.innerHTML =
                        '<p class="text-center text-muted">Nenhum dado disponível para o período selecionado</p>';
                }
            } catch (e) {
                // TRATA ERROS NA CRIAÇÃO DO GRÁFICO
                console.error('Erro ao criar gráfico de pagamentos:', e);
            }
        }

        // GRÁFICO DE SABORES MAIS VENDIDOS
        // INICIALIZAÇÃO DO GRÁFICO DE SABORES
        const saboresCtx = document.getElementById('saboresChart');
        if (saboresCtx) {
            try {
                // OBTEM OS DADOS DO PHP VIA JSON
                const saboresData = <?= json_encode($dataSabores) ?>;
                const saboresLabels = <?= json_encode($labelsSabores) ?>;

                // VERIFICA SE EXISTEM DADOS PARA EXIBIR
                if (saboresData.length > 0 && saboresLabels.length > 0) {
                    // CRIA O GRÁFICO DE BARRAS HORIZONTAIS
                    const saboresChart = new Chart(saboresCtx, {
                        type: 'bar',
                        data: {
                            labels: saboresLabels,
                            datasets: [{
                                label: 'Quantidade Vendida',
                                data: saboresData,
                                backgroundColor: <?= json_encode($colorsSabores) ?>,
                                borderColor: '#343a40',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: false // DESATIVA O TÍTULO DO EIXO Y
                                    },
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.1)'
                                    },
                                    ticks: {
                                        color: 'white'
                                    }
                                },
                                x: {
                                    title: {
                                        display: false // TAMBÉM PODE REMOVER O TÍTULO DO EIXO X SE QUISER
                                    },
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: 'white'
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.dataset.label || '';
                                            const value = context.raw || 0;
                                            return `${label}: ${value} un.`;
                                        }
                                    }
                                },
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                } else {
                    // EXIBE MENSAGEM QUANDO NÃO HÁ DADOS
                    saboresCtx.parentNode.innerHTML =
                        '<p class="text-center text-muted">Nenhum dado disponível para o período selecionado</p>';
                }
            } catch (e) {
                // TRATA ERROS NA CRIAÇÃO DO GRÁFICO
                console.error('Erro ao criar gráfico de sabores:', e);
            }
        }

        // GRÁFICO COMPLETO DE PRODUTOS POR TEMPO (MES, DIA, ANO)
        // INICIALIZAÇÃO DO GRÁFICO DE PRODUTOS POR TEMPO
        const produtosTempoCtx = document.getElementById('produtosTempoChart');
        if (produtosTempoCtx) {
            try {
                // FUNÇÃO PARA DETECTAR DISPOSITIVOS MÓVEIS
                const isMobile = () => window.innerWidth <= 768;

                // CONFIGURAÇÕES COMUNS PARA TODOS OS GRÁFICOS DE TEMPO
                const commonOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.raw} unidades`;
                                }
                            }
                        },
                        legend: {
                            display: false
                        }
                    }
                };

                // FUNÇÃO PARA CRIAR/RECRIAR O GRÁFICO
                function createProdutosTempoChart() {
                    // DESTRUIR GRÁFICO EXISTENTE SE HOUVER
                    if (window.produtosTempoChart instanceof Chart) {
                        window.produtosTempoChart.destroy();
                    }

                    <?php if ($diaFiltro): ?>
                    // CONFIGURAÇÕES ESPECÍFICAS PARA O GRÁFICO DIÁRIO
                    const diaOptions = {
                        ...commonOptions,
                        indexAxis: isMobile() ? 'y' : 'x',
                        scales: {
                            [isMobile() ? 'x' : 'y']: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                title: {
                                    display: true,
                                    text: 'Quantidade de Itens',
                                    color: 'white',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                ticks: {
                                    color: 'white'
                                }
                            },
                            [isMobile() ? 'y' : 'x']: {
                                grid: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Dia Selecionado',
                                    color: 'white',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                ticks: {
                                    color: 'white'
                                }
                            }
                        }
                    };

                    // CRIA O GRÁFICO PARA VISUALIZAÇÃO DIÁRIA
                    window.produtosTempoChart = new Chart(produtosTempoCtx, {
                        type: 'bar',
                        data: {
                            labels: ['Total do Dia'],
                            datasets: [{
                                label: 'Quantidade Vendida',
                                data: [<?= $diaTotal ?>],
                                backgroundColor: 'rgba(255, 215, 0, 0.7)',
                                borderColor: '#343a40',
                                borderWidth: 1,
                                borderRadius: 4
                            }]
                        },
                        options: diaOptions
                    });

                    <?php elseif ($mesFiltro): ?>
                    // CONFIGURAÇÕES ESPECÍFICAS PARA O GRÁFICO MENSAL
                    const mesOptions = {
                        ...commonOptions,
                        indexAxis: isMobile() ? 'y' : 'x',
                        scales: {
                            [isMobile() ? 'x' : 'y']: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                title: {
                                    display: true,
                                    text: 'Quantidade de Itens',
                                    color: 'white',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                ticks: {
                                    color: 'white'
                                }
                            },
                            [isMobile() ? 'y' : 'x']: {
                                grid: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Dia do Mês',
                                    color: 'white',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                ticks: {
                                    color: 'white'
                                }
                            }
                        }
                    };

                    // CRIA O GRÁFICO PARA VISUALIZAÇÃO MENSAL
                    window.produtosTempoChart = new Chart(produtosTempoCtx, {
                        type: 'bar',
                        data: {
                            labels: <?= json_encode($labelsDias) ?>,
                            datasets: [{
                                label: 'Quantidade Vendida',
                                data: <?= json_encode($dataProdutosDia) ?>,
                                backgroundColor: 'rgba(255, 215, 0, 0.7)',
                                borderColor: '#343a40',
                                borderWidth: 1,
                                borderRadius: 4
                            }]
                        },
                        options: mesOptions
                    });

                    <?php else: ?>
                    // CONFIGURAÇÕES ESPECÍFICAS PARA O GRÁFICO ANUAL
                    const anoOptions = {
                        ...commonOptions,
                        indexAxis: isMobile() ? 'y' : 'x',
                        scales: {
                            [isMobile() ? 'x' : 'y']: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                title: {
                                    display: true,
                                    text: 'Quantidade de Itens',
                                    color: 'white',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                ticks: {
                                    color: 'white'
                                }
                            },
                            [isMobile() ? 'y' : 'x']: {
                                grid: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Mês',
                                    color: 'white',
                                    font: {
                                        weight: 'bold'
                                    }
                                },
                                ticks: {
                                    color: 'white'
                                }
                            }
                        }
                    };

                    // CRIA O GRÁFICO PARA VISUALIZAÇÃO ANUAL
                    window.produtosTempoChart = new Chart(produtosTempoCtx, {
                        type: 'bar',
                        data: {
                            labels: <?= json_encode($labelsMeses) ?>,
                            datasets: [{
                                label: 'Quantidade Vendida',
                                data: <?= json_encode($dataProdutosMes) ?>,
                                backgroundColor: 'rgba(255, 215, 0, 0.7)',
                                borderColor: '#343a40',
                                borderWidth: 1,
                                borderRadius: 4
                            }]
                        },
                        options: anoOptions
                    });
                    <?php endif; ?>
                }

                // CRIA O GRÁFICO INICIAL
                createProdutosTempoChart();

                // RECRIA O GRÁFICO QUANDO A TELA É REDIMENSIONADA
                window.addEventListener('resize', createProdutosTempoChart);

                //CASO OCORRA ALGUM ERRO AO CRIAR O GRÁFICO DE TEMPO
            } catch (e) {
                //MOSTRA MENSAGEM DE ERRO PARA O USUÁRIO 
                console.error('Erro ao criar gráfico de produtos por tempo:', e);
                produtosTempoCtx.parentNode.innerHTML =
                    '<p class="text-center text-danger">Erro ao carregar o gráfico</p>';
            }
        }

        // ATUALIZA A PÁGINA A CADA 5 MINUTOS (300.000 MILISEGUNDOS)
        setTimeout(function() {
            location.reload();
        }, 300000);
    });
    </script>
</body>

</html>