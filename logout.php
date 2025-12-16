<?php
/*
* Arquivo: logout.php
* 
* Descrição:
* Script de encerramento de sessão da Pizzaria BellaVitta.
* Responsável por finalizar a sessão do usuário e redirecioná-lo para a página de login.
* 
* Funcionalidades:
* -> Encerra a sessão ativa do usuário
* -> Limpa todos os dados da sessão
* -> Destrói completamente a sessão
* -> Redireciona para a página de login
* 
* Fluxo de operação:
* 1) Inicializa a sessão PHP para acessar os dados de sessão existentes
* 2) Limpa todas as variáveis de sessão registradas (session_unset)
* 3) Destrói completamente a sessão atual no servidor (session_destroy)
* 4) Redireciona o usuário para a página de login (header location)
* 5) Encerra a execução do script após o redirecionamento
* 
* Dependências:
* -> Requer sessão ativa para funcionar corretamente
* -> Integrado com login.php para redirecionamento
* -> Compatível com sistema de autenticação do painel administrativo
* 
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2025 BellaVitta
*/

// INICIALIZA A SESSÃO
session_start();

// LIMPA A SESSÃO 
session_unset();

// DESTRÓI A SESSÃO 
session_destroy();

// REDIRECIONA O USUÁRIO PARA LOGIN
header("location: login.php");

exit;
?>