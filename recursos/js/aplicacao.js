(function () {
  "use strict";

  function configurarMenuMobile() {
    document.addEventListener("click", function (evento) {
      var botao = evento.target.closest("[data-menu-toggle]");
      var menu = document.querySelector("[data-sidebar]");

      if (botao && menu) {
        menu.classList.toggle("is-open");
        return;
      }

      if (menu && menu.classList.contains("is-open") && !evento.target.closest("[data-sidebar]")) {
        menu.classList.remove("is-open");
      }
    });
  }

  function configurarConfirmacao() {
    document.addEventListener("submit", function (evento) {
      var formulario = evento.target;
      if (!formulario.matches("[data-confirmar-exclusao]")) {
        return;
      }

      var mensagem = formulario.getAttribute("data-confirmar-exclusao") || "Deseja excluir este registro?";
      if (!window.confirm(mensagem)) {
        evento.preventDefault();
      }
    });
  }

  function prepararCamposSenha() {
    document.querySelectorAll('input[type="password"], input[data-password-input]').forEach(function (input, indice) {
      if (!input.id) {
        input.id = "senha_auto_" + indice;
      }

      input.setAttribute("inputmode", "numeric");
      input.setAttribute("pattern", "[0-9]{6}");
      input.setAttribute("minlength", "6");
      input.setAttribute("maxlength", "6");
      input.setAttribute("data-password-input", "");

      var shell = input.closest(".input-shell");
      if (!shell || shell.querySelector('[data-password-toggle][aria-controls="' + input.id + '"]')) {
        return;
      }

      var botao = document.createElement("button");
      botao.className = "password-toggle";
      botao.type = "button";
      botao.setAttribute("data-password-toggle", "");
      botao.setAttribute("aria-controls", input.id);
      botao.setAttribute("aria-label", "Mostrar senha");
      botao.setAttribute("aria-pressed", "false");
      botao.innerHTML = '<span class="material-symbols-outlined" aria-hidden="true">visibility</span>';
      input.insertAdjacentElement("afterend", botao);
    });
  }

  function mostrarNotificacaoSenha(formulario, mensagem) {
    var aviso = formulario.querySelector("[data-password-feedback]");

    if (!aviso) {
      aviso = document.createElement("div");
      aviso.className = "feedback is-visible error";
      aviso.setAttribute("data-password-feedback", "");
      aviso.setAttribute("role", "alert");
      formulario.appendChild(aviso);
    }

    aviso.textContent = mensagem;
    aviso.classList.add("is-visible", "error");
  }

  function limparNotificacaoSenha(formulario) {
    var aviso = formulario.querySelector("[data-password-feedback]");

    if (aviso) {
      aviso.remove();
    }
  }

  function senhaValida(input) {
    if (!input.required && input.value === "") {
      return true;
    }

    return /^\d{6}$/.test(input.value);
  }

  function configurarSenhas() {
    prepararCamposSenha();

    function alternarVisibilidadeSenha(botao) {
      var input = document.getElementById(botao.getAttribute("aria-controls"));
      if (!input) {
        return;
      }

      var visivel = input.type === "text";
      input.type = visivel ? "password" : "text";
      botao.setAttribute("aria-pressed", visivel ? "false" : "true");
      botao.setAttribute("aria-label", visivel ? "Mostrar senha" : "Esconder senha");

      var icone = botao.querySelector(".material-symbols-outlined");
      if (icone) {
        icone.textContent = visivel ? "visibility" : "visibility_off";
      }
    }

    function encontrarBotaoSenha(alvo) {
      while (alvo && alvo !== document) {
        if (alvo.matches && alvo.matches("[data-password-toggle]")) {
          return alvo;
        }

        alvo = alvo.parentElement || alvo.parentNode;
      }

      return null;
    }

    document.addEventListener("input", function (evento) {
      var input = evento.target;

      if (!input.matches("[data-password-input]")) {
        return;
      }

      input.value = input.value.replace(/\D/g, "").slice(0, 6);
      var formulario = input.closest("form");
      if (formulario && senhaValida(input)) {
        limparNotificacaoSenha(formulario);
      }
    });

    document.addEventListener("submit", function (evento) {
      var formulario = evento.target;
      var senhaInvalida = Array.from(formulario.querySelectorAll("[data-password-input]")).find(function (input) {
        return !senhaValida(input);
      });

      if (!senhaInvalida) {
        limparNotificacaoSenha(formulario);
        return;
      }

      evento.preventDefault();
      mostrarNotificacaoSenha(formulario, "Senha incorreta.");
      senhaInvalida.focus();
    });

    document.addEventListener("click", function (evento) {
      var botao = encontrarBotaoSenha(evento.target);

      if (!botao) {
        return;
      }

      alternarVisibilidadeSenha(botao);
    });
  }

  function configurarParticulas() {
    var canvas = document.querySelector("[data-particulas]");
    if (!canvas || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      return;
    }

    var contexto = canvas.getContext("2d");
    var particulas = [];
    var animacao = null;

    function redimensionar() {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;

      var quantidade = Math.max(36, Math.floor((canvas.width * canvas.height) / 18000));
      particulas = Array.from({ length: quantidade }, function () {
        return {
          x: Math.random() * canvas.width,
          y: Math.random() * canvas.height,
          vx: (Math.random() - 0.5) * 0.35,
          vy: (Math.random() - 0.5) * 0.35,
          tamanho: Math.random() * 2.2 + 0.8,
          opacidade: Math.random() * 0.45 + 0.2
        };
      });
    }

    function desenhar() {
      contexto.clearRect(0, 0, canvas.width, canvas.height);

      particulas.forEach(function (particula) {
        particula.x += particula.vx;
        particula.y += particula.vy;

        if (particula.x < 0 || particula.x > canvas.width) {
          particula.vx *= -1;
        }
        if (particula.y < 0 || particula.y > canvas.height) {
          particula.vy *= -1;
        }

        contexto.beginPath();
        contexto.arc(particula.x, particula.y, particula.tamanho, 0, Math.PI * 2);
        contexto.fillStyle = "rgba(0, 191, 255, " + particula.opacidade + ")";
        contexto.shadowColor = "rgba(0, 191, 255, 0.8)";
        contexto.shadowBlur = 10;
        contexto.fill();
      });

      animacao = window.requestAnimationFrame(desenhar);
    }

    redimensionar();
    desenhar();
    window.addEventListener("resize", redimensionar);
    window.addEventListener("beforeunload", function () {
      window.cancelAnimationFrame(animacao);
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    configurarMenuMobile();
    configurarConfirmacao();
    configurarSenhas();
    configurarParticulas();
  });
})();
