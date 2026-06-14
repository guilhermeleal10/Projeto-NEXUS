(function () {
  "use strict";

  var FONTE_COOKIE = "fonte";
  var ESTILO_COOKIE = "styleSheet";
  var FONTE_PADRAO = 1;
  var FONTE_MINIMA = 0.9;
  var FONTE_MAXIMA = 1.3;
  var PASSO_FONTE = 0.1;
  var tamanhoFonte = FONTE_PADRAO;

  function limitarFonte(valor) {
    return Math.min(FONTE_MAXIMA, Math.max(FONTE_MINIMA, valor));
  }

  function arredondarFonte(valor) {
    return Math.round(valor * 10) / 10;
  }

  function createCookie(name, value, days) {
    var expires = "";

    if (days) {
      var date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      expires = "; expires=" + date.toUTCString();
    }

    document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/";
  }

  function readCookie(name) {
    var nameEQ = name + "=";
    var cookies = document.cookie.split(";");

    for (var i = 0; i < cookies.length; i++) {
      var cookie = cookies[i].trim();

      if (cookie.indexOf(nameEQ) === 0) {
        return decodeURIComponent(cookie.substring(nameEQ.length));
      }
    }

    return null;
  }

  function atualizarBotoesFonte() {
    var botaoMenos = document.querySelector('[data-acessibilidade-fonte="menos"]');
    var botaoMais = document.querySelector('[data-acessibilidade-fonte="mais"]');
    var noMinimo = tamanhoFonte <= FONTE_MINIMA + 0.001;
    var noMaximo = tamanhoFonte >= FONTE_MAXIMA - 0.001;

    if (botaoMenos) {
      botaoMenos.disabled = noMinimo;
    }

    if (botaoMais) {
      botaoMais.disabled = noMaximo;
    }
  }

  function aplicarFonte(valor, salvar) {
    tamanhoFonte = arredondarFonte(limitarFonte(valor));

    if (Math.abs(tamanhoFonte - FONTE_PADRAO) < 0.001) {
      document.documentElement.style.fontSize = "";
    } else {
      document.documentElement.style.fontSize = Math.round(tamanhoFonte * 100) + "%";
    }

    atualizarBotoesFonte();

    if (salvar) {
      createCookie(FONTE_COOKIE, tamanhoFonte, 365);
    }
  }

  function mudaFonte(tipo) {
    if (tipo === "mais") {
      aplicarFonte(tamanhoFonte + PASSO_FONTE, true);
      return;
    }

    if (tipo === "menos") {
      aplicarFonte(tamanhoFonte - PASSO_FONTE, true);
      return;
    }

    aplicarFonte(FONTE_PADRAO, true);
  }

  function obterLinkContraste() {
    return document.getElementById("styleContraste");
  }

  function obterBaseCss() {
    var link = obterLinkContraste();
    var base = link ? link.getAttribute("data-acessibilidade-css-base") : "";

    if (base) {
      return base;
    }

    if (link && link.href) {
      return link.href.substring(0, link.href.lastIndexOf("/") + 1);
    }

    return "recursos/css/";
  }

  function mudaStyleSheet(sheet) {
    var link = obterLinkContraste();

    if (!link || (sheet !== "style.css" && sheet !== "contraste.css")) {
      return;
    }

    link.setAttribute("href", obterBaseCss() + sheet);
  }

  function aplicarContraste(ativo, salvar) {
    var botao = document.getElementById("mudaEstilo");

    mudaStyleSheet(ativo ? "contraste.css" : "style.css");

    if (document.body) {
      document.body.classList.toggle("alto-contraste", ativo);
    }

    if (botao) {
      botao.classList.toggle("is-active", ativo);
      botao.setAttribute("aria-pressed", ativo ? "true" : "false");
      botao.setAttribute("aria-label", ativo ? "Desativar alto contraste" : "Ativar alto contraste");
      botao.title = ativo ? "Desativar alto contraste" : "Alto contraste";
    }

    if (salvar) {
      createCookie(ESTILO_COOKIE, ativo ? "contraste.css" : "style.css", 365);
    }
  }

  function mudaContraste() {
    var botao = document.getElementById("mudaEstilo");
    var ativo = botao ? !botao.classList.contains("is-active") : readCookie(ESTILO_COOKIE) !== "contraste.css";

    aplicarContraste(ativo, true);
  }

  function checkCookie() {
    var fonteCookie = parseFloat(readCookie(FONTE_COOKIE));
    var contrasteCookie = readCookie(ESTILO_COOKIE);

    if (!Number.isNaN(fonteCookie)) {
      aplicarFonte(fonteCookie, false);
    } else {
      atualizarBotoesFonte();
    }

    aplicarContraste(contrasteCookie === "contraste.css", false);
  }

  function configurarEventos() {
    document.addEventListener("click", function (evento) {
      var botaoFonte = evento.target.closest("[data-acessibilidade-fonte]");
      var botaoContraste = evento.target.closest("[data-acessibilidade-contraste]");

      if (botaoFonte) {
        evento.preventDefault();
        mudaFonte(botaoFonte.getAttribute("data-acessibilidade-fonte"));
        return;
      }

      if (botaoContraste) {
        evento.preventDefault();
        mudaContraste();
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    configurarEventos();
    checkCookie();
  });

  window.mudaFonte = mudaFonte;
  window.mudaStyleSheet = mudaStyleSheet;
  window.mudaContraste = mudaContraste;
  window.createCookie = createCookie;
  window.readCookie = readCookie;
  window.checkCookie = checkCookie;
})();
