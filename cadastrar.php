<?php
/*
  arquivo : cadastrar.php

  descrição:
 * Este arquivo é responsável pelo processamento do formulário de cadastro dos usuários no sistema.
 * Ele realiza validações de dados, tratamento de informações sensíveis e o registro no banco de dados.
 * 
 * Funcionalidades :
 * -> Validação de campos obrigatórios
 * -> Verificação de formato de e-mail e número de telefone
 * -> Critérios para validação de senha
 * -> Prevenção contra cadastros duplicados
 * -> Criptografia de senha antes do armazenamento
 * -> Envio de e-mail de confirmação (modo produção)
 * -> Redirecionamento inteligente baseado no ambiente (local/produção)
 * 
 * Fluxo de operação:
 * 1) Recebe os dados do formulário via POST
 * 2) Realiza validações em todos os campos
 * 3) Processa e trata os dados
 * 4) Verifica se o usuário já existe
 * 5) Insere no banco de dados se tudo estiver válido
 * 6) Envia e-mail de confirmação ou redireciona conforme configuração
 * 
 * Dependências:
 * -> PHPMailer para envio de e-mails
 * -> Arquivo config.php com configurações do banco e ambiente
 * -> Bibliotecas externas (Bootstrap, Font Awesome, jQuery)
 * 
 * Segurança:
 * -> Utiliza prepared statements para prevenir SQL injection
 * -> Sanitização de inputs
 * -> Criptografia de senhas
 * -> Validação rigorosa de todos os campos
 * 
 * Observações:
 * -> O modo de operação (local/produção) é definido em config.php
 * -> O template do e-mail está embutido no código
 * 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2025 BellaVitta
*/

/****************************************************************
 * CONFIGURAÇÕES INICIAIS E SEGURANÇA
****************************************************************/

// PUXA AS INFORMAÇÕES DE CONEXÃO COM O BANCO 
require("assets/config/config.php");

//REQUERIMENTO DA BIBLIOTECA PHPMAILER PARA DISPARO DE EMAIL(MODO PRODUÇÃO)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'assets/lib/PHPMailer/src/Exception.php';
require 'assets/lib/PHPMailer/src/PHPMailer.php';
require 'assets/lib/PHPMailer/src/SMTP.php';

/****************************************************************
 PROCESSAMENTO DO FORMULÁRIO DE CADASTRO
****************************************************************/

// SE A POSTAGEM EXISTIR
if(isset($_POST["nome_completo"]) && isset ($_POST["email"])&& isset ($_POST["numero"]) && isset ($_POST["senha"]) && isset ($_POST["repete_senha"])){


  // SE TEM CAMPO VAZIO
  if(empty($_POST["nome_completo"]) || empty($_POST["email"]) || empty($_POST["numero"]) || empty($_POST["senha"]) || empty($_POST["repete_senha"]) || empty($_POST["repete_senha"]) || empty($_POST["termos"])){
    
    // MOSTRA AO USUÁRIO MENSAGEM DE ERRO
    $erro_geral="Todos os campos são obrigatorios";

    // CASO CONTRÁRIO , FORAM PREENCHIDOS CORRETAMENTE
  }else{

    // TRATA OS DADOS
    $nome= trataPost($_POST["nome_completo"]);
    $email = trataPost($_POST["email"]);
    $contato = trataNumero($_POST["numero"]);
    $senha = trataPost($_POST["senha"]);
    $senha_cript = sha1($senha); //criptografa a senha
    $repete_senha = trataPost($_POST["repete_senha"]);
    $checkbox = trataPost($_POST["termos"]);


    // VALIDA OS CAMPOS :

    // CAMPO NOME
    if (!preg_match("/^[A-Za-záàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ'\s]+$/",$nome)) {
      $erro_nome = "Somente permitido letras e espaços!";
    }

    // CAMPO EMAIL
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $erro_email = "Formato de e-mail inválido!";
    }
    
    // CAMPO NÚMERO
    if (!preg_match('/^55[1-9]{2}9\d{8}$/', $contato)) {
        $erro_numero = "Formato inválido! Precisa ter 13 dígitos (55 + DDD + 9 dígitos)";
    }
    
    //CAMPO SENHA
    if (
      strlen($senha) < 6 ||  
      !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $senha) || 
      !preg_match('/[a-zA-Z].*[a-zA-Z]/', $senha) 
    ){
      $erro_senha = "A senha deve conter pelo menos 6 caracteres, 1 caractere especial e 2 letras";
    }

    //VERIFICA SE REPETE SENHA = SENHA
    if($senha !== $repete_senha){
      $erro_repete_senha = "Senha e repetição de senha diferentes!";
    }

    //VERIFICA SE CHECKBOX FOI MARCADO
    if (!isset($_POST["termos"]) || $_POST["termos"] !== "on") {
      $erro_checkbox = "Você deve aceitar os termos.";
    }


    // SE NÃO HOUVE ERROS
    if(!isset($erro_geral) && !isset($erro_nome) && !isset($erro_email) && !isset($erro_numero) && !isset($erro_senha) && !isset($erro_repete_senha) && !isset($erro_checkbox)){

      //VERIFICA SE O USUARIO JA FOI CADASTRADO
      $sql = $pdo ->prepare("SELECT * FROM  usuarios where email =? LIMIT 1");
      $sql->execute(array($email));
      $usuario = $sql->fetch();

      // SE O USUARIO NÃO FOI CADASTRADO
      if(!$usuario){

        // INICIALIZA AS VARIAVEIS PARA CADASTRO
        $recupera_senha="";
        $token="";
        $codigo_confirmacao= sha1(uniqid());
        $status="novo";
        $data_cadastro= date("Y-m-d");
        $nivel_acesso = "cliente";
       
        // CADASTRA O USUÁRIO
        $sql= $pdo->prepare("INSERT INTO usuarios 
        (nome, email, contato, senha, recupera_senha, token, codigo_confirmacao, status, data_cadastro, nivel_acesso) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");        
       
       // SE EXECUTOU A INSERÇÃO CORRETAMENTE
        if($sql->execute(array($nome,$email,$contato,$senha_cript,$recupera_senha,$token, $codigo_confirmacao, $status, $data_cadastro, $nivel_acesso))){
          
          /********************************************
          * MODO LOCAL - REDIRECIONA PARA LOGIN
          ********************************************/
          if($modo=="local"){

            //REDIRECIONA O USUARIO PARA LOGIN
            header('location: login.php?result=ok');

          }

          /********************************************
          * MODO PRODUÇÃO - ENVIA EMAIL DE CONFIRMAÇÃO
          ********************************************/

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



        // SE O USUÁRIO JÁ FOI CADASTRADO NO BANCO
      }else{

        // MOSTRA A MENSAGEM AO USUÁRIO
        $erro_geral = " Usuário já Cadastrado "; 

      }




      
    }


  
  }






}



?>




<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=7">
    <title>Cadastro</title>


   <!-- REFERÊNCIA BOOTSTRAP CSS E BOOTSTRAP ICONS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    />
  
    <!--  REFERÊNCIA DA ANIMAÇÃO DAS VALIDACOES -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <!-- REFERÊNCIA DO CSS EXTERNO -->
    <link rel="stylesheet" href="assets/css/form_aut.css" />

  </head>

  <body>

    <div class="aut-container">

      <!-- ==============================================
      FORMUÁRIO DE CADASTRO
      =============================================== -->

      <h3>Cadastrar</h3>

      <form method="post">
       
        <!-- MENSAGEM DE ERRO-GERAL(CAMPO VAZIO OU USUÁRIO JÁ CADASTRADO) -->
        <?php if(isset($erro_geral)){ ?>
          <div class=" erro-geral animate__animated animate__rubberBand text-center">
            <?php echo $erro_geral ?>
          </div> 
          
        <?php } ?>
              
            
        <!--MENSAGEM DE SUCESSO ( USUARIO CADASTRADO COM SUCESSO)-->
        <?php if (isset($_GET['result']) && ($_GET['result']=="ok")){ ?>
          <div class=" animate__animated animate__rubberBand  sucesso" >
          Usuário cadastrado com sucesso!
          </div>               
        <?php }?>


        <!-- CAMPO NOME -->
        <div <?php if(isset($erro_geral) || isset($erro_nome) ){echo 'class=" input-group erro-borda "';}else{ echo' class="input-group"';}?>>
          <span class="input-group-text"><i <?php if(isset($erro_geral) || isset($erro_nome) ){echo 'class=" bi bi-person text-danger "';}else{ echo' class="bi bi-person"';}?>></i></span>
          <input class="form-control" name="nome_completo" type="text" placeholder="Nome Completo" required/>
        </div>

        <!-- SE OCORRER SOMENTE O ERRO_NOME -->
        <?php if(isset($erro_nome)){ ?>
          <div class="erro-validacao">
            <?php echo $erro_nome  ?>
            
          </div>

        <?php } ?>

        <!-- CAMPO EMAIL -->
        <div <?php if(isset($erro_geral) || isset($erro_email) ){echo 'class=" input-group erro-borda "';}else{ echo' class="input-group"';}?>>
          <span class="input-group-text"> <i <?php if(isset($erro_geral) || isset($erro_email) ){echo 'class=" bi bi-envelope text-danger "';}else{ echo' class="bi bi-person"';}?> ></i></span>
          <input class="form-control" name="email" type="email" placeholder="Email" required />
        </div>

        <!-- SE OCORRER SOMENTE O ERRO_EMAIL -->
        <?php if(isset($erro_email)){ ?>
          <div class="erro-validacao">
            <?php echo $erro_email  ?>
            
          </div>

        <?php } ?>



        <!-- CAMPO NUMERO -->
        <div <?php if(isset($erro_geral) || isset($erro_numero) ){echo 'class=" input-group erro-borda "';}else{ echo' class="input-group"';}?>>
          <span class="input-group-text"> <i <?php if(isset($erro_geral) || isset($erro_numero) ){echo 'class=" bi bi-telephone text-danger "';}else{ echo' class="bi bi-telephone"';}?> ></i></span>
          <input class="form-control" name="numero" type="tel" placeholder="Seu Número" required/>
        </div>

        <!-- SE OCORRER SOMENTE O ERRO_NUMERO -->
        <?php if(isset($erro_numero)){ ?>
          <div class="erro-validacao">
            <?php echo $erro_numero  ?>
            
          </div>

        <?php } ?>

        <!-- CAMPO SENHA -->
        <div <?php if(isset($erro_geral) || isset($erro_senha)){echo 'class=" input-group erro-borda "';}else{ echo' class="input-group"';}?>>
          <span class="input-group-text"><i <?php if(isset($erro_geral) || isset($erro_repete_senha)){echo 'class=" bi bi-lock text-danger "';}else{ echo' class="bi bi-lock"';}?>></i></span>
          <input class="form-control" name="senha" type="password" placeholder="Senha" required />
        </div>


        <!-- SE OCORRER SOMENTE O ERRO_SENHA -->
        <?php if(isset($erro_senha)){ ?>
          <div class="erro-validacao">
            <?php echo $erro_senha  ?>
            
          </div>

        <?php } ?>

        <!-- CAMPO REPETE SENHA -->
        <div <?php if(isset($erro_geral) || isset($erro_repete_senha)){echo 'class=" input-group erro-borda "';}else{ echo' class="input-group"';}?>>
          <span class="input-group-text "><i <?php if(isset($erro_geral) || isset($erro_repete_senha)){echo 'class=" bi bi-lock-fill text-danger "';}else{ echo' class="bi bi-lock-fill"';}?>></i></span>
          <input class="form-control" name="repete_senha" type="password" placeholder="Repita a senha" required />
        </div>

        <!-- SE OCORRER SOMENTE O ERRO_REPETE_SENHA -->
        <?php if(isset($erro_repete_senha)){ ?>
          <div class="erro-validacao">
            <?php echo $erro_repete_senha  ?>
          </div>

        <?php } ?>

        <!-- CHECKBOX -->
        <div class="form-check">
          <input <?php if(isset($erro_geral) || isset($erro_checkbox)){echo 'class=" form-check-input erro-borda "';}else{ echo' class="form-check-input "';}?>
          class="form-check-input"
          name="termos"
          type="checkbox"
          id="termos"
          />
          <label class="form-check-label" for="termos">
            Ao se cadastrar você concorda com a nossa
            <a href="#" class="privacidade-link">Política de Privacidade</a>
            e os <a href="#" class="privacidade-link">Termos de uso</a>
          </label>
        </div>

        <!-- SE OCORRER SOMENTE O ERRO_REPETE_SENHA -->
        <?php if(isset($erro_checkbox)){ ?>
          <div class="erro-validacao">
            <?php echo $erro_checkbox  ?>
            
          </div>

        <?php } ?>

        <!-- BOTÃO CADASTRAR -->
        <button type="submit" class="btn-aut">Cadastrar</button>

        <!-- LINK PARA LOGIN-->
        <div class="aut-links">
          <p><a href="login.php">Já tenho uma conta</a></p>
        </div>



      </form>


    </div>
    
    <!-- REFERÊNCIA DO BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- REFERÊNCIA DO JQUERY -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>

      // TEMPORIZADOR DAS MENSAGENS DE ERRO
      setTimeout(() => {
        //REMOVE OS ERROS GERAL(CAMPO VAZIO) E ERRO-VALIDAÇÃO(VALIDAÇÃO ESPECIFICA DE CADA CAMPO COM MENSAGEM)
          $('.erro-geral').fadeOut(600, function() { $(this).remove(); });
          $('.erro-validacao').fadeOut(600, function() { $(this).remove(); });
          
        //REMOVE O ERRO-INPUT ( BORDA AVERMELHADA) E TEXT-DANGER
          $('.erro-borda').removeClass('erro-borda')
          $('.text-danger').removeClass('text-danger')
      }, 4300);

        
    </script>
  </body>
</html>
