(function () {
  "use strict";

  var FONTE_COOKIE = "fonte";
  var ESTILO_COOKIE = "styleSheet";
  var TEMA_COOKIE = "nexus-theme";
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
    document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/; SameSite=Lax";
  }

  function readCookie(name) {
    var nameEQ = name + "=";
    var cookies = document.cookie.split(";");
    for (var i = 0; i < cookies.length; i++) {
      var cookie = cookies[i].trim();
      if (cookie.indexOf(nameEQ) === 0) return decodeURIComponent(cookie.substring(nameEQ.length));
    }
    return null;
  }

  function atualizarBotoesFonte() {
    var botoesMenos = document.querySelectorAll('[data-acessibilidade-fonte="menos"]');
    var botoesMais = document.querySelectorAll('[data-acessibilidade-fonte="mais"]');
    var noMinimo = tamanhoFonte <= FONTE_MINIMA + 0.001;
    var noMaximo = tamanhoFonte >= FONTE_MAXIMA - 0.001;
    botoesMenos.forEach(function (botao) { botao.disabled = noMinimo; });
    botoesMais.forEach(function (botao) { botao.disabled = noMaximo; });
  }

  function aplicarFonte(valor, salvar) {
    tamanhoFonte = arredondarFonte(limitarFonte(valor));
    if (Math.abs(tamanhoFonte - FONTE_PADRAO) < 0.001) {
      document.documentElement.style.fontSize = "";
    } else {
      document.documentElement.style.fontSize = Math.round(tamanhoFonte * 100) + "%";
    }
    atualizarBotoesFonte();
    if (salvar) createCookie(FONTE_COOKIE, tamanhoFonte, 365);
  }

  function mudaFonte(tipo) {
    if (tipo === "mais") return aplicarFonte(tamanhoFonte + PASSO_FONTE, true);
    if (tipo === "menos") return aplicarFonte(tamanhoFonte - PASSO_FONTE, true);
    aplicarFonte(FONTE_PADRAO, true);
  }

  function obterLinkContraste() {
    return document.getElementById("styleContraste");
  }

  function obterBaseCss() {
    var link = obterLinkContraste();
    var base = link ? link.getAttribute("data-acessibilidade-css-base") : "";
    if (base) return base;
    if (link && link.href) return link.href.substring(0, link.href.lastIndexOf("/") + 1);
    return "recursos/css/";
  }

  function mudaStyleSheet(sheet) {
    var link = obterLinkContraste();
    if (!link || (sheet !== "style.css" && sheet !== "contraste.css")) return;
    link.setAttribute("href", obterBaseCss() + sheet);
  }

  function aplicarContraste(ativo, salvar) {
    mudaStyleSheet(ativo ? "contraste.css" : "style.css");
    if (document.body) document.body.classList.toggle("alto-contraste", ativo);
    document.querySelectorAll("[data-acessibilidade-contraste]").forEach(function (botao) {
      botao.classList.toggle("is-active", ativo);
      botao.setAttribute("aria-pressed", ativo ? "true" : "false");
      botao.setAttribute("aria-label", ativo ? "Desativar alto contraste" : "Ativar alto contraste");
      botao.title = ativo ? "Desativar alto contraste" : "Ativar alto contraste";
      var label = botao.querySelector(".accessibility-control-label");
      if (label) label.textContent = ativo ? "Desativar alto contraste" : "Alto contraste";
    });
    if (salvar) createCookie(ESTILO_COOKIE, ativo ? "contraste.css" : "style.css", 365);
  }

  function mudaContraste() {
    aplicarContraste(!(document.body && document.body.classList.contains("alto-contraste")), true);
  }

  function lerTemaSalvo() {
    var tema = null;
    try { tema = window.localStorage.getItem("nexus-theme"); } catch (e) { /* localStorage pode estar bloqueado */ }
    return tema === "light" || tema === "dark" ? tema : readCookie(TEMA_COOKIE);
  }

  function atualizarBotoesTema(tema) {
    document.querySelectorAll("[data-theme-toggle]").forEach(function (botao) {
      var claroAtivo = tema === "light";
      var proximoRotulo = claroAtivo ? "Ativar tema escuro" : "Ativar tema claro";
      botao.setAttribute("aria-pressed", claroAtivo ? "true" : "false");
      botao.setAttribute("aria-label", proximoRotulo);
      botao.title = proximoRotulo;
      var icone = botao.querySelector("[data-theme-icon]");
      if (icone) icone.textContent = claroAtivo ? "dark_mode" : "light_mode";
      var rotulo = botao.querySelector("[data-theme-label]");
      if (rotulo) rotulo.textContent = proximoRotulo;
    });
  }

  function aplicarTema(tema, salvar) {
    tema = tema === "light" ? "light" : "dark";
    document.documentElement.setAttribute("data-theme", tema);
    atualizarBotoesTema(tema);
    if (salvar) {
      createCookie(TEMA_COOKIE, tema, 365);
      try { window.localStorage.setItem("nexus-theme", tema); } catch (e) { /* cookie mantém a preferência quando permitido */ }
    }
  }

  function alternarTema() {
    var atual = document.documentElement.getAttribute("data-theme") === "light" ? "light" : "dark";
    aplicarTema(atual === "light" ? "dark" : "light", true);
  }

  function alternarPainelAcessibilidade(aberto) {
    var painel = document.querySelector("[data-accessibility-panel]");
    var botao = document.querySelector("[data-accessibility-toggle]");
    if (!painel || !botao) return;
    var deveAbrir = typeof aberto === "boolean" ? aberto : painel.hidden;
    painel.hidden = !deveAbrir;
    botao.setAttribute("aria-expanded", deveAbrir ? "true" : "false");
    botao.setAttribute("aria-label", deveAbrir ? "Fechar painel de acessibilidade" : "Abrir painel de acessibilidade");
    botao.title = deveAbrir ? "Fechar acessibilidade" : "Acessibilidade";
    if (deveAbrir) {
      var primeiroControle = painel.querySelector("button:not(:disabled)");
      if (primeiroControle) primeiroControle.focus();
    }
  }

  function checkCookie() {
    var fonteCookie = parseFloat(readCookie(FONTE_COOKIE));
    var contrasteCookie = readCookie(ESTILO_COOKIE);
    if (!Number.isNaN(fonteCookie)) aplicarFonte(fonteCookie, false);
    else atualizarBotoesFonte();
    aplicarContraste(contrasteCookie === "contraste.css", false);
    var temaSalvo = lerTemaSalvo();
    aplicarTema(temaSalvo === "light" ? "light" : "dark", false);
  }

  function configurarEventos() {
    document.addEventListener("click", function (evento) {
      var alvo = evento.target;
      if (!(alvo instanceof Element)) return;

      var botaoTema = alvo.closest("[data-theme-toggle]");
      if (botaoTema) {
        evento.preventDefault();
        alternarTema();
        return;
      }

      var alternadorPainel = alvo.closest("[data-accessibility-toggle]");
      if (alternadorPainel) {
        evento.preventDefault();
        alternarPainelAcessibilidade();
        return;
      }

      var fecharPainel = alvo.closest("[data-accessibility-close]");
      if (fecharPainel) {
        evento.preventDefault();
        alternarPainelAcessibilidade(false);
        var launcher = document.querySelector("[data-accessibility-toggle]");
        if (launcher) launcher.focus();
        return;
      }

      var botaoFonte = alvo.closest("[data-acessibilidade-fonte]");
      if (botaoFonte) {
        evento.preventDefault();
        mudaFonte(botaoFonte.getAttribute("data-acessibilidade-fonte"));
        return;
      }

      var botaoContraste = alvo.closest("[data-acessibilidade-contraste]");
      if (botaoContraste) {
        evento.preventDefault();
        mudaContraste();
        return;
      }

      var painel = document.querySelector("[data-accessibility-panel]");
      var launcher = document.querySelector("[data-accessibility-toggle]");
      if (painel && !painel.hidden && !alvo.closest("[data-accessibility-panel]") && !alvo.closest("[data-accessibility-toggle]") && !alvo.closest("[data-theme-toggle]")) {
        alternarPainelAcessibilidade(false);
      }
    });

    document.addEventListener("keydown", function (evento) {
      if (evento.key !== "Escape") return;
      var painel = document.querySelector("[data-accessibility-panel]");
      if (painel && !painel.hidden) {
        alternarPainelAcessibilidade(false);
        var launcher = document.querySelector("[data-accessibility-toggle]");
        if (launcher) launcher.focus();
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
  window.aplicarTemaNexus = aplicarTema;
})();
