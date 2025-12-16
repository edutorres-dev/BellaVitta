/**
* Arquivo: restrita.js
* 
* Descrição:
*  Simulação de um sistema de pedidos para Pizzaria BellaVitta, responsável por:             
* - Gerenciamento de sacola de compras
* - Modais interativos para seleção de produtos
* - Fluxo completo de pedido (seleção → pagamento)
* 
* Funcionalidades :
* -> Inserção das pizzas
* -> Renderização dinâmica do cardápio
* -> Sistema de sacola com agrupamento de itens
* -> Modais interativos (tamanho, pedidos, pagamento)
* -> Cálculo automático de valores
*
*
* Fluxo de Operações :
* 1) Inicialização :
*   -> Carrega pizzas da API (com fallback offline) 
*   -> Renderiza cards no container '#pizza-container' 
*   -> Inicializa instâncias dos modais (DOMContentLoaded) 
*
* 2) Seleção do produto :
*   -> Usuário clica "Pedir Agora" em qualquer pizza 
*   -> abrirModal() armazena pizza selecionada e abre modal de escolha de tamanho das pizzas
*   -> Modal recebe as informaçoes e exibe  da pizza selecionada │
*
* 3) Adição do pedido à sacola
*    -> Usuário seleciona tamanho (P/M/G) e confirma
*    -> confirmarPedido() cria item com : nome, preço , tamanho e imagem da pizza
*    -> item é adicioando a sacola
*    -> interface é atualizada do offcanvas e o contador (badge)
*    -> modal de tamanho é fechado 
*
* 4) Finalizar o pedido
*    ->  Usuário clica "Finalizar Pedido" no offcanvas
*    ->  Verifica se há itens na sacola 
*    ->  fecha offcanvas(Menu lateral) e abre o modal de pagamento ( com temporizador) já atualizado com o pedido 
*
* 5) Processo de pagamento
*    ->  Modal de pagamento é aberto com abrirModalPagamento()
*    ->  Itens são reagrupados para serem mostrados
*    ->  Gera descrição formatada
*
* 6) Pós pagamento
*    ->  Sacola é limpa via limparSacola()
*    ->  Contador é resetado para 0 
*    ->  Sistema está pronto para novo pedido 
*
* 
* Funções de manutenção :
* -> RemoverItemAgrupado(): Remove um item específico da sacola (ação do botão lixeira)
* -> AtualizarContadorSacola(): Atualiza badge visual 
* -> formatarPreco(): Converte números para formato BRL 
* -> formatarPreco(): Converte números para formato BRL 
*
* 
* Dependências:
* - Bootstrap 5 (Modals)
* - jQuery (para eventos de modal)
* 
* 
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2025 BellaVitta
*
*/


/* ================================================================
  INSERÇÃO DAS PIZZAS NA SESSÃO
==================================================================*/

// CARREGA AS PIZZAS DO BANCO DE DADOS VIA API
async function carregarPizzas() {
  try {
    const response = await fetch("assets/api/produtos.php");
    if (!response.ok) throw new Error("Erro ao carregar pizzas");
    return await response.json();
  } catch (error) {
    console.error("Erro:", error);
    
    // SE A API FALHAR , ELE RETORNA ESSAS DUAS PIZZAS
    return [
      {
        nome: "Calabresa",
        imagem: "assets/img/cardapio/calabresa.jpg",
        preco_pequena: 30.0,
        preco_media: 55.0,
        preco_grande: 75.0,
      },
      {
        nome: "Marguerita",
        imagem: "assets/img/cardapio/marg.jpg",
        preco_pequena: 32.0,
        preco_media: 58.0,
        preco_grande: 78.0,
      },
    ];
  }
}

// FORMATA PREÇO
function formatarPreco(valor) {
  return "R$" + valor.toFixed(2).replace(".", ",");
}

// CRIA O MOLDE PARA A INSTANCIAR AS PIZZAS
class Pizza {
  constructor(nome, imagem, precoPequena, precoMedia, precoGrande) {
    this.nome = nome;
    this.imagem = imagem;
    this.precoPequena = precoPequena;
    this.precoMedia = precoMedia;
    this.precoGrande = precoGrande;
  }

  //GERA A ESTILIZACAO VISUAL DO MOLDE DOS CARDS DAS PIZZAS
  criarElemento() {
    const div = document.createElement("div");
    div.className = "col-lg-3 col-md-6 col-sm-6";
    div.innerHTML = `
            <div class="card h-100 bg-dark text-white shadow card-item">
                <div class="card-img-container">
                    <img src="${this.imagem}" class="card-img-top" alt="${
      this.nome
    }">
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${this.nome}</h5>
                    <ul class="list-unstyled text-center">
                        <li class="d-flex justify-content-around iphone-hub">
                            <span class="text-start" style="width: 33%;">Pequena</span>
                            <span class="text-center" style="width: 33%;">4 fatias</span>
                            <span class="text-end text-warning" style="width: 33%;">${formatarPreco(
                              this.precoPequena
                            )}</span>
                        </li>
                        <li class="d-flex justify-content-around iphone-hub">
                            <span class="text-start" style="width: 33%;">Média</span>
                            <span class="text-center" style="width: 33%;">8 fatias</span>
                            <span class="text-end text-warning" style="width: 33%;">${formatarPreco(
                              this.precoMedia
                            )}</span>
                        </li>
                        <li class="d-flex justify-content-around iphone-hub">
                            <span class="text-start" style="width: 33%;">Grande</span>
                            <span class="text-center" style="width: 33%;">12 fatias</span>
                            <span class="text-end text-warning" style="width: 33%;">${formatarPreco(
                              this.precoGrande
                            )}</span>
                        </li>
                    </ul>
                    <button class="btn btn-warning mt-auto" onclick="abrirModal('${
                      this.nome
                    }', '${this.imagem}')">Pedir Agora</button>
                </div>
            </div>
        `;
    return div;
  }
}

// CARREGA E EXIBE AS PIZZAS LA NO ID PERCORRENDO A LISTA
carregarPizzas().then((pizzas) => {
  const container = document.getElementById("pizza-container");
  container.innerHTML = ""; // Limpa o container

  pizzas.forEach((pizza) => {
    const pizzaObj = new Pizza(
      pizza.nome,
      pizza.imagem,
      parseFloat(pizza.preco_pequena),
      parseFloat(pizza.preco_media),
      parseFloat(pizza.preco_grande)
    );
    container.appendChild(pizzaObj.criarElemento());
  });
});



/* ================================================================
   SISTEMA DE GERENCIAMENTO DE PEDIDOS + INTEGRAÇÃO AOS MODAIS
=================================================================*/

// ARRAY QUE ARMAZENA AS PIZZAS ADICIONADAS Á SACOLA
let sacola = [];

// OBJETO QUE ARMAZENA TEMPORARIAMENTE OS DADOS DAS PIZZAS SELECIONADAS 
let pizzaSelecionada = {};

// INSTÂNCIAS DOS MODAIS (SERÃO PREENCHIDAS QUANDO O DOOM ESTIVER PRONTO)
let modalPagamentoInstance = null;
let modalPedidosInstance = null;

// INCIALIZAÇÃO DOS MODAIS AO CARREGAR O DOOM
document.addEventListener("DOMContentLoaded", function () {

  // MODAL DE PAGAMENTO
  modalPagamentoInstance = new bootstrap.Modal(
    document.getElementById("modalPagamento"),
    {
      keyboard: false,
      backdrop: "static",
    }
  );

  // MODAL DE PEDIDOS
  modalPedidosInstance = new bootstrap.Modal(
    document.getElementById("meusPedidosModal")
  );

});


//ABRE O MODAL DE ESCOLHA DE TAMANHO
function abrirModal(nome, imagem) {
  pizzaSelecionada = { nome, imagem };

  document.getElementById("nomePizzaModal").textContent = nome;
  document.getElementById("imagemPizzaModal").src = imagem;

  new bootstrap.Modal(document.getElementById("modalEscolhaTamanho")).show();
}


// CONFIRMA A ESCOLHA DE TAMANHO E ADICIONA Á SACOLA
function confirmarPedido() {
  const opcaoSelecionada = document.querySelector(
    'input[name="tamanhoPizza"]:checked'
  );

  if (!opcaoSelecionada) {
    alert("Por favor, selecione um tamanho.");
    return;
  }

  // CRIA O ITEM(PEDIDO) QUE VAI PARA SACOLA
  const item = {
    nome: `${pizzaSelecionada.nome} (${opcaoSelecionada.value})`,
    preco: parseFloat(opcaoSelecionada.dataset.preco),
    imagem: pizzaSelecionada.imagem,
  };

  //ADICIONA NA SACOLA
  sacola.push(item);

  // ATUALIZA A INTERFACE
  atualizarModalPedidos();
  atualizarContadorSacola();

  // FECHA O MODAL DE TAMANHO
  bootstrap.Modal.getInstance(
    document.getElementById("modalEscolhaTamanho")
  ).hide();
}


// ATUALIZA O CONTADOR DA SACOLA 
function atualizarContadorSacola() {
  const contador = document.getElementById("contadorPedidos");
  const totalItens = sacola.length;

  if (contador) {
    contador.textContent = totalItens;

    // MOSTRA O BADGE SÓ SE HOUVER ICONES
    contador.style.display = totalItens > 0 ? "absolute" : "none";
  }
}

//ATUALIZA O MODAL DE PEDIDOS (OFFCANVAS) E AGRUPA PEDIDOS IGUAIS
function atualizarModalPedidos() {
  const listaPedidos = document.getElementById("offcanvas-itens");
  const totalElement = document.getElementById("offcanvas-total");

  listaPedidos.innerHTML = "";
  let totalPreco = 0;

  if (sacola.length === 0) {
    listaPedidos.innerHTML =
      `<p class="text-center py-4 text-white">Nenhum item adicionado</p>`;
    totalElement.textContent = "Total: R$ 0,00";
    return;
  }

  // AGRUPA ITEMS POR NOME E PREÇO
  const itensAgrupados = {};
  sacola.forEach((item) => {
    const chave = `${item.nome}|${item.preco}`;
    if (!itensAgrupados[chave]) {
      itensAgrupados[chave] = { ...item, quantidade: 1 };
    } else {
      itensAgrupados[chave].quantidade++;
    }
  });

  // RENDERIZA CADA ITEM AGRUPADO
  Object.values(itensAgrupados).forEach((item) => {
    const itemElement = document.createElement("div");
    itemElement.className =
      "d-flex justify-content-between align-items-center py-2 border-bottom border-secondary";

    itemElement.innerHTML = `
      <div class="d-flex align-items-center">
          <img src="${item.imagem}" alt="${item.nome}" 
               width="40" height="40" class="rounded me-2">
          <span>${item.quantidade}x ${item.nome}</span>
      </div>

      <div class="d-flex align-items-center">
          <span class="me-3">R$ ${(item.preco * item.quantidade).toFixed(2)}</span>
          <button class="btn btn-sm btn-outline-danger" 
                  onclick="removerItemAgrupado('${item.nome}', ${item.preco})">
              <i class="bi bi-trash"></i>
          </button>
      </div>`;

    listaPedidos.appendChild(itemElement);

    totalPreco += item.preco * item.quantidade;
  });

  totalElement.textContent = `Total: R$ ${totalPreco.toFixed(2)}`;
}


// REMOÇÃO DE APENAS UM ITEM DOS AGRUPADOS E ATUALIZA NO MODAL DE PEDIDOS E CONTATOR
function removerItemAgrupado(nome, preco) {
  const index = sacola.findIndex(
    (item) => item.nome === nome && item.preco === preco
  );

  if (index !== -1) {
    sacola.splice(index, 1);
    atualizarModalPedidos();
    atualizarContadorSacola();
  }
}


// FINALIZA O PEDIDO 
function finalizarPedido(event) {
  if (event) event.preventDefault();

  if (!sacola.length) {
    alert("Seu carrinho está vazio!");
    return;
  }

  const offcanvasPedidos = bootstrap.Offcanvas.getInstance(
    document.getElementById("meusPedidosOffcanvas")
  );

  // FECHA O OFFCANVAS 
  if (offcanvasPedidos && offcanvasPedidos._isShown) {
    offcanvasPedidos.hide();

    // TEMPORIZADOR PARA ASSIM QUE ELE FECHAR , ABRIR O MODAL DE PAGAMENTO
    setTimeout(() => abrirModalPagamento(), 300);
  } else {
    abrirModalPagamento();
  }

}


// EXIBE O MODAL DE PAGAMENTO COM OS DADOS JÁ FORMTADOS DO PEDIDOS
function abrirModalPagamento() {
  // AGRUPA OS ITEMS IGUAIS
  const itensAgrupados = sacola.reduce((acc, item) => {
    const chave = `${item.nome}|${item.preco}`;
    acc[chave] = acc[chave] || { ...item, quantidade: 0 };
    acc[chave].quantidade++;
    return acc;
  }, {});

  // DESCRIÇÃO EXIBIDA NO FORMULÁRIO DO MODAL
  const descricaoFormatada = Object.values(itensAgrupados)
    .map(
      (item) =>
        `${item.quantidade}x ${item.nome} - R$ ${(item.preco * item.quantidade).toFixed(2)}`
    )
    .join(" , ");

  // CALCULA O TOTAL
  const total = sacola
    .reduce((sum, item) => sum + item.preco, 0)
    .toFixed(2);

  // PREENCHE O FORMULÁRIO
  document.getElementById("descricao_pedido").value = descricaoFormatada;
  document.getElementById("valor_total").value = `R$ ${total}`;

  modalPagamentoInstance.show();
}


//LIMPA A SACOLA APÓS O PEDIDO SER FINALIZADO
function limparSacola() {
  sacola = [];
  atualizarModalPedidos();
  atualizarContadorSacola();
} 




