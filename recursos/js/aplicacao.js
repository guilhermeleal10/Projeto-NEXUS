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
    configurarParticulas();
  });
})();
