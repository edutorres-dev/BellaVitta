/**
 * Arquivo: owl.carousel.js
 *
 * Descrição:
 *  Biblioteca de carrossel de imagens e elementos visuais baseada na versão original do
 *  **Owl Carousel (2013–2017)** desenvolvida por **David Deutsch**.
 *  Esta versão foi simplificada, reorganizada, comentada e adaptada para atender às
 *  necessidades do projeto BellaVitta, mantendo o funcionamento essencial,
 *  porém com código mais enxuto e compatível com a estrutura atual do sistema.
 *
 * Funcionalidades principais:
 *  - Transformação de qualquer container HTML em um carrossel dinâmico
 *  - Navegação entre itens (próximo/anterior)
 *  - Responsividade com base na largura do viewport
 *  - Controle de margem, quantidade de itens e posição inicial
 *  - Re-renderização automática ao redimensionar a tela
 *  - Sistema de preparação e distribuição dos itens (wrap dinâmico)
 *  - Configurações ajustáveis via objeto de opções
 *
 * Fluxo de funcionamento:
 *  1) Instancia a classe principal `OwlCarousel`
 *  2) Mescla opções básicas com opções customizadas
 *  3) Cria toda estrutura interna do carrossel dinamicamente
 *  4) Insere os itens dentro da `owl-stage`
 *  5) Define largura e espaçamento de cada item
 *  6) Adiciona handlers de resize e navegação
 *  7) Atualiza posição ao navegar ou redimensionar
 *
 * Modificações feitas para o projeto BellaVitta:
 *  - Simplificação da classe original para reduzir peso e complexidade
 *  - Remoção de funcionalidades que não eram utilizadas (ex.: drag, animate, nav avançado)
 *  - Adaptação da estrutura interna para funcionar com o layout atual do projeto
 *  - Ajustes para comportamento mais previsível em itens de largura fixa
 *  - Organização lógica e documentação completa em PT-BR
 *
 * Tratamento de eventos:
 *  - Atualização automática do carrossel ao redimensionar a janela
 *  - Cache de timers para evitar múltiplas chamadas de resize (debounce)
 *
 * Segurança:
 *  - Nenhum processamento sensível é realizado neste arquivo
 *  - Totalmente client-side
 *  - Manipulação segura utilizando jQuery/Zepto
 *
 * Dependências:
 *  - jQuery ou Zepto (o código automaticamente utiliza o que estiver disponível)
 *
 * Observações:
 * - Este arquivo mantém a essência da implementação original do Owl Carousel,
 *   porém com adaptações específicas para o design e comportamento do sistema BellaVitta.
 * - O código foi reorganizado e comentado para estudo, manutenção e futuras expansões.
 *
 * Autor original da biblioteca: David Deutsch (2013–2017)
 * Adaptação e documentação: Eduardo Torres Do Ó – Projeto BellaVitta (2025)
 *
 *
 */

(function ($, window, document, undefined) {
  "use strict";

  /**
   * CLASSE PRINCIPAL DO OWL CAROUSEL RESPONSAVEL POR INICIALIZAR, CPMFOGURAR E CONTROLAR  O CAROUSEL.
   * @class OwlCarousel
   * @param {HTMLElement} element
   * @param {Object} [options] 
  */

  function Owl(element, options) {

    // ELEMENTO BASE DO CAROUSEL
    this.$element = $(element);

    // MESCLA OPÇÕES PADRÕES COM PERSONALIZADAS
    this.options = $.extend({}, Owl.Defaults, options);

    // ESTADOS
    this._current = 0; // INDICE DO ITEM ATUAL
    this._handlers = {}; // EVENTOS INTERNOS
    this._plugins = {}; // PLUGINS ADICINADOS(NÃO USADOS)

    // INICIALZAÇÃO DO CAROUSEL
    this.setup();
    this.initialize();
  }

  //COMPORTAMENTO DEFAULT DO CAROUSEL  
  Owl.Defaults = {
    items: 3, // QTD DE ITEMS VISÍVEIS
    loop: false, // VOLTA AO INÍCIO QUANDO CHEGA AO FIM
    center: false, // CENTRALIZA O ITEM ATIVO
    margin: 0, // ESPAÇAMENTO ENTRE ITENS
    stagePadding: 0, // PAADING INTERNO NA AREA DO CAROUSEL
    startPosition: 0, // ITEM INICIAL
    rtl: false, // DIREÇAO DA DIREITA PARA ESQUERDA

    //NAVEGAÇÃO
    nav: false,
    navText: ["prev", "next"],
    dots: true,

    // AUTOPLAY
    autoplay: false,
    autoplayTimeout: 5000,

    // RESPONSIVIDADE
    responsive: {},
    responsiveRefreshRate: 200,
  };

  // CONFIGURAÇOES INCIAIS PARA RESPONSIVIDADE
  Owl.prototype.setup = function () {
    const viewportWidth = this.viewport();

    this.settings = $.extend(
      {},
      this.options,
      this.getResponsiveSettings(viewportWidth)
    );
  };

 
  Owl.prototype.getResponsiveSettings = function (width) {
    const responsiveOpts = this.options.responsive;
    let bestMatch = -1;
    let settings = {};

    if (!responsiveOpts) return settings;

    $.each(responsiveOpts, function (breakpoint) {
      if (breakpoint <= width && breakpoint > bestMatch) {
        bestMatch = Number(breakpoint);
      }
    });

    return bestMatch > -1 ? responsiveOpts[bestMatch] : {};
  };

  // INICIALIZA HTML DO CAROUSEL
  Owl.prototype.initialize = function () {
    
    // CRIA A ESTRUTURA PRINCIPAL
    this.$stage = $('<div class="owl-stage"/>')
      .wrap('<div class="owl-stage-outer"/>')
      .parent()
      .appendTo(this.$element);

    //MARCA O ELEMENTO COMO CARREGADO
    this.$element.addClass("owl-carousel owl-loaded");

    // INSERE OS ITENS NA ESTRUTURA
    this.replace(this.$element.children().not(".owl-stage-outer"));

    // REGISTRA OS EVENTOS
    this.registerEventHandlers();

    // RENDERIZA LAYOUT
    this.refresh();
  };

  
  //  SUBSTITUI CONTEÚDO DO CAROUSEL
  Owl.prototype.replace = function (content) {
    this.$stage.empty();
    this._items = [];

    content.each(
      $.proxy(function (_, item) {
        const preparedItem = this.prepare(item);
        this.$stage.append(preparedItem);
        this._items.push(preparedItem);
      }, this)
    );

    this.reset(this.settings.startPosition);
  };

//  PREPARA CADA ITEM ENCAPSULANDO NA DIV
  Owl.prototype.prepare = function (item) {
    return $('<div class="owl-item"/>').append(item);
  };

  // ATUALIZA LARGURA E POSIÇÃO DOS ITEMS NO CAROUSEL
  Owl.prototype.refresh = function () {
    const stageWidth = this.$element.width();
    const itemWidth = stageWidth / this.settings.items;

    // DEFINE LARGURA E MARGEM PARA CADA ITEM
    this._items.css({
      width: itemWidth,
      marginRight: this.settings.margin,
    });

    this.reset(this._current);
  };

  //  REDEFINE POSIÇÃO ATUAL
  Owl.prototype.reset = function (position) {
    this._current = Math.max(0, position); // Impede números negativos
    this.update();
  };

  // ATUALIZA O DESLOCAMENTO PARA 3D
  Owl.prototype.update = function () {
    const firstItem = this._items.first();
    if (!firstItem.length) return;

    const itemWidth = firstItem.width() + this.settings.margin;
    const offset = -(this._current * itemWidth);

    this.$stage.css("transform", `translate3d(${offset}px, 0, 0)`);
  };

  //  NAVEGA PARA O ITEM ESPECÍFICO
  Owl.prototype.to = function (position) {
    this._current = position;
    this.update();
  };

  // AVANÇA PARA O PRÓXIMO ITEM
  Owl.prototype.next = function () {
    this.to(this._current + 1);
  };

  //VOLTA PARA O TEIM ANTERIOR  
  Owl.prototype.prev = function () {
    this.to(this._current - 1);
  };

  // OBTÉM LARGURA DO VIEWPORT
  Owl.prototype.viewport = function () {
    return window.innerWidth || document.documentElement.clientWidth;
  };


  // REGISTRA OS EVENTOS , ESPECIALMENTE RESIZE COM DEBOUNCE
  Owl.prototype.registerEventHandlers = function () {
    let self = this;

    $(window).on("resize", function () {
      clearTimeout(self.resizeTimer);

      self.resizeTimer = setTimeout(function () {
        self.refresh();
      }, self.settings.responsiveRefreshRate);
    });
  };

  // INICIALIZA O PLUGIN NO ELEMENTO SELECIONADO
  $.fn.owlCarousel = function (options) {
    return this.each(function () {
      if (!$(this).data("owl.carousel")) {
        $(this).data("owl.carousel", new Owl(this, options));
      }
    });
  };
})(window.Zepto || window.jQuery, window, document);
