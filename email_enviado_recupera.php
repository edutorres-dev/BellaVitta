<!--
Arquivo: email_enviado_recupera.php

Descrição:
Página de confirmação após de envio de e-mail para recuperação de senha, com animação interativa e links rápidos para provedores de e-mail.

Funcionalidades:
-> Exibe animação Lottie para feedback visual amigável
-> Informa o usuário sobre o envio do e-mail de recuperação
-> Fornece links diretos para os principais webmails (Gmail e Outlook)

* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2025 BellaVitta

-->


<!DOCTYPE html>
<html lang="pt">
<head>

    <!-- META-TAGS PARA CONFIGURAÇÕES DO SITE (COMPATIBILIDADE ,FORMATAÇÃO DO ESCOPO , ETC) -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmação de Cadastro</title>
    
    <!-- REFERÊNCIA DO BOOTSTRAP CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
     <!-- FAVICON -->
    <link rel="icon" href="assets\img\favicon\favicon.ico" type="image/x-icon">
    
    <!--REFERÊNCIA DO CSS EXTERNO-->
    <link rel="stylesheet" href="assets/css/form_aut.css">
    
    <!-- ALINHAMENTO DA ANIMAÇÃO  -->
    <style>

        body{
             background: linear-gradient(rgba(27, 26, 26, 0.509),rgba(27, 26, 26, 0.509)), url(./assets/img/hero/fundopizza.jpg) center center / cover no-repeat;
        }
       
        .animation-container {
            display: flex;
            justify-content: center;
        }
       
    </style>

</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">

    <div class="container text-center" >
        <!-- ANIMAÇÃO -->
        <div class="animation-container">
            <script
            src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs"
            type="module"
            ></script>

            <dotlottie-player
            src="https://lottie.host/fd9b5bcd-2451-4f43-bd00-dd9e98db2801/ejnotc2rwQ.lottie"
            background="transparent"
            speed="1"
            style="width: 300px; height: 300px"
            loop
            autoplay
            ></dotlottie-player>
        </div>

        <!-- MENSAGEM -->
        <h1 class="fw-bold text-white">Recuperação de Senha</h1>
        <p class="text-white"  style="font-size: 19px;">Enviamos um e-mail de recuperação de senha ao e-mail cadastrado , verifique sua caixa de entrada .</p>
        <p  class="text-white" style="font-size: 19px;">Caso não encontre, confira sua caixa de spam!</p>

        <!-- BOTÕES -->
        <br><a  class="btn-aut" style=" padding: 15px; border-radius: 5px; text-decoration: none;" href="https://mail.google.com"> Ir para o Gmail</a>
        <a class="btn-aut" style=" padding: 15px; border-radius: 5px; text-decoration: none; " href="https://login.live.com/login.srf?wa=wsignin1.0&rpsnv=168&ct=1735245085&rver=7.5.2211.0&wp=MBI_SSL&wreply=https%3a%2f%2foutlook.live.com%2fowa%2f%3fnlp%3d1%26cobrandid%3dab0455a0-8d03-46b9-b18b-df2f57b9e44c%26culture%3dpt-br%26country%3dbr%26RpsCsrfState%3d0f3d39b4-d5d2-a6fd-e032-0a8cc59e4f9e&id=292841&aadredir=1&CBCXT=out&lw=1&fl=dob%2cflname%2cwld&cobrandid=ab0455a0-8d03-46b9-b18b-df2f57b9e44c"> Ir para o Outlook</a>
        <br><br><br><br><br><br>
        
    </div>
    

    <!-- REFERÊNCIA DO BOOTSTRAP DO JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
