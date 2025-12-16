/**
 * Arquivo: main.js
 * 
 * Descrição:  Script principal do frontend da Pizzaria BellaVitta, responsável por:
 * -> Renderização dinâmica dos serviços e cardápio
 * -> Configuração do carrossel de depoimentos
 * -> Efeitos de navegação 
 *
 * Funcionalidades :
 * -> Renderização dinâmica de cards de serviços
 * -> Exibição do cardápio de pizzas com preços
 * -> Efeito sticky na navbar ao scrollar
 * -> Configuração do carrossel OwlCarousel
 * -> Efeito sticky na navbar ao scrollar
 * 
 *  * Fluxo de operações :
 * 
 * 1) INICIALIZAÇÃO DO SISTEMA
 *    - Carrega todas as dependências (jQuery, OwlCarousel)
 *    - Configura os ouvintes de eventos globais
 * 
 * 2) RENDERIZAÇÃO DINÂMICA DE CONTEÚDO
 *    - Instancia e renderiza cards de serviços
 *    - Instancia e renderiza cards do cardápio de pizzas
 *    - Instancia e renderiza depoimentos no carrossel
 * 
 * 3) GERENCIAMENTO DE INTERAÇÕES DO USUÁRIO
 *    - Botões "Pedir Agora" → redirecionam para cadastro/login
 *    - Navbar sticky → ativa/desativa com scroll
 *    - Carrossel automático → navegação entre depoimentos
 * 
 * 4) RESPONSIVIDADE E OTIMIZAÇÃO
 *    - Layout adaptativo para mobile/tablet/desktop
 *    - Carregamento otimizado de imagens
 *    - Transições suaves entre estados 
 *
 * Dependências:
 * -> Biblioteca jQuery (v3.7.1)
 * -> Biblioteca Owl Carousel (v2.3.4)
 * -> Framework Bootstrap 5
 *
* @ Autor - Eduardo Torres Do Ó
* @ Direitos Reservados - 2025 BellaVitta
*/


/* -==============================================================
  INSERÇÃO DOS SERVIÇOS
===================================================================*/

//CRIA O MOLDE PAI PARA INSTANCIAR OS CARDS
class Servico {
  constructor(titulo, descricao, imagem) {
    this.titulo = titulo;
    this.descricao = descricao;
    this.imagem = imagem;
  }

  // CRIA O MOLDE DOS CARDS DE SERVIÇO
  CriaCardServico() {
    const col = document.createElement("div");
    col.className = "col-lg-3 col-sm-6 d-flex text-center";

    col.innerHTML = `
        <div class="service-item rounded pt-3">
          <div class="p-4">
            <img class="mb-5" src="${this.imagem}" alt="">
            <h5 class="text-white">${this.titulo}</h5>
            <p class="text-white" style="font-size: 16px;">${this.descricao}</p>
          </div>
        </div>
      `;
    return col;
  }
}

// INSTANCIA CADA SERVIÇO
const servicos = [
  new Servico(
    "Chefes Internacionais",
    "Chefes internacionais trazem autenticidade, técnicas exclusivas e sabores inovadores, elevando a experiência gastronônimca .",
    "assets/img/servicos/service-1.png"
  ),
  new Servico(
    "Rápido Atendimento",
    "Nosso atendimento garante eficiência, qualidade e satisfação, proporcionando uma experiência ágil e impecável para todos os clientes.",
    "assets/img/servicos/service-2.png"
  ),
  new Servico(
    "Ingredientes Artesanais",
    "Usamos ingredientes naturais, sempre frescos e selecionados, garantindo sabor autêntico, qualidade superior e uma experiência saudável e irresistível.",
    "assets/img/servicos/service-3.png"
  ),
  new Servico(
    "Localização",
    "Nossa localização estratégica oferece fácil acesso, conforto e conveniência, proporcionando uma experiência agradável e acessível para todos.",
    "assets/img/servicos/service-4.png"
  ),
];

// PERCORE O ARRAY E ADICIONA OS SERIVÇOS LA NA DIV INSERE_SERVICOS
const containerServicos = document.getElementById("insere_servicos");
servicos.forEach((servico) => {
  containerServicos.appendChild(servico.CriaCardServico());
});



/* ================================================================
   INSERÇÃO DAS PIZZAS
===================================================================*/

// CRIA O MOLDE PAI PARA INSTANCIAR AS PIZZAS
class Pizza {
  constructor(nome, imagem, precoPequena, precoMedia, precoGrande) {
    this.nome = nome;
    this.imagem = imagem;
    this.precoPequena = precoPequena;
    this.precoMedia = precoMedia;
    this.precoGrande = precoGrande;
  }

  // CRIA O MOLDE DOS CARDS DAS PIZZAS
  CriaCardPizza() {
    const div = document.createElement("div");
    div.className = "col-lg-3 col-md-6 col-sm-6";
    div.innerHTML = `
            <div class="card h-100 bg-dark text-white shadow card-item">
                <div class="card-img-container">
                    <img src="${this.imagem}" class="card-img-top" alt="${this.nome}">
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${this.nome}</h5>
                    <ul class="list-unstyled text-center">
                        <li class="d-flex justify-content-around iphone-hub">
                            <span class="text-start" style="width: 33%;">Pequena</span>
                            <span class="text-center" style="width: 33%;">4 fatias</span>
                            <span class="text-end text-warning" style="width: 33%;">${this.precoPequena}</span>
                        </li>
                        <li class="d-flex justify-content-around iphone-hub">
                            <span class="text-start" style="width: 33%;">Média</span>
                            <span class="text-center" style="width: 33%;">8 fatias</span>
                            <span class="text-end text-warning" style="width: 33%;">${this.precoMedia}</span>
                        </li>
                        <li class="d-flex justify-content-around iphone-hub">
                            <span class="text-start" style="width: 33%;">Grande</span>
                            <span class="text-center" style="width: 33%;">12 fatias</span>
                            <span class="text-end text-warning" style="width: 33%;">${this.precoGrande}</span>
                        </li>
                    </ul>
                    <button class="btn btn-warning mt-auto" onclick="alert('Cadastre-se para realizar o pedido')">Pedir Agora</button>               
                </div>
            </div>
        `;
    return div;
  }
}

//INSTANCIA AS PIZZAS
const pizzas = [
  new Pizza(
    "Calabresa",
    "assets/img/cardapio/calabresa.jpg",
    "R$30,00",
    "R$55,00",
    "R$75,00"
  ),
  new Pizza(
    "Marguerita",
    "assets/img/cardapio/marg.jpg",
    "R$32,00",
    "R$58,00",
    "R$78,00"
  ),
  new Pizza(
    "Portuguesa",
    "assets/img/cardapio/portuguesa.jpg",
    "R$32,00",
    "R$58,00",
    "R$78,00"
  ),
  new Pizza(
    "Pepperoni",
    "assets/img/cardapio/peperoni (2).jpg",
    "R$32,00",
    "R$58,00",
    "R$78,00"
  ),
  new Pizza(
    "Frango com Catupiry",
    "assets/img/cardapio/frango_catupry.jpg",
    "R$32,00",
    "R$58,00",
    "R$78,00"
  ),
  new Pizza(
    "Prestígio",
    "assets/img/cardapio/prestigio.jpg",
    "R$32,00",
    "R$58,00",
    "R$78,00"
  ),
  new Pizza(
    "Chocolate com Morango",
    "assets/img/cardapio/choco_morango.jpg",
    "R$32,00",
    "R$58,00",
    "R$78,00"
  ),
  new Pizza(
    "Banana com Canela",
    "assets/img/cardapio/banana.jpg",
    "R$32,00",
    "R$58,00",
    "R$78,00"
  ),
];

// PERCORE E ADICIONA AS PIZZA LA NA DIV COM ID PIZZA CONTANEINER
const container = document.getElementById("pizza-container");
pizzas.forEach((pizza) => {
  container.appendChild(pizza.CriaCardPizza());
});

/* ===============================================================
   INSERÇÃO DAS TESTEMUNHAS NA SEÇÃO 
==================================================================*/

// CRIA O MOLDE PAI PARA INSTANCIAR AS TESTEMUNHAS
class Testemunha {
  constructor(nome, localizacao, imagem, texto, estrelas = 4) {
    this.nome = nome;
    this.localizacao = localizacao;
    this.imagem = imagem;
    this.texto = texto;
    this.estrelas = estrelas;
  }

  // CRIA O MOLDE DE ESTILIZAÇÃO PARA CADA TESTEMUNHA
  criarElemento() {
    const div = document.createElement("div");
    div.className = "testimonial-item text-white text-center p-4";
    div.style.backgroundColor = "rgba(255, 255, 255, 0.05)";

    // ESTRELAS
    let estrelasHTML = "";
    for (let i = 0; i < this.estrelas; i++) {
      estrelasHTML +=
        '<small class="fa fa-star text-warning star-fixed"></small>';
    }

    // CONTEÚDO
    div.innerHTML = `
      <img
        class="bg-white rounded-circle shadow p-1 mx-auto mb-3"
        src="${this.imagem}"
        style="width: 80px; height: 80px"
        alt="Foto de ${this.nome}"
      />
      <h5 class="mb-0">${this.nome}</h5>
      <p>${this.localizacao}</p>
      <div class="stars">
        ${estrelasHTML}
      </div>
      <p class="mb-0">"${this.texto}"</p>
    `;
    return div;
  }
}

// INSTANCIA CADA TESTEMUNHA
const testemunhas = [
  new Testemunha(
    "Larissa Alves",
    "Rio de Janeiro, Brasil",
    "assets/img/testemunhas/testimonial-1.jpg",
    "Pizza deliciosa, massa leve e recheio generoso! Melhor experiência gastronômica, voltarei com certeza!"
  ),
  new Testemunha(
    "Carlos Eduardo",
    "São Paulo, Brasil",
    "assets/img/testemunhas/testimonial-2.jpg",
    "Ambiente aconchegante e charmoso! Perfeito para encontros especiais, música agradável e iluminação ideal."
  ),
  new Testemunha(
    "Breno Campos",
    "Argentina, Buenos Aires",
    "assets/img/testemunhas/testimonial-3.jpg",
    "Atendimento impecável! Equipe atenciosa, rápida e sempre sorridente. Tornou minha experiência ainda melhor!"
  ),
  new Testemunha(
    "Débora Martins",
    "New York, USA",
    "assets/img/testemunhas/testimonial-4.jpg",
    "Localização perfeita, fácil acesso e estacionamento amplo. Excelente opção para quem busca qualidade!",
    5
  ),
];

// PERCORRE O ARRAY E ADICIONA AS TESTEMUNHAS NO CAROUSEL
const containerTestemunhas = document.getElementById("testemunho");
testemunhas.forEach((testemunha) => {
  containerTestemunhas.appendChild(testemunha.criarElemento());
});



/* ===============================================================
   CONFIGURAÇÃO DE EFEITO STICKY(FIXO) + CARROUSEL
==================================================================*/

(function ($) {
  "use strict";

  // EFEITO DE STICK DO NAVBAR (FIXO NA ROLAGEM)
  $(window).scroll(function () {
    if ($(this).scrollTop() > 45) {
      $(".navbar").addClass("sticky-top shadow-sm");
    } else {
      $(".navbar").removeClass("sticky-top shadow-sm");
    }
  });

  //CONFIGURAÇÕES DO CAROUSEL
  $(".testimonial-carousel").owlCarousel({
    autoplay: true, 
    smartSpeed: 1000, 
    center: true,
    margin: 24, 
    dots: false, 
    loop: true, 
    nav: false, 

    // RESPONSIVIDADE
    responsive: {
      0: {
        // PARA TELAS DE ATÉ 767px (MOBILE)
        items: 1, // MOSTRA 1 ITEM POR VEZ
      },
      768: {
        // PARA TELAS DE 768px até 991px (TABLET)
        items: 2, // MOSTRA 2 ITENS POR VEZ
      },
      992: {
        // PARA TELAS A PARTIR DE 992px EM DIANTE (DESKTOP)
        items: 3, // MOSTRA 3 ITENS POT VEZ
      },
    },
  });
})(jQuery);
