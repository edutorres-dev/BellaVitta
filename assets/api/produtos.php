<?php
/* Arquivo: produtos.php
* 
* Descrição:
* API para manipulação de produtos da Pizzaria BellaVitta.
* Retorna dados dos produtos em formato JSON para o frontend.
* 
* Funcionalidades :
* -> Consulta única de todos os produtos cadastrados
* -> Retorno em formato JSON
* -> Tratamento de erros
* 
* Fluxo de operação:
* 1) Configura headers para resposta JSON
* 2) Carrega conexão com banco de dados
* 3) Executa query SELECT * FROM produtos
* 4) Retorna resultados como JSON
* 5) Em caso de erro, retorna HTTP 500 com mensagem
* Método HTTP:
* - GET: Retorna lista completa de produtos
* Estrutura da resposta:
* - Sucesso (200): Array de objetos produto
* - Erro (500): Objeto com mensagem de erro
*
*
* Exemplo de resposta (sucesso):
* [
*   {
*     "id": 1,
*     "nome": "Calabresa",
*     "descricao": "Molho, mussarela, calabresa e cebola",
*     "preco": 45.90,
*     ...
*   },
*   ...
* ]
* 
* Exemplo de resposta (erro):
* {
*   "error": "ERRO AO BUSCAR PRODUTOS: Mensagem do erro"
* }
* 
* Dependências:
* - config.php: Conexão com banco de dados
* - PDO: Para consultas SQL seguras
* 
* Segurança:
* -> Prepared statements
* -> Tratamento de erros 
* -> Cabeçalhos específicos
* -> Acesso apenas via requisições HTTP
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2025 BellaVitta
* 
*/

// DEFINIR O CABEÇALHO PARA INDICAR QUE A RESPOSTA SERÁ NO FORMATO JSON
header('Content-Type: application/json');

// INCLUIR O ARQUIVO DE CONFIGURAÇÃO DO BANCO DE DADOS
require("../../assets/config/config.php");

try {
    // PREPARAR E EXECUTAR A CONSULTA SQL PARA OBTER TODOS OS PRODUTOS ORDENADOS POR NOME
    $stmt = $pdo->query("SELECT * FROM produtos ORDER BY nome");
    
    // OBTER TODOS OS REGISTROS RETORNADOS COMO ARRAY ASSOCIATIVO
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // CONVERTER O ARRAY DE PRODUTOS PARA JSON E IMPRIMIR
    echo json_encode($produtos);
    
} catch (PDOException $e) {
    
    // DEFINIR CÓDIGO DE STATUS HTTP 500 (ERRO INTERNO DO SERVIDOR) EM CASO DE ERRO
    http_response_code(500);
    
    // RETORNAR MENSAGEM DE ERRO EM FORMATO JSON
    echo json_encode(['error' => 'ERRO AO BUSCAR PRODUTOS: ' . $e->getMessage()]);
}