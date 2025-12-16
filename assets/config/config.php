<?php

/*
* Arquivo: config.php
* 
* Descrição:
* Arquivo central de configuração do sistema da Pizzaria BellaVitta.
* Responsável por estabelecer conexão com o banco de dados e fornecer funções utilitárias essenciais.
* 
* Funcionalidades :
* -> Gerenciamento de conexão com o banco de dados (local/produção)
* -> Tratamento de dados (entrada)
* -> Sistema de autenticação por token
* -> Configurações globais do sistema
* 
* Fluxo de operação:
*
* 1) Inicia a sessão php e atribuição de variável
* 2) Configuração de ambiente ( modo local / produção)
* 3) Conexão com o banco de dados
* 4) tratamento de dados
* 5) Autorização do token

* Segurança:
* -> Dados de conexão protegidos
* -> Sanitização de inputs (XSS/SQL injection prevention)
* -> Gerenciamento de sessão seguro
* -> Tratamento de erros de conexão
* 
* Dependências:
* -> PDO para conexão com MySQL
* -> Sessões PHP para autenticação
* 
* Observações importantes:
* -> Alterar credenciais para produção
* -> Não expor informações sensíveis

* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2025 BellaVitta
* 

*/

/****************************************************************
 * CONFIGURAÇÕES DE INICIALIZAÇÃO 
****************************************************************/

// INICIALIZA SESSÃO
session_start();

// SEU SITE
$site = "https://bellavitta.online/"; 


/****************************************************************
 * MODOS DE CONEXÃO ( CONFIGURAÇÃO DE AMBIENTE)
****************************************************************/

$modo = "producao";

// LOCAL
if ($modo =="local"){
    $servidor = "localhost";
    $usuario = "root";
    $senha = "";
    $banco = "bella";
}

// PRODUÇÃO
if($modo == "producao"){
    $servidor = "";
    $usuario = "";
    $senha = "";
    $banco = "";
}


/****************************************************************
 * ABERTURA DE CONEXÃO
****************************************************************/

try {
    $pdo = new PDO("mysql:host=$servidor;dbname=$banco", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Banco conectado com sucesso!";
} catch (PDOException $erro) {
    echo "Falha ao se conectar com o banco! " . $erro->getMessage();
}


/****************************************************************
 * TRATAMENTO DE ENTRADAS
****************************************************************/
function trataPost($dados){
    $dados=trim($dados);
    $dados=stripcslashes($dados);
    $dados=htmlspecialchars($dados);
    return $dados;
}

function trataNumero($dados){
    $dado=trim($dados);
    return preg_replace('/[^0-9]/', '', $dado);


}



/****************************************************************
 * FUNÇÃO PARA AUTORIZAR TOKEN DO USUÁRIO
****************************************************************/

function auth($tokenSessao){

    // TEVE QUE REDECLARAR COMO GLOBAL PARA PEGAR A CONEXÃO COM O BANCO
    global $pdo;

    // VERIFICA SE O USUÁRIO TEM O TOKEN
    $sql = $pdo -> prepare(" SELECT * FROM usuarios WHERE token=? LIMIT 1 ");
    $sql->execute(array($tokenSessao));
    $usuario = $sql->fetch(PDO::FETCH_ASSOC);

    //SE O USUÁRIO NAO TEM O TOKEN
     if(!$usuario){
        return false;
    }else{
        return $usuario;
    }

}








?>