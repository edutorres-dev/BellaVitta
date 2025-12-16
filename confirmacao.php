<?php

/*
* Arquivo: confirmacao.php
* 
* Descrição:
* Processa a confirmação de cadastro de usuários através de um código único enviado por e-mail.
* Atualiza o status do usuário no banco de dados para "confirmado" quando o código é válido.
* 
* Funcionalidades :
* -> Validação do código de confirmação recebido via URL(GET)
* -> Verificação da existência do usuário no banco de dados
* -> Atualização do status do usuário para "confirmado"
* -> Redirecionamento com feedback para o usuário
* 
* Fluxo de operação:
* 1) Recebe código de confirmação via parâmetro GET
* 2) Valida e sanitiza o código recebido
* 3) Busca usuário correspondente no banco de dados
* 4) Se encontrado, atualiza status para "confirmado"
* 5) Redireciona para login com mensagem de sucesso
* 6) Se código inválido, exibe mensagem de erro
* 
* Segurança:
* -> Sanitização do código de confirmação
* -> Uso de prepared statements para prevenir SQL injection
* -> Verificação da existência do usuário antes de atualizar
* -> Redirecionamento seguro após confirmação
*
* Dependências:
* -> Arquivo config.php com configurações do banco e conexão PDO
* -> Função trataPost() para sanitização de inputs
* 
* 
* Observações importantes:
* -> O código tem validade indeterminada (não expira)
* -> Cada código é único por usuário
* -> O status inicial do usuário é "novo"
* 
*
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2025 BellaVitta
* 
*/


/****************************************************************
 * CONFIGURAÇÕES INICIAIS
****************************************************************/

// PUXA AS INFORMACÕES DO BANCO
require("assets/config/config.php");

/****************************************************************
 * PROCESSAMENTO DE CONFIRMAÇÃO DE CADASTRO
****************************************************************/

// SE TEM O CÓDIGO DE CONFIRMAÇÃO, PEGA ELE VIA GET
if(isset($_GET["cod_confirm"]) && !empty($_GET["cod_confirm"])){

    // TRATA ESSE CÓDIGO EVITANDO SQL INJECTION
    $cod= trataPost($_GET["cod_confirm"]);

    //VERIFICA SE O USUÁRIO TEM ESSE CÓDIGO
    $sql = $pdo->prepare("SELECT * FROM usuarios WHERE codigo_confirmacao=? LIMIT 1");
    $sql->execute(array($cod));
    $usuario = $sql->fetch(PDO::FETCH_ASSOC);

    // SE EXISTIR ALGUM USUÁRIO COM O CÓDIGO
    if($usuario){

        //ATUALIZA O STATUS DO USUÁRIO PARA CONFIRMADO NO SISTEMA
        $status = "confirmado";
        $sql= $pdo->prepare("UPDATE usuarios SET status=? WHERE codigo_confirmacao=? ");

        // SE CONSEGIU ATUALIZAR O STATUS DO USUÁRIO
        if($sql->execute(array($status,$cod))){

            // REDIRECIONA O USUARIO PARA O LOGIN
            header("location: login.php?result=ok");

        }

        // SE O USUÁRIO NÃO ESTIVER COM O CÓDIGO
    }else{ 

        // MOSTRA A MENSAGEM DE ERRO AO USUÁRIO
       echo "<h1>Código de confirmação inválido!</h1>";

    }

}



?>