<?php
/*
* Arquivo: restrita.php
* 
* Descrição:
* Área restrita do sistema após autenticação, contendo todas as funcionalidades para clientes:
* - Visualização de cardápio
* - Realização de pedidos
* - Gerenciamento de conta
* 
* Funcionalidades :
* -> Autenticação e controle de acesso via token de sessão
* -> Gerenciamento de perfil do usuário (alteração de senha, exclusão de conta)
* -> Sacola de pedidos
* -> Sistema completo de pedidos com:
*    - Seleção de produtos
*    - Escolha de tamanhos
*    - Cálculo de valores
*    - Finalização com dados de entrega

* Fluxo de operação:
* 1) Inicialização e verificação de segurança:
*    -> Carrega configurações do banco de dados (config.php)
*    -> Inicializa biblioteca PHPMailer para envio de emails
*    -> Verifica autenticação do usuário via função auth() com token de sessão
*    -> Redireciona usuários não autenticados para login.php
*    -> Redireciona usuários com nível 'master' para painel administrativo
* 
* 2) Processamento de ações do usuário (em paralelo, com base nos parâmetros POST):
*    A) Redefinição de email:
*       -> Detecta POST com parâmetro "novo_email"
*       -> Validação dos campos e tratamento de erros com a função trataPost()
*       -> Verifica unicidade do email no banco
*       -> Gera novo token e código de confirmação
*       -> Atualiza dados no banco
*       -> Em modo produção: envia email de confirmação via PHPMailer
*       -> Em modo local: redireciona para logout
* 
*    B) Redefinição de telefone:
*       -> Detecta POST com parâmetro "novo_tel"
*       -> Valida preenchimento do campo , formato e tratamento de entrada
*       -> Atualiza número no banco
*       -> Atualiza sessão e redireciona para logout
* 
*    C) Redefinição de senha:
*       -> Detecta POST com parâmetros "senha" e "repete_senha"
*       -> Valida preenchimento dos campos e trata as entradas
*       -> Criptografa a senha com SHA1 e atualiza no banco
*       -> Atualiza sessão e redireciona para logout
* 
*    D) Exclusão de conta:
*       -> Detecta POST com parâmetro "senha_exclui"
*       -> Valida preenchimento e trata os campos
*       -> Confirma senha comparando com hash no banco
*       -> Se válida: exclui usuário e seus pedidos
*       -> Se inválida: exibe mensagem de erro
*       -> Redireciona para logout após exclusão
* 
*    E) Processamento de pedidos:
*       -> Detecta POST com todos os campos obrigatórios
*       -> Valida preenchimento , formatação e trata as entradas
*       -> Insere pedidos no banco com status "confirmado"
*       -> Gera mensagem formatada para WhatsApp
*       -> Cria link direto para WhatsApp com confirmação
*       -> Exibe alerta JavaScript para usuário abrir WhatsApp
* 
* 3) Renderização da interface:
*    -> Estrutura HTML com Bootstrap 5
*    -> Exibe informações do usuário autenticado
*    -> Carrega modais e menu lateral offcanvas para cada funcionalidade
*    -> Mantém valores dos campos em caso de erros de validação
* 
* Segurança:
* -> Verificação de token de sessão em todas as requisições
* -> Prepared statements para todas as operações no banco
* -> Criptografia SHA1 para senhas (observação: considerar migrar para bcrypt)
* -> Sanitização de todos os inputs
* -> Validação server-side
* -> Proteção contra CSRF (embutida no token de sessão)
* 
* Dependências:
* -> Framework Bootstrap 5 
* -> Biblioteca Font Awesome para ícones
* -> Bibliteoca jQuery para interações client-side
* -> Biblioteca Owl Carousel para elementos visuais
* -> Arquivo config.php com configurações do banco
* 
* Observações importantes:
* -> Todo o cardápio é carregado dinamicamente via JavaScript (restrita.js)
* -> O sistema envia confirmação via WhatsApp automaticamente
* -> Modais permanecem abertos em caso de erros de validação
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

//REQUERIMENTO DA BIBLIOTECA PHPMAILER PARA DISPARO DE EMAIL(MODO PRODUÇÃO)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'assets/lib/PHPMailer/src/Exception.php';
require 'assets/lib/PHPMailer/src/PHPMailer.php';
require 'assets/lib/PHPMailer/src/SMTP.php';

// VERIFICANDO AUTENTICAÇÃO
$usuario = auth($_SESSION["TOKEN"]);

// SE O USUARIO NÃO TEM A AUTENTICAÇÃO PELO TOKIEN ARMAZENADO NA SESSION
if (!$usuario) {

    // REDIRECIONA PARA O LOGIN
    header("location: login.php");
}

/* NORMALIZA O ARRAY DO USUÁRIO (EVITA WARNINGS) */
if (!is_array($usuario)) {
    $usuario = [
        'id'      => null,
        'nome'    => '',
        'email'   => '',
        'contato' => '',
        'nivel_acesso' => ''
    ];
}

// SE ESTIVER AUTENTICADO E FOR MASTER
if ($usuario && $usuario['nivel_acesso'] === 'master') {
    // REDIRECIONA PARA O PAINEL ADMIN
    header("Location: admin_pedidos.php");
    exit;
}


/****************************************************************
 * PROCESSAMENTO DA REDEFINIÇÃO DE EMAIL
 ****************************************************************/

// SE EXISTE A POSTAGEM PARA REDEFINIR SENHA
if (isset($_POST["novo_email"])) {

    // ATRIBUI O VALOR DA ID 
    $id = $usuario['id'];
    $nome = $usuario['nome'];

    // SE TIVER AGLUM CAMPO VAZIO
    if (empty($_POST["novo_email"])) {

        // MOSTRA AO USUARIO MENSAGEM DE ERRO
        $erro_geral_email = "Campo obrigatório";


        // CASO CONTRARIO , FOi PREENCHIDO
    } else {

        /********************************************************
         TRATAMENTO DOS DADOS VINDOS DO POST
         ********************************************************/
        $email = trataPost($_POST["novo_email"]);

        /********************************************************
        VALIDAÇÃO DOS CAMPOS OBRIGATÓRIOS
         ********************************************************/
        // CAMPO EMAIL
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro_email = "Formato de e-mail inválido!";
        }

        // VERIFICA SE O EMAIL JA ESTA SENDO USADO POR OUTRO USUARIO
        $sql = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $sql->execute(array($email, $id));
        if ($sql->rowCount() > 0) {
            $erro_email_usado = "Este e-mail já está em uso por outro usuário!";
        }


        //SE NÃO EXISTE ERRO
        if (!isset($erro_email) && !isset($erro_email_usado)) {

            // AS VARIAVEISM(COLUNAS DO BANCO) VOLTAM A TER DEFAULT
            $codigo_confirmacao = sha1(uniqid());
            $token="";
            $codigo_confirmacao= sha1(uniqid());
            $status="novo";
            $data_cadastro= date("Y-m-d");
            $nivel_acesso = "cliente";

            // ATUALIZA O EMIAL PELA ID 
            $sql = $pdo->prepare("UPDATE usuarios SET email = ?, token = ?, codigo_confirmacao = ?, status = ?, data_cadastro = ? WHERE id = ?");

            // SE CONSEGIU ATUALIZAR O EMAIL
            if ($sql->execute(array( $email, $token, $codigo_confirmacao, $status, $data_cadastro, $id))) {

                /********************************************
                 * MODO LOCAL - REDIRECIONA PARA LOGIN
                ********************************************/
                if($modo=="local"){

                    //REDIRECIONA O USUARIO PARA LOGIN
                    header('location: logout.php');
                    exit;
                }

                if($modo=="producao"){

                    // ENIVO DE DE EMAIL PARA O USUARIO:

                    // INSTANCIA  O PHP MAILER
                    $mail = new PHPmailer(true);

                    // TENTA FAZER O ENVIO DO EMAIL
                    try{

                    // REMETENTE
                    $mail->SetFrom('BellaVitta@hotmail.com', "BellaVitta");

                    //DESTINATÁRIO(USUARIO)
                    $mail->addAddress($email,$nome);

                    // CONTEÚDO DO EMAIL COMO HTML
                    $mail ->isHTML(true);
                    
                    // FORMATAÇÃO SEGUINDO O PADRÃO UTF8
                    $mail->CharSet = 'UTF-8'; 

                    //TÍTULO DO EMAIL
                    $mail->Subject = "Confirme seu Cadastro !";

                    // CORPO DO EMAIL
                     $mail->Body = '
                      <div style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 0; margin: 0;">
                          <div style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                              
                              <!-- Imagem de topo -->
                              <img src="'.$site.'/assets/img/hero/fundopizza.jpg" alt="Banner" style="width: 100%; max-height: 250px; object-fit: cover; display: block;">
        
                              <!-- Conteúdo principal -->
                              <div style="padding: 30px; text-align: center; color: #333;">
                                  <h2 style="color: #2c3e50; margin-bottom: 20px;">Bem-vindo à Pizzaria BellaVitta!</h2>
                                  <p style="font-size: 16px; line-height: 1.6; margin-bottom: 25px;">
                                      Que bom ter você conosco! <br>
                                      Para ativar sua conta e começar a aproveitar nossas promoções exclusivas e sabores irresistíveis, confirme seu e-mail clicando no botão abaixo:
                                  </p>
                                  <a href="'.$site.'/confirmacao.php?cod_confirm='.$codigo_confirmacao.'" 
                                    style="display: inline-block; padding: 14px 30px; background-color: rgb(255, 208, 0); color: rgba(25, 25, 25, 0.795); text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold;">
                                      Confirmar E-mail
                                  </a>
                                  <p style="font-size: 14px; color: #888; margin-top: 30px;">
                                      Se você não realizou esse cadastro, pode ignorar esta mensagem.
                                  </p>
                              </div>
        
                              <!-- Rodapé -->
                              <div style="background-color: #f0f0f0; padding: 20px; text-align: center; font-size: 13px; color: #777;">
                                  © '.date("Y").' Pizzaria BellaVitta. Todos os direitos reservados.
                              </div>
                          </div>
                      </div>';
                    

                    //  ENVIA O EMAIL                     
                    $mail->send();

                    // REDIRECIONA PARA OBRIGADO.PHP
                    header('location: obrigado.php');


                    // SE DER ALGUM ERRO NO ENVIO
                    }catch(Exception $e){

                    // MOSTRA A MENSAGEM DE ERRO AO USUÁRIO
                    echo "Houve um problema ao enviar o email de confirmação: {$mail->ErrorInfo}";

                    }


                    
                    


                }



            }

         
                
            
        }
    }

}






/****************************************************************
 * PROCESSAMENTO DA REDEFINIÇÃO DO NÚMERO
 ****************************************************************/

// SE EXISTE A POSTAGEM PARA REDEFINIR SENHA
if (isset($_POST["novo_tel"])) {

    // SE TIVER AGLUM CAMPO VAZIO
    if (empty($_POST["novo_tel"])) {

        // MOSTRA AO USUÁRIO MENSAGEM DE ERRO
        $erro_geral_numero = "Campo obrigatório";


        // CASO CONTRARIO , FORAM PREENHCHIDOS
    } else {

        /********************************************************
         TRATAMENTO DOS DADOS VINDOS DO POST
         ********************************************************/
        $contato = trataPost($_POST["novo_tel"]);

        /********************************************************
        VALIDAÇÃO DOS CAMPOS OBRIGATÓRIOS
         ********************************************************/

        // CAMPO NÚMERO
        if (!preg_match('/^55[1-9]{2}9\d{8}$/', $contato)) {
            $erro_numero = "Formato inválido! Precisa ter 13 dígitos (55 + DDD + 9 dígitos)";
        }



        //SE NÃO EXISTE ERRO
        if (!isset($erro_numero)) {

            try {

                // ATRIBUI O VALOR DA ID 
                $id = $usuario['id'];

                // ATUALIZA O NÚMERO PELA ID 
                $sql = $pdo->prepare("UPDATE usuarios SET contato = ? WHERE id = ?");

                if ($sql->execute(array($contato, $id))) {

                    // ATUALIZA OS DADOS NA SESSÃO
                    $_SESSION['usuario_contato'] = $contato;

                    // REDIRECIONA PARA LOGOUT
                    header("Location: logout.php");
                    exit;
                }




                // SE NAO CONSEGIU ATUALIZAR OS DADOS ( ALTERAR A SENHA)    
            } catch (PDOException $e) {
                $erro_geral_atualiza_numero = "Erro ao atualizar dados: ";
            }
        }
    }
}






/****************************************************************
 * PROCESSAMENTO DA REDEFINIÇÃO DE SENHA
 ****************************************************************/

// SE EXISTE A POSTAGEM PARA REDEFINIR SENHA
if (isset($_POST["senha"]) && isset($_POST["repete_senha"])) {

    // SE TIVER AGLUM CAMPO VAZIO
    if (empty($_POST["senha"]) || empty($_POST["repete_senha"])) {

        // MOSTRA AO USUARIO MENSAGEM DE ERRO
        $erro_geral_senha = "Todos os campos são obrigatorios";


        // CASO CONTRARIO , FORAM PREENHCHIDOS
    } else {

        /********************************************************
         TRATAMENTO DOS DADOS VINDOS DO POST
         ********************************************************/
        $senha = trataPost($_POST["senha"]);
        $senha_cript = sha1($senha); //criptografa a senha
        $repete_senha = trataPost($_POST["repete_senha"]);


        /********************************************************
        VALIDAÇÃO DOS CAMPOS OBRIGATÓRIOS
         ********************************************************/

        //VALIDA SENHA
        if (
            strlen($senha) < 6 ||  // se a senha tem pelo menos 6 caracteres
            !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $senha) ||  // se tem pelo menos um caractere especial
            !preg_match('/[a-zA-Z].*[a-zA-Z]/', $senha) // se tem pelo menos duas letras (maiusculas ou minusculas)
        ) {
            $erro_senha = "A senha deve conter pelo menos 6 caracteres, 1 caractere especial e 2 letras";
        }

        //VERIFICA SE REPETE SENHA = SENHA
        if ($senha !== $repete_senha) {
            $erro_repete_senha = "Senha e repetição de senha diferentes!";
        }


        //SE NÃO EXISTE ERRO
        if (!isset($erro_geral) && !isset($erro_senha) && !isset($erro_repete_senha)) {

            try {

                // ATRIBUI O VALOR DA ID 
                $id = $usuario['id'];

                // ATUALIZA OS DADOS PELA ID 
                $sql = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");

                if ($sql->execute(array($senha_cript, $id))) {

                    // ATUALIZA OS DADOS NA SESSÃO
                    $_SESSION['usuario_senha'] = $senha;

                    // REDIRECIONA PARA LOGOUT
                    header("Location: logout.php");
                    exit;
                }




                // SE NAO CONSEGIU ATUALIZAR OS DADOS ( ALTERAR A SENHA)    
            } catch (PDOException $e) {
                $erro_geral_atualiza_senha = "Erro ao atualizar dados: ";
            }
        }
    }
}


/****************************************************************
 * PROCESSAMENTO DA EXCLUSÃO DE CONTA
 ****************************************************************/

// VERIFICA SE EXISTE A POSTAGEM PARA EXLCUIR CONTA
if (isset($_POST['senha_exclui'])) {

    // SE O CAMPO NÃO FOR VAZIO
    if (!empty($_POST['senha_exclui'])) {

        /********************************************************
             TRATAMENTO DOS DADOS 
         ********************************************************/
        $senha = trataPost($_POST["senha_exclui"]);
        $senha_cript = sha1($senha);
        $id = $usuario['id'];

        // VERIFICA SE A SENHA ESTÁ CORRETA( BATE COM A DO CADASTRO)
        $sql = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND senha = ? LIMIT 1");
        $sql->execute(array($id, $senha_cript));
        $usuarioBanco = $sql->fetch(PDO::FETCH_ASSOC);

        // SE A SENHA INSERIDA É IGUAL A CADASTRADA
        if ($usuarioBanco) {

            // DELETA O USUÁRIO
            $sql_delete_usuario = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $delete_usuario = $sql_delete_usuario->execute(array($id));

            /****************************************************************
             * SE CONSEGUIU DELETAR O USUÁRIO, DELETA OS PEDIDOS
             ****************************************************************/

            if ($delete_usuario) {
                $sql_delete_pedidos = $pdo->prepare("DELETE FROM pedidos WHERE cliente = ?");
                $delete_pedidos = $sql_delete_pedidos->execute(array($usuario['nome']));

                // REDIRECIONA PARA LOGOUT 
                header("Location: logout.php");
                exit();

                // SE NÃO CONSEGIU DELETAR O USUÁRIO 
            } else {

                //MOSTRAR MENSAGEM DE ERRO AO USUÁRIO
                $erro_delete = "Houve um erro ao deletar sua conta!";
            }



            // SE A SENHA NÃO FOR IGUAL A CADASTRADA NO SISTEMA 
        } else {

            // MOSTRA MENSAGEM DE ERRO AO USUÁRIO
            $erro_senha_auth = "Senha incorreta!";
        }



        // SE O CAMPO ESTIVER VAZIO , OU SEJA , O USUÁRIO NÃO INSERIU A SENHA PARA DELETAR A CONTA 
    } else {
        $erro_senha_exclui = "Por favor, informe sua senha";
    }
}



/****************************************************************
 PROCESSAMENTO DE PEDIDOS
****************************************************************/

// SE A POSTAGEM DE FAZER PEDIDO EXISTIR
if(isset($_POST["descricao_pedido"]) && isset ($_POST["valor_total"])&& isset ($_POST["endereco"]) && isset ($_POST["metodo_pagamento"])){

    
    //SE ALGUM CAMPO NÃO FOR PREENCHIDO
    if(empty($_POST["descricao_pedido"]) || empty($_POST["valor_total"]) || empty($_POST["endereco"]) || empty($_POST["metodo_pagamento"])){
        
        // MOSTRA MENSAGEM DE ERRO AO USUÁRIO
        $erro_geral_pedido="Todos os campos são obrigatorios";


        // SE FORAM PREENCHIDOS
    }else{

        /********************************************************
         TRATAMENTO DOS DADOS 
        ********************************************************/        
        $pedido = trataPost($_POST["descricao_pedido"]);
        $valor_total = trataPost($_POST["valor_total"]);
        $endereco = trataPost($_POST["endereco"]);
        $pagamento = trataPost($_POST["metodo_pagamento"]);

       /********************************************************
        VALIDAÇÃO DOS CAMPOS OBRIGATÓRIOS
       ********************************************************/

        // CAMPO DE DESCRIÇÃO DO PEDIDO
        if (!preg_match('/^(\d+[xX]\s+[a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\s]+\((Grande?|Média|Pequena)\)(?:\s*[–-]\s*R\$\s*\d+\.\d{2})?)(?:\s*,\s*\d+[xX]\s+[a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\s]+\((Grande?|Média|Pequena)\)(?:\s*[–-]\s*R\$\s*\d+\.\d{2})?)*$/i', $pedido)) {
            $erro_pedido = "Formato inválido! Use: Qtdx Sabor (Tamanho) [– R$ Valor]. Ex: 2x Calabresa (Grande) – R$ 75.00, 1x Margherita (Média)";
        }

        // CAMPO DE VALOR TOTAL
       if (!preg_match('/^R\$\s\d+\.\d{2}$/', $valor_total)) {
            $erro_valor = "Formato inválido! Use: R$ 999.99";
        }

        // VALIDA ENDERECO
        if (!preg_match('/^[a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\s]+(?:\s*,\s*|\s+)\d+(?:\s*,\s*|\s+)(?:casa\s+\d+|apto?\s+\d+|bloco\s+\d+)(?:(?:\s*,\s*|\s+)(?:casa\s+\d+|apto?\s+\d+|bloco\s+\d+))*(?:\s*,\s*|\s+)(\d{5}-?\d{3})$/i', $endereco)) {
            $erro_endereco = "Formato de endereço inválido! Use: Nome da Rua, Número, [casa X], [apt Y], [bloco Z], CEP";
        }

        //VALIDA PAGAMENTO
        if (!preg_match('/^(Pix|Cartão de Débito|Cartão de Credito|VR)$/i', $pagamento)) {
            $erro_pagamento = "Forma de pagamento inválida! Use: Pix, Cartão de Débito, Cartão de Credito ou VR";
        }


        // SE NÃO EXISTE ERRO
        if(!isset($erro_geral) && !isset($erro_pedido) && !isset($erro_valor) && !isset($erro_endereco) && !isset($erro_pagamento)){

            // ATRIBUI VALORES AS VARIÁVEIS DE PEDIDO
            $data_pedido = date("Y-m-d H:i:s"); 
            $cliente=($usuario['nome']);
            $numero=($usuario['contato']);
            $status="confirmado";


            /********************************************************
             INSERÇÃO DO PEDIDO NO SISTEMA
            ********************************************************/
            $sql= $pdo->prepare("INSERT INTO pedidos VALUES(null,?,?,?,?,?,?,?,?)");

            // SE CONSEGIU INSERIR O PEDIDO
            if($sql->execute(array($cliente,$numero,$pedido,$data_pedido,$endereco, $pagamento , $valor_total,$status))){

                /********************************************************
                 ENVIA A MENSAGEM DO PEDIDO PARA O USUÁRIO NO WHATSAPP
                ********************************************************/
                
                // CORPO DA MENSAGEM + FORMATAÇÃO DA MENSAGEM 
                $mensagem = 
                  "*PEDIDO CONFIRMADO - BELLA VITTA*\n\n" .
                    "Olá, *" . $cliente . "*! Seu pedido está sendo preparado!\n\n" .
                    
                    "*ITENS DO PEDIDO:*\n" .
                    str_replace(", ", "\n", $pedido) . "\n\n" . // Transforma vírgulas em quebras de linha
                    
                    "*Data/Hora:* " . date("d/m/Y à\s H:i", strtotime($data_pedido)) . "\n\n" .
                    "*Endereço:* " . str_replace(", ", " ", $endereco) . "\n\n" . // Alinhamento do endereço
                    "*Pagamento:* " . $pagamento . "\n\n" .
                    "*Total:* " . $valor_total . "\n\n" .
                    
                    "*Tempo estimado:* 40-60 minutos\n" .
                    "(Avisaremos quando sair para entrega!)\n\n" .
                    
                    "Agradecemos sua preferência! \n" .
                    "*Equipe Bella Vitta*"
                ;
                
                // FORMATA A STRING DE TEXTO
                $mensagem_whatsapp = rawurlencode($mensagem);
            
                // GERA O LINK DO WHATSSAP
                $link_whatsapp = "https://wa.me/$numero?text=$mensagem_whatsapp";
            
                // CRIA UM ALERT DE JAVASCRIPT PARA O USUARIO CLICAR E SER REDIRECIONADO PARA O WHATSAPP
                echo "
                <script>
                    if (confirm('Pedido confirmado! Clique em OK para abrir o WhatsApp e receber a confirmação.')) {
                        window.open('$link_whatsapp', '_blank');
                    }
                </script>
                ";

                

                // SE OCORREU ERRO AO FAZER A INSERÇÃO DO PEDIDO NO SISTEMA 
            }else{

                //MENSAGEM DE ERRO AO USUÁRIO
                $erro_realizar_pedido = "Ocorreu um erro ao realizar seu pedido . Tente Novamente!";
            }




        }





    
    }



}





?>



<!DOCTYPE html>
<html lang="pt-br" class="html">

<head>
    <!-- META-TAGS PARA CONFIGURACOES DO SITE (COMPATIBILIDADE , DESCRIÇAO DA PAGINA , PALAVRAS CHAVES DE PESQUISA) -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=7">
    <meta name="keywords"
        content="Pizzaria , pizza de calebresa , pizza mussarela, Pizzaria em Niterói , Melhor pizzaria de Itaipu , Pizzaria Italina em Niterói , Pizza portuguesa , Pizza catupyry">
    <!-- Palavras-chave para SEO -->
    <meta name="description"
        content="Bem-vindo à Pizzaria Bella Vita, onde cada fatia é uma celebração de sabor e tradição! Nossa missão é oferecer a melhor experiência em pizzas artesanais, combinando ingredientes frescos e de alta qualidade com técnicas tradicionais italianas. ">

    <!-- OPEN GRAPH META TAGS ( CONFIGURAÇAO DE COMPARTILHAMENTO LINKS NA WEB , MELHORA NO SEO) -->
    <meta property="og:title" content="Pizzaria Bella Vita">
    <meta property="og:description"
        content="Bem-vindo à Pizzaria Bella Vita, onde cada fatia é uma celebração de sabor e tradição! Nossa missão é oferecer a melhor experiência em pizzas artesanais, combinando ingredientes frescos e de alta qualidade com técnicas tradicionais italianas.">
    <meta property="og:image:secure_url" content="https://www.worldtravel.net.br/img/bg-hero.jpeg">
    <meta property="og:image" content="https://www.bellavitta.online/img/booking.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="https://www.bellavitta.online">
    <meta property="og:type" content="website">
    <meta name="author" content="Eduardo Torres">
    <title>BellaVitta</title>

    <!-- FAVICON -->
    <link rel="icon" href="assets\img\favicon\favicon.ico" type="image/x-icon">



    <!-- LINKS DE REFERÊNCIA PARA O PROJETO , INDICANDO TIPOGRAFIA USADA NA PAGINA , BIBLIOTECAS , CSS E FRAMEWORKS -->

    <!-- GOOGLE FONT (TIPOGRAFIA USADA NA PAGINA)-->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!--FONT AWESOME (ICONES FONT AWESOME USADOS NA PAGINA)-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">

    <!-- OWL CAROUSEL ( BIBLIOTECA DE EFEITO VISUAL PARA A SESSAO DE FEEDBACKS)-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" rel="stylesheet">


    <!-- BOOTSTRAP CSS E BOOTSTRAP ICONS( REFERENCIA PARA USAR O FRAMEWORK BOOTSTRAP E OS ICONES) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS  ( FOLHA DE ESTILO PARA CUSTOMIZACÃO VISUAL USADA NA PAGINA) -->
    <link href="assets/css/restrita.css" rel="stylesheet">



</head>

<body>

    <!-- ==============================================
       MENU DE NAVEGAÇÃO (NAVBAR)
    =============================================== -->
    <div class="container-fluid position-relative p-0" id="menu">
        <nav class="navbar navbar-expand-lg px-4 px-lg-5 py-3 py-lg-0  ">
            <a href="#" class="navbar-brand p-0">
                <h1 class="m-0 fs-2 titulo text-warning">
                    <img src="assets/img/logo/logotipo.png" alt="BellaVitta" class="brand-logo" />
                    BellaVitta
                </h1>
            </a>

            <!-- BOTÕES -->
            <div class="d-flex align-items-center ms-auto gap-3 ">

                <!-- BOTÃO DO USUÁRIO-->
                <button class="btn-user" type="button" data-bs-toggle="modal" data-bs-target="#userMainModal">
                    <i class="bi bi-person-circle"></i>
                </button>

                <!-- BOTÃO DA SACOLA-->
                <button class="btn-user" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#meusPedidosOffcanvas">
                    <i class="bi bi-bag-check"></i>
                    <span id="contadorPedidos" class="bag-badge">0</span>
                </button>

            </div>

        </nav>

        <!-- ==============================================
            SEÇÃO HERO
        =============================================== -->
        <div class="container-fluid bg-primary hero-section">
            <div class="container d-flex align-items-center justify-content-center min-vh-100">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-10">
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

    <!-- ==============================================
        MODAL PRINCIPAL DO USUÁRIO
    =============================================== -->
    <div class="modal fade user-modal" id="userMainModal" tabindex="-1" aria-labelledby="userMainModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-0">

                    <!-- AVATAR E INFORMACOES DE CONTA DO USUARIO -->
                    <div class="user-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($usuario['nome']); ?></h4>
                        <p><?php echo htmlspecialchars($usuario['email']); ?></p>
                    </div>

                    <!-- SUBMENU DE OPÇÕES DO USUARIO  -->
                    <ul class="user-options">

                        <!-- OPÇÃO DE ALTERAR EMAIL -->
                        <li>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#AlterarEmaillModal"
                                data-bs-dismiss="modal">
                                <i class="bi bi-person-fill-gear"></i> Alterar Email
                            </a>
                        </li>
                        
                        <!--OPÇÃO DE MEUS PEDIDOS-->
                        <li>
                         <a href="#" data-bs-toggle="modal" data-bs-target="#MeusPedidosModal"
                                data-bs-dismiss="modal">
                                <i class="bi bi-receipt"></i> Meus Pedidos
                            </a>
                        </li>

                        <!-- OPÇÃO DE ALTERAR TELEFONE -->
                        <li>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#editTelefoneModal"
                                data-bs-dismiss="modal">
                                <i class="bi bi-telephone"></i> Alterar Número
                            </a>
                        </li>

                        <!-- OPÇÃO DE ALTERAR SENHA -->
                        <li>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#AlterarSenhaModal"
                                data-bs-dismiss="modal">
                                <i class="bi bi-shield-lock"></i> Alterar Senha
                            </a>
                        </li>



                        <!-- OPÇÃO DE EXCLUIR CONTA -->
                        <li>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#ModalDeletarConta"
                                data-bs-dismiss="modal">
                                <i class="bi bi-trash-fill"></i> Excluir Conta
                            </a>
                        </li>

                        <!-- OPÇÃO DE SAIR  -->
                        <li>
                            <a href="logout.php" class="logout">
                                <i class="bi bi-box-arrow-right"></i> Sair
                            </a>
                        </li>

                    </ul>

                </div>
            </div>
        </div>
    </div>
    
    <!-- ==============================================
        MODAL MEUS PEDIDOS
    =============================================== -->
    <div class="modal fade user-modal" id="MeusPedidosModal" tabindex="-1" aria-labelledby="MeusPedidosModalLabel" 
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning" id="MeusPedidosModalLabel">
                        <i class="bi bi-receipt me-2"></i>Meus Pedidos
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" 
                            aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <?php
                    // INICIALIZAÇÃO DE VARIÁVEIS
                    $cliente_nome = $usuario['nome'];
                    $data_atual = date('Y-m-d'); // Formato para comparar com o banco de dados
                    
                    // BUSCA OS PEDIDOS DO USUÁRIO (DATA ATUAL)
                    $sql = $pdo->prepare("SELECT * FROM pedidos WHERE cliente = ? AND DATE(data_pedido) = ? ORDER BY data_pedido DESC");
                    $sql->execute(array($cliente_nome, $data_atual));
                    $pedidos = $sql->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($pedidos) > 0): 
                    ?>
                    
                    <div class="pedidos-list">
                        
                        <!--PERCORRE OS PEDIDOS-->
                        <?php foreach ($pedidos as $pedido): 
                            // FORMATAR DATA
                            $data_formatada = date("d/m/Y", strtotime($pedido['data_pedido']));
                            $hora_formatada = date("H:i", strtotime($pedido['data_pedido']));
                            
                            // DETERMINAR COR DO STATUS
                            $status_class = '';
                            switch(strtolower($pedido['status'])) {
                                case 'confirmado':
                                    $status_class = 'bg-warning text-dark';
                                    break;
                                case 'saiu para entrega':
                                    $status_class = 'bg-primary';
                                    break;
                                case 'entregue':
                                    $status_class = 'bg-success';
                                    break;
                                case 'cancelado':
                                    $status_class = 'bg-danger';
                                    break;
                                default:
                                    $status_class = 'bg-secondary';
                            }
                        ?>
                        
                        <div class="pedido-card mb-4 p-3 border-bottom border-warning">
                            <!-- CABEÇALHO DO PEDIDO -->
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start mb-3">
                                <div class="mb-2">
                                    <div class="d-flex flex-wrap align-items-center mb-1">
                                        <!--EXIBE O STATUS DO PEDIDO-->
                                        <span class="badge <?php echo $status_class; ?> me-3 mb-1">
                                            <?php echo ucfirst($pedido['status']); ?>
                                        </span>
                                        <!--EXIBE A DATA DO PEDIDO-->
                                        <span class="text-white-50 small mb-1">
                                            <i class="bi bi-calendar-check me-1"></i>
                                            <?php echo $data_formatada; ?>
                                        </span>
                                        <span class="text-white-50 small ms-sm-3 mb-1">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo $hora_formatada; ?>
                                        </span>
                                    </div>
                                    <!--EXIBE A ID (NUMERO) DO PEDIDO-->
                                    <h6 class="text-warning mb-0">Pedido #<?php echo $pedido['id']; ?></h6>
                                </div>
                            </div>
                            
                            <!-- DETALHES DO PEDIDO -->
                            <div class="detalhes-pedido">
                                <!-- ITENS DO PEDIDO -->
                                <div class="mb-3">
                                    <div class="bg-dark p-3 rounded">
                                        <h6 class="text-white mb-2">
                                            <i class="bi bi-basket me-2"></i>Itens do Pedido
                                        </h6>
                                        <p class="text-white mb-0"><?php echo htmlspecialchars($pedido['pedido']); ?></p>
                                    </div>
                                </div>
                                
                                <!-- FORMA DE PAGAMENTO -->
                                <div class="mb-3">
                                    <div class="bg-dark p-3 rounded">
                                        <h6 class="text-white mb-2">
                                            <i class="bi bi-credit-card me-2"></i>Pagamento
                                        </h6>
                                        <p class="text-white mb-0">
                                            <?php echo htmlspecialchars($pedido['pagamento']); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- ENDEREÇO DE ENTREGA -->
                                <div class="mb-0">
                                    <div class="bg-dark p-3 rounded">
                                        <h6 class="text-white mb-2">
                                            <i class="bi bi-geo-alt me-2"></i>Endereço de Entrega
                                        </h6>
                                        <p class="text-white mb-0 small">
                                            <?php echo htmlspecialchars($pedido['endereco']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php endforeach; ?>
                    </div>
                    
                    <!--CONTA O TOTAL DE PEDIDOS E MOSTRA-->
                    <div class="mt-3 text-center">
                        <p class="text-warning small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            <?php echo count($pedidos); ?> pedido(s) realizado(s) hoje
                        </p>
                    </div>
                    
                    <?php else: ?>
                    
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-bag-x text-warning" style="font-size: 4rem;"></i>
                        </div>
                        <h5 class="text-white mb-3">Nenhum pedido hoje</h5>
                        <p class="text-white-50 mb-4">
                            Você ainda não realizou nenhum pedido hoje. Explore nosso cardápio e faça seu pedido!
                        </p>
                    </div>
                    
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
   

    <!-- ==============================================
        MODAL DE ALTERAR EMAIL
    =============================================== -->
    <div class="modal fade edit-modal" id="AlterarEmaillModal" tabindex="-1" aria-labelledby="AlterarEmailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning" id="AlterarEmailModalLabel">
                        <i class="bi bi-person-fill-gear me-2"></i>Alterar Email
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">

                        <!-- MENSAGEM DO ERRO-GERAL( CAMPO NÃO PREENCHIDO) -->
                        <?php if (isset($erro_geral_email)) { ?>
                        <div class=" erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $erro_geral_email ?>
                        </div>

                        <?php } ?>


                        <!-- MENSAGEM DO ERRO-GERAL( CAMPO NÃO PREENCHIDO) -->
                        <?php if (isset($erro_email_usado)) { ?>
                        <div class=" erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $erro_email_usado ?>
                        </div>

                        <?php } ?>


                        <!--MENSAGEM DO ERRO_ATUALIZA-->
                        <?php if (isset($erro_geral_atualiza_email)) { ?>
                        <div class=" erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $erro_geral_atualiza_email ?>
                        </div>

                        <?php } ?>

                        <div class="mb-3">
                            <label for="email_atual" class="form-label text-white">Email Atual</label>
                            <input type="email" class="form-control bg-dark text-white" id="email_atual"
                                value="<?php echo htmlspecialchars($usuario['email']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="novo_email" class="form-label text-white">Novo Email</label>
                            <input <?php if (isset($erro_geral_email) || isset($erro_email)) {
                                        echo 'class=" form-control bg-dark text-white erro-input "';
                                    } else {
                                        echo ' class="form-control bg-dark text-white"';
                                    } ?> name="novo_email" type="email" placeholder=" Digite o Novo Email" required>
                        </div>



                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Salvar Alterações</button>
                    </div>

                </form>
            </div>
            
        </div>

        
    </div>

    <!-- ==============================================
            MODAL DE ALTERAR TELEFONE
        =============================================== -->
    <div class="modal fade edit-modal" id="editTelefoneModal" tabindex="-1" aria-labelledby="editTelefoneModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning" id="editTelefoneModalLabel">
                        <i class="bi bi-telephone me-2"></i>Alterar Número
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- ==============================================
                        FORMULÁRIO PARA ALTERAR TELEFONE
                    =============================================== -->

                <form method="post">
                    <div class="modal-body">

                        <!-- MENSAGEM DO ERRO-GERAL( CAMPO NÃO PREENCHIDO) -->
                        <?php if (isset($erro_geral_numero)) { ?>
                        <div class=" erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $erro_geral_numero ?>
                        </div>

                        <?php } ?>


                        <!--MENSAGEM DO ERRO_ATUALIZA-->
                        <?php if (isset($erro_geral_atualiza_numero)) { ?>
                        <div class=" erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $erro_geral_atualiza_numero ?>
                        </div>

                        <?php } ?>

                        <div class="mb-3">
                            <label for="telefone_atual" class="form-label text-white">Número Atual</label>
                            <input type="text" class="form-control bg-dark text-white" id="telefone_atual"
                                value="<?php echo htmlspecialchars($usuario['contato']); ?>" readonly>
                        </div>


                        <div class="mb-3">
                            <label for="novo_tel" class="form-label text-white">Novo Número</label>
                            <input <?php if (isset($erro_geral_numero) || isset($erro_numero)) {
                                        echo 'class=" form-control bg-dark text-white erro-input "';
                                    } else {
                                        echo ' class="form-control bg-dark text-white"';
                                    } ?> name="novo_tel" type="tel" placeholder=" Digite o novo número" required>

                        </div>

                        <!-- SE OCORRER SOMENTE O ERRO_NUMERO -->
                        <?php if (isset($erro_numero)) { ?>
                        <div class="erro-validacao">
                            <?php echo $erro_numero; ?>
                        </div>
                        <?php } ?>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Salvar Alterações</button>
                    </div>

                </form>
            </div>



        </div>
    </div>



    <!-- ==============================================
     MODAL DE ALTERAR SENHA
    =============================================== -->

    <div class="modal fade user-modal" id="AlterarSenhaModal" tabindex="-1" aria-labelledby="AlterarSenhaModalLabel"
        aria-hidden="true">

        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header mb-4">
                    <h5 class="modal-title text-warning" id="AlterarSenhaModalLabel">Alterar Senha</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- ==============================================
                     FORMULÁRIO PARA ALTERAR SENHA
                =============================================== -->

                <form method="post">
                    <div class="modal-body">

                        <!-- MENSAGEM DO ERRO-GERAL( CAMPO NÃO PREENCHIDO) -->
                        <?php if (isset($erro_geral_senha)) { ?>
                        <div class=" erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $erro_geral_senha ?>
                        </div>

                        <?php } ?>


                        <!--MENSAGEM DO ERRO_ATUALIZA-->
                        <?php if (isset($erro_geral_atualiza_senha)) { ?>
                        <div class=" erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $erro_geral_atualiza_senha ?>
                        </div>

                        <?php } ?>


                        <!--CAMPO SENHA-->
                        <div class="mb-4">


                            <!-- SE OCORRER O ERRO-GERAL(CAMPO NÃO PREENCHIDO) OU ERRO_SENHA( LA DO VALIDA SENHA) -->
                            <input <?php if (isset($erro_geral_senha) || isset($erro_senha_auth)) {
                                        echo 'class=" form-control bg-dark text-white erro-input "';
                                    } else {
                                        echo ' class="form-control bg-dark text-white"';
                                    } ?> name="senha" type="password" placeholder="Nova Senha">


                            <!-- SE OCORRER SOMENTE O ERRO_SENHA -->
                            <?php if (isset($erro_senha)) { ?>
                            <div class="erro-validacao">
                                <?php echo $erro_senha; ?>
                            </div>
                            <?php } ?>


                        </div>

                        <!--CAMPO REPETE_SENHA-->
                        <div class="mb-4">

                            <!-- SE OCORRER O ERRO-GERAL(CAMPO NÃO PREENCHIDO) OU ERRO_REPETE_SENHA( LA DO VALIDA ERRO_REPETE_SENHA) -->
                            <input <?php if (isset($erro_geral_senha) || isset($erro_repete_senha)) {
                                        echo 'class=" form-control bg-dark text-white erro-input "';
                                    } else {
                                        echo ' class="form-control bg-dark text-white"';
                                    } ?> name="repete_senha" type="password" placeholder=" Repita a Nova Senha">


                            <!-- SE OCORRER SOMENTE O ERRO_REPETE_SENHA -->
                            <?php if (isset($erro_repete_senha)) { ?>
                            <div class="erro-validacao">
                                <?php echo $erro_repete_senha; ?>
                            </div>
                            <?php } ?>


                        </div>




                    </div>

                    <!--BOTÔES DO RODAPÉ DO MODAL-->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Salvar Alterações</button>
                    </div>

                </form>
            </div>
        </div>

    </div>
    </div>



    <!-- ==============================================
    MODAL DE EXLCUIR CONTA
    =============================================== -->
    <div class="modal fade user-modal" id="ModalDeletarConta" tabindex="-1" aria-labelledby="deleteAccountModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header mb-2">
                    <h5 class="modal-title text-warning" id="deleteAccountModalLabel">Excluir Conta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- ==============================================
                        FORMULÁRIO PARA EXCLUSÃO DE SENHA
                    =============================================== -->
                <form method="post">

                    <div class="modal-body">

                        <!-- MENSAGEM DO ERRO_DELETE -->
                        <?php if (isset($erro_delete)) { ?>
                        <div class=" erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $erro_delete ?>
                        </div>

                        <?php } ?>

                        <!-- MENSAGEM DO ERRO-SENHA-EXCLUI -->
                        <?php if (isset($erro_senha_exclui)) { ?>
                        <div class=" erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $erro_senha_exclui ?>
                        </div>

                        <?php } ?>



                        <p class="text-white">Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.
                        </p>


                        <!-- CAMPO DE SENHA -->
                        <div class="mb-3">

                            <input <?php if (isset($erro_senha_exclui) || isset($erro_senha_auth)) {
                                        echo 'class=" form-control bg-dark text-white erro-input "';
                                    } else {
                                        echo ' class="form-control bg-dark text-white"';
                                    } ?> type="password" placeholder="Digite sua senha para confirmar"
                                name="senha_exclui" required>

                            <!-- SE OCORRER SOMENTE O ERRO_SENHA_AUTH -->
                            <?php if (isset($erro_senha_auth)) { ?>
                            <div class="erro-validacao">
                                <?php echo $erro_senha_auth ?>
                            </div>
                            <?php } ?>

                        </div>


                    </div>

                    <!--BOTÔES DO RODAPÉ DO MODAL-->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Deletar Conta</button>
                    </div>

                </form>
            </div>
        </div>

    





    </div>




    <!-- ==============================================
          MODAL ESCOLHA DE TAMANHO DAS PIZZAS
        =============================================== -->

    <div class="modal fade" id="modalEscolhaTamanho" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Escolha o tamanho da pizza</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <!--CONTEUDO DE DENTRO DO MODAL , TAMANHOS DAS PIZZAS -->
                <div class="modal-body">
                    <p id="nomePizzaModal" class="text-center fw-bold"></p>
                    <img id="imagemPizzaModal" src="" class="img-fluid mx-auto d-block mb-3" style="max-width: 200px;">
                    <div class="list-group text-center">

                        <!--PIZA PEQUENA-->
                        <label class="list-group-item">
                            <input type="radio" name="tamanhoPizza" value="Pequena" data-preco="30"
                                class="form-check-input">
                            Pequena - 4 fatias (R$30,00)
                        </label>

                        <!--PIZA MÉDIA-->
                        <label class="list-group-item">
                            <input type="radio" name="tamanhoPizza" value="Média" data-preco="55"
                                class="form-check-input">
                            Média - 8 fatias (R$55,00)
                        </label>

                        <!--PIZA GRANDE-->
                        <label class="list-group-item">
                            <input type="radio" name="tamanhoPizza" value="Grande" data-preco="75"
                                class="form-check-input">
                            Grande - 12 fatias (R$75,00)
                        </label>


                    </div>
                </div>

                <!--BOTÔES DO RODAPÉ DO MODAL-->
                <div class="modal-footer justify-content-center ">
                    <button type="button" class="btn btn-warning " onclick="confirmarPedido()">Adicionar ao
                        Carrinho</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>



            </div>
        </div>
    </div>




    <!-- ==============================================
        OFFCANVAS(MENU LATERAL DOS PEDIDOS - SACOLA)
    =============================================== -->

    <div class="offcanvas offcanvas-end bg-dark" tabindex="-1" id="meusPedidosOffcanvas"
        aria-labelledby="meusPedidosOffcanvasLabel">
        <div class="offcanvas-header border-secondary">
            <h5 class="offcanvas-title text-warning" id="meusPedidosOffcanvasLabel">
                <i class="bi bi-bag-check me-2"></i>Sacola
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div id="offcanvas-itens" style="max-height: 60vh; overflow-y: auto;">
                <!-- ITENS DE PEDIDO SERãO INSERIDOS AQUI VIA JAVASCRIPT(RESTITA.JS) -->
                <div class="pedido-item">
                    <div class="d-flex justify-content-between align-items-start">

                    </div>
                </div>


            </div>

            <h4 id="offcanvas-total" class="text-warning mt-3"></h4>

            <!-- Botão para finalizar pedido -->
            <button type="button" class="btn btn-warning w-100 mt-3" onclick="finalizarPedido(event)">
                Finalizar Pedido
            </button>
        </div>
    </div>



    <!-- ==============================================
            MODAL DE PAGAMENTO
        =============================================== -->

    <div class="modal fade" id="modalPagamento" tabindex="-1" aria-labelledby="modalPagamentoLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPagamentoLabel">Finalizar Pedido</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formPagamento" method="post">


                        <!-- MENSAGEM DO ERRO-GERAL( CAMPO NÃO PREENCHIDO) -->
                        <?php if (isset($erro_geral_pedido)) { ?>
                        <div class=" erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $erro_geral_pedido ?>
                        </div>

                        <?php } ?>


                        <!-- MENSAGEM DO ERRO-GERAL( AO INSERIR PEDIDO) -->
                        <?php if (isset($erro_realizar_pedido)) { ?>
                        <div class=" erro-geral animate__animated animate__rubberBand text-center">
                            <?php echo $erro_realizar_pedido ?>
                        </div>

                        <?php } ?>



                        <!-- CAMPO DESCRIÇÃO DO PRODUTO -->
                        <div class="mb-3">
                            <label for="descricao_pedido" class="form-label fw-bold">Detalhes do Pedido:</label>
                            <input <?php if (isset($erro_geral_pedido) || isset($erro_pedido)) {
                                        echo 'class=" form-control bg-dark text-white erro-input "';
                                    } else {
                                        echo ' class="form-control bg-dark text-white"';
                                    } ?> type="text" id="descricao_pedido" name="descricao_pedido" readonly
                                style="cursor: default; white-space: pre; overflow-x: auto;" <?php if (isset($_POST['descricao_pedido'])) {
                                                                                                    echo "value='" . $_POST['descricao_pedido'] . "'";
                                                                                                } ?>>
                        </div>

                        <!-- SE OCORRER SOMENTE O ERRO_PEDIDO -->
                        <?php if (isset($erro_pedido)) { ?>
                        <div class="erro-validacao mb-2">
                            <?php echo $erro_pedido ?>
                        </div>
                        <?php } ?>



                        <!-- CAMPO VALOR TOTAL -->
                        <div class="mb-3">
                            <label for="valor_total" class="form-label fw-bold">Valor Total:</label>
                            <input <?php if (isset($erro_geral_pedido) || isset($erro_valor)) {
                                        echo 'class=" form-control bg-dark text-white erro-input "';
                                    } else {
                                        echo ' class="form-control bg-dark text-white"';
                                    } ?> type="text" id="valor_total" name="valor_total" readonly
                                style="cursor: default; font-weight: bold;" <?php if (isset($_POST['valor_total'])) {
                                                                                echo "value='" . $_POST['valor_total'] . "'";
                                                                            } ?>>
                        </div>


                        <!-- SE OCORRER SOMENTE O ERRO_VALOR -->
                        <?php if (isset($erro_valor)) { ?>
                        <div class="erro-validacao mb-2">
                            <?php echo $erro_valor ?>
                        </div>
                        <?php } ?>



                        <!-- CAMPO DE ENDEREÇO -->
                        <div class="mb-3">
                            <label for="endereco" class="form-label fw-bold">Endereço :</label>
                            <input <?php if (isset($erro_geral_pedido) || isset($erro_endereco)) {
                                        echo 'class=" form-control bg-dark text-white erro-input "';
                                    } else {
                                        echo ' class="form-control bg-dark text-white"';
                                    } ?> type="text" id="endereco" name="endereco" placeholder="Digite seu endereço"
                                <?php if (isset($_POST['endereco'])) {
                                                                                                                            echo "value='" . $_POST['endereco'] . "'";
                                                                                                                        } ?> required>
                        </div>


                        <!-- SE OCORRER SOMENTE O ERRO_EDNEREÇO -->
                        <?php if (isset($erro_endereco)) { ?>
                        <div class="erro-validacao mb-2">
                            <?php echo $erro_endereco ?>
                        </div>
                        <?php } ?>




                        <!-- CAMPO DE PAGAMENTO -->
                        <div class="mb-4">
                            <label for="metodo_pagamento" class="form-label fw-bold">Método de Pagamento:</label>
                            <select <?php if (isset($erro_geral_pedido) || isset($erro_pagamento)) {
                                        echo 'class=" form-select bg-dark text-white erro-input "';
                                    } else {
                                        echo ' class="form-select bg-dark text-white text-white"';
                                    } ?> id="metodo_pagamento" name="metodo_pagamento" required>
                                <option value="">Selecione uma opção</option>
                                <option value="Pix">Pix</option>
                                <option value="Cartão de Credito">Cartão de Crédito</option>
                                <option value="Cartão de Débito">Cartão de Débito</option>
                                <option value="VR">Vale Refeição</option>
                            </select>
                        </div>


                        <button type="submit" class="btn btn-warning w-100 fw-bold py-2">CONFIRMAR PEDIDO</button>

                    </form>
                </div>
            </div>
        </div>

    </div>



    <!-- ==============================================
        SESSÃO CARDÁPIO 
    =============================================== -->

    <div class="container-fluid py-5" id="cardapio" style="background-color: #040304e8" id="cardapio">
        <div class="container">
            <div class="text-center">
                <h1 class="mb-5 text-white">Menu</h1>
            </div>

            <div class="row g-4" id="pizza-container">
                <!--PRODUTOS(PIZZAS) INSERIDOS AQUI VIA JAVASCRIPT (RESTRITA.JS)-->
            </div>
        </div>
    </div>




    <!-- ==============================================
        SESSÃO DO RODAPÉ
    =============================================== -->

    <div class="container-fluid footer text-white pt-3 px-0 position-relative overlay-top"
        style="background-color: #040304df" id="contatos">
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




    <!--  REFERÊNCIA DO JQUERY -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!--  REFERÊNCIA DO BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>

    <!--  REFERÊNCIA DA BILIOTECA OWL CAROUSEL JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

    <!-- REFERÊNCIA JS ( MANIPULACAO DO CAROUSEL) -->
    <script src="assets/js/restrita.js"></script>


    <script>
        // EFEITO DE STICK SCROLL (NAVBAR FICA FIXADO QUANDO ROLA)
        $(window).scroll(function () {
            if ($(this).scrollTop() > 45) {
            $(".navbar").addClass("sticky-top shadow-sm");
            } else {
            $(".navbar").removeClass("sticky-top shadow-sm");
            }
        });
    </script>


    <!-- ==============================================
    MANTÉM OS MODAIS ABERTOS QUANDO HOUVER OS ERROS DE VALIDAÇÃO
    =============================================== -->
   
    <!-- MODAL DE ALTERAR NUMERO -->
    <?php if (isset($erro_geral_numero) || isset($erro_geral_atualiza_numero) || isset($erro_numero)): ?>
    <script>
    $(document).ready(function() {
        var modalNumero = new bootstrap.Modal(document.getElementById('editTelefoneModal'));
        modalNumero.show();
    });
    </script>
    <?php endif; ?>


    <!-- MODAL DE ALTERAR SENHA -->
    <?php if (isset($erro_geral_senha) || isset($erro_geral_atualiza_senha) || isset($erro_senha) || isset($erro_repete_senha)): ?>
    <script>
    $(document).ready(function() {
        var modalSenha = new bootstrap.Modal(document.getElementById('AlterarSenhaModal'));
        modalSenha.show();
    });
    </script>
    <?php endif; ?>


    <!-- MODAL DE EXCLUIR CONTA -->
    <?php if (isset($erro_senha_exclui) || isset($erro_senha_auth) || isset($erro_delete)): ?>
    <script>
    $(document).ready(function() {
        var modalDeleta = new bootstrap.Modal(document.getElementById('ModalDeletarConta'));
        modalDeleta.show();
    });
    </script>
    <?php endif; ?>


    <!-- MODAL DE PEDIDOS -->
    <?php if (isset($erro_geral_pedido) || isset($erro_realizar_pedido) || isset($erro_pedido) || isset($erro_valor) || isset($erro_endereco) || isset($erro_pagamento)): ?>
    <script>
    $(document).ready(function() {
        var modalPagamento = new bootstrap.Modal(document.getElementById('modalPagamento'));
        modalPagamento.show();
    });
    </script>
    <?php endif; ?>



    
    <!-- ==============================================
    TEMPORIZADOR DAS MENSAGENS DE ERRO 
    =============================================== -->
    <script>
    setTimeout(() => {
        $('.erro-geral').fadeOut(600, function() {
            $(this).remove();
        });
        $('.erro-validacao').fadeOut(600, function() {
            $(this).remove();
        });
        $('.sucesso').fadeOut(600, function() {
            $(this).remove();
        });

        $('.erro-input').removeClass('erro-input').css('border', ''); 
    }, 4000);
    </script>






















</body>


</html>
