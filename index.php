<!-- /**
* Arquivo: index.php
* 
* Descrição: Página inicial da Pizzaria BellaVitta, apresentando a marca, serviços e cardápio.
* 
* Funcionalidades principais:
* -> Apresentação institucional da pizzaria
* -> Exibição do cardápio de pizzas
* -> Exibição do depoimento dos clientes
* -> Exibição das infomações para contato
* 
* Estrutura da página:
* -> Seção de cabeçalho com menu de navegação (navbar) e redirecionamento para a página de login
* -> Seção hero (destaque principal)
* -> Seçao sobre a pizzaria
* -> Seção de serviços/diferenciais
* -> Seção de cardápio de pizzas
* -> Seção de depoimentos de clientes (carousel)
* -> Seção do rodapé com informações de contato
* 
* Dependências:
* -> Framework Bootstrap 5 para layout responsivo
* -> Owl Carousel para sliders e depoimentos
* -> Bibliteoca Font Awesome para ícones
* -> Google Fonts (Poppins) para tipografia
* -> Biblioteca jQuery para interações
* 
* @ Autor Eduardo Torres Do Ó
* @ Direitos Reservados - 2025 BellaVitta
*/ -->


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=7">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BellaVitta</title>
    <!-- META-TAGS PARA CONFIGURAÇÕES DO SITE (COMPATIBILIDADE , DESCRIÇÃO DA PAGINA , PALAVRAS CHAVES DE PESQUISA) -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=7" />
    <meta name="keywords"
        content="Pizzaria , pizza de calebresa , pizza mussarela, Pizzaria em Niterói , Melhor pizzaria de Itaipu , Pizzaria Italina em Niterói , Pizza portuguesa , Pizza catupyry" />
    <meta name="description"
        content="Bem-vindo à Pizzaria Bella Vita, onde cada fatia é uma celebração de sabor e tradição! Nossa missão é oferecer a melhor experiência em pizzas artesanais, combinando ingredientes frescos e de alta qualidade com técnicas tradicionais italianas." />

    <!-- OPEN GRAPH META TAGS ( CONFIGURAÇAO DE COMPARTILHAMENTO LINKS NA WEB , MELHORA NO SEO) -->
    <meta property="og:title" content="Pizzaria Bella Vita" />
    <meta property="og:image" content="https://www.bellavitta.online/assets\img\hero\fundopizza.jpg" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta property="og:url" content="https://www.bellavitta.online" />
    <meta property="og:type" content="website" />
    <meta name="author" content="Eduardo Torres" />
    <title>BellaVitta</title>

    <!-- LINKS DE REFERÊNCIA PARA O PROJETO , INDICANDO TIPOGRAFIA USADA NA PAGINA , BIBLIOTECAS , CSS E FRAMEWORKS -->

    <!-- GOOGLE FONT (TIPOGRAFIA USADA NA PAGINA)-->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet" />

    <!--FONT AWESOME (ICONES FONT AWESOME USADOS NA PAGINA)-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />

    <!-- OWL CAROUSEL ( BIBLIOTECA DE EFEITO VISUAL PARA A SESSAO DE FEEDBACKS)-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css"
        rel="stylesheet" />

    <!-- BOOTSTRAP CSS E BOOTSTRAP ICONS( REFERENCIA PARA USAR O FRAMEWORK BOOTSTRAP E OS ICONES) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <!-- CSS  ( FOLHA DE ESTILO PARA CUSTOMIZACÃO VISUAL USADA NA PAGINA) -->
    <link href="assets/css/style.css" rel="stylesheet" />

</head>

<body>

    <div class="container-fluid position-relative p-0" id="menu">
        <!-- =========================
        SEÇÃO MENU DE NAVEGAÇÃO (NABAR)
        ============================== -->
        <nav class="navbar navbar-expand-lg px-4 px-lg-5 py-3 py-lg-0">

            <a href="#" class="navbar-brand p-0">
                <h1 class="m-0 fs-2 titulo text-warning">
                    <img src="assets/img/logo/logotipo.png" alt="BellaVitta" class="brand-logo" />
                    BellaVitta
                </h1>
            </a>

            <!-------- -------------
                BOTAO TOGGLER MOBILE
            ----------------------->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-------- -------------
             LINKS DE NAVEGAÇÃO DO NAVBAR
            ----------------------->
            <div class="collapse navbar-collapse justify-content-center text-center" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    <a href="#home" class="nav-link active">Home</a>
                    <a href="#sobre" class="nav-link">Sobre</a>
                    <a href="#cardapio" class="nav-link">Cardápio</a>
                    <a href="#contatos" class="nav-link">Contato</a>
                </div>

                <!-------- -------------
                    BOTÃO DE LOGIN
                ----------------------->
                <button type="button" class="btn btn-warning rounded-pill py-2 px-4 ms-3 " style="margin-right:15px"
                    onclick="window.location.href='login.php'">
                    Login
                </button>

            </div>

        </nav>

        <!-- =========================
        SEÇÃO HERO
        ============================== -->
        <div class="container-fluid hero-section">

            <div class="container d-flex align-items-center justify-content-center min-vh-100">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-12">
                        <h1 class="display-3 text-warning mb-3 fw-bold text-uppercase">
                            Deixe seu dia mais saboroso!!!
                        </h1>
                        <p class="fs-5 text-white text-uppercase mb-4">
                            Bem-vindo à Pizzaria Bella Vitta onde cada fatia é uma
                            celebração de sabor e tradição!
                        </p>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- =========================
        SEÇÃO SOBRE 
    ============================== -->
    <div class="container-fluid py-5 pt-5" style="background-color: #040304e8" id="sobre">

        <div class="container">

            <div class="row g-5 align-items-center">

                <div class="col-lg-6">
                    <!----------------------
                     IMAGENS
                    ----------------------->
                    <div class="row g-3 d-flex align-items-stretch">


                        <div class="col-6">
                            <img class="img-fluid rounded w-100 h-100 object-fit-cover"
                                src="assets/img/sobre/about-1.jpg" />
                        </div>


                        <div class="col-6">
                            <img class="img-fluid rounded w-100 h-100 object-fit-cover"
                                src="assets/img/sobre/sobre2.jpg" />
                        </div>


                        <div class="col-6">
                            <img class="img-fluid rounded w-100 h-100 object-fit-cover"
                                src="assets/img/sobre/sobre1.jpg" />
                        </div>


                        <div class="col-6">
                            <img class="img-fluid rounded w-100 h-100 object-fit-cover"
                                src=" assets/img/sobre/cozinheiros.jpg " />
                        </div>


                    </div>

                </div>

                <!----------------------
                    CONTEÚDO DE TEXTO
                ----------------------->
                <div class="col-lg-6">

                    <h1 class="mb-4 text-white">
                        Bem-vindo ao
                        <img style="height: 55px" src="assets/img/logo/logotipo.png" alt="" /><span
                            class="text-warning"> BellaVitta</span>
                    </h1>

                    <p style="font-size: 19.4px" class="mb-4 text-white">
                        Nossa pizzaria é um verdadeiro paraíso para os apaixonados por
                        sabores autênticos, onde cada pizza é criada com paixão e cuidado.
                        Utilizamos ingredientes frescos e selecionados, sempre valorizando
                        a qualidade e o sabor artesanal.
                    </p>

                    <p style="font-size: 19.4px" class="mb-4 text-white">
                        Inspirados nas tradições das melhores pizzarias italianas,
                        acreditamos que cada fatia conta uma história de dedicação e amor
                        pela culinária. Venha experimentar e descobrir o que torna nossas
                        pizzas tão especiais! 🍕🔥
                    </p>


                    <div class="row g-4 mb-4">

                        <div class="col-sm-6">
                            <div class="d-flex align-items-center border-start border-5 border-warning px-3">
                                <h1 class="flex-shrink-0 display-5 text-warning mb-0" data-toggle="counter-up">
                                    15
                                </h1>
                                <div class="ps-4">
                                    <p class="mb-0 text-warning">Anos de</p>
                                    <h6 class="text-uppercase mb-0 text-warning">
                                        Experiência
                                    </h6>
                                </div>
                            </div>
                        </div>


                        <div class="col-sm-6">
                            <div class="d-flex align-items-center border-start border-5 border-warning px-3">
                                <h1 class="flex-shrink-0 display-5 text-warning mb-0" data-toggle="counter-up">
                                    50
                                </h1>
                                <div class="ps-4">
                                    <p class="mb-0 text-warning">Chefes</p>
                                    <h6 class="text-uppercase mb-0 text-warning">Renomados</h6>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- BOTÃO DE SAIBA MAIS -->
                    <a class="btn btn-warning py-3 px-5 mt-2" href="">Saiba Mais</a>

                </div>

            </div>

        </div>

    </div>

    <!-- ==============================================
        SEÇÃO DE SERVIÇOS
    =============================================== -->
    <div class="container-fluid py-5" id="servicos" style="background-color: #040304e8">

        <div class="container">
            <h1 class="mb-5 text-white text-center"> Por que comer na Bella Vitta ?</h1>

            <div class="row g-4" id="insere_servicos">
                <!----------------------
                    SERVIÇOS INSERIDOS AQUI VIA JAVASCRIPT ( ARQUIVO MAIN.JS)
                ----------------------->
            </div>

        </div>

    </div>


    
    <!-- ==============================================
    SEÇÃO DE CARDÁPIO
    =============================================== -->

    <div class="container-fluid py-5" style="background-color: #040304e8" id="cardapio">
        <div class="container">
            <h1 class="mb-5 text-white text-center">Menu</h1>
            <div class="row g-4" id="pizza-container">
                <!--(PIZZAS) INSERIDAS AQUI VIA JAVASCRIPT (MAIN.JS)-->
            </div>
        </div>
    </div>



    <!-- ==============================================
    SEÇÃO DE FEEDBACKS(TESTEMUNHAS)
    =============================================== -->
    <div class="container-fluid py-5" style="background-color: #040304e8; overflow-x: hidden" id="feedbacks">
        <div class="container">
            <h1 class="mb-5 text-white text-center">Feedbacks</h1>

            <div class="owl-carousel testimonial-carousel position-relative w-100" id="testemunho">
                <!-- TESTEMUNHAS INSERIDAS AQUI VIA JAVASCRIPT(MAIN.JS)-->
            </div>
        </div>
    </div>

    <!-- ==============================================
    SEÇÃO DO RODAPÉ
    =============================================== -->
    <div class="container-fluid text-white pt-3 px-0 position-relative overlay-top" style="background-color: #040304df"
        id="contatos">

        <div class="row mx-0 pt-5 px-sm-3 px-lg-5 mt-4">

            <!-- CONTATO -->
            <div class="col-lg-4 col-md-6 mb-5 text-center text-lg-left">
                <h4 class="text-white text-uppercase mb-4" style="letter-spacing: 3px">
                    Contato
                </h4>
                <p><i class="fa fa-map-marker-alt mr-2"></i> Centro Niterói</p>
                <p><i class="fa-brands fa-whatsapp mr-2"></i> 21 995262727</p>
                <p class="m-0">
                    <i class="fa fa-envelope mr-2"></i> BellaVitta@gmail.com
                </p>
            </div>

            <!-- REDES SOCIAIS  -->
            <div class="col-lg-4 col-md-6 mb-5 text-center text-lg-left">
                <h4 class="text-white text-uppercase mb-4" style="letter-spacing: 3px">
                    Redes Sociais
                </h4>
                <p>Nos siga e fique por dentro das novidades!</p>
                <div class="d-flex justify-content-center justify-content-lg-center">
                    <a class="btn btn-lg btn-outline-warning btn-lg-square mr-2" href="#"><i
                            class="fab fa-twitter"></i></a>
                    <a class="btn btn-lg btn-outline-warning btn-lg-square mr-2" href="#"><i
                            class="fab fa-facebook-f"></i></a>
                    <a class="btn btn-lg btn-outline-warning btn-lg-square mr-2" href="#"><i
                            class="fab fa-linkedin-in"></i></a>
                    <a class="btn btn-lg btn-outline-warning btn-lg-square" href="#"><i
                            class="fab fa-instagram"></i></a>
                </div>
            </div>

            <!-- HORÁRIO DE FUNCIONAMENTO  -->
            <div class="col-lg-4 col-md-6 mb-5 text-center text-lg-right">
                <h4 class="text-white text-uppercase mb-4" style="letter-spacing: 3px">
                    Funcionamento
                </h4>
                <div class="d-inline-block text-center text-lg-right">
                    <h6 class="text-white">Segunda - Sexta</h6>
                    <p>19:00 - 22:00</p>
                    <h6 class="text-white text-uppercase">Sábado - Domingo</h6>
                    <p>17:00 - 23:00</p>
                </div>
            </div>


        </div>

        <!-- DIREITOS AUTORAIS -->
        <div class="container-fluid text-center text-white border-top mt-4 py-4 px-sm-3 px-md-5"
            style="border-color: rgba(256, 256, 256, 0.1) !important">
            <p class="mb-2 text-white">
                Copyright &copy;
                <a class="font-weight-bold text-warning" href="#">BellaVitta</a>.
                Todos os Direitos Reservados.
            </p>
            <p class="m-0 text-white">
                Desenvolvido por
                <a class="font-weight-bold text-warning"
                    href="https://www.linkedin.com/in/eduardo-torres-do-%C3%B3-576085385/">Eduardo Torres Do Ó</a>
            </p>
        </div>
    </div>



    <!-- REFERÊNCIA DO BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!--  REFERÊNCIA DO JQUERY -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!--  REFERÊNCIA DA BILIOTECA OWL CAROUSEL JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

    <!-- REFERÊNCIA JS ( MANIPULACAO DO CAROUSEL) -->
    <script src="assets/js/main.js"></script>
    <script src="assets/lib/owlcarousel/owl.carousel.js"></script>




</body>

</html>