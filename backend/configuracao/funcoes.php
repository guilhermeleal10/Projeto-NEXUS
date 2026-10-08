<?php

declare(strict_types=1);

function h(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function caminho_base(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    return str_contains($script, '/paginas/') ? '../' : '';
}

function dinheiro(mixed $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function planos_matricula(): array
{
    return [
        'Basico' => ['rotulo' => 'Basico', 'valor' => 0.00],
        'Maromba' => ['rotulo' => 'Maromba', 'valor' => 49.90],
        'Shape' => ['rotulo' => 'Shape', 'valor' => 99.99],
    ];
}

function valor_plano(mixed $plano): float
{
    $planos = planos_matricula();
    return (float) ($planos[(string) $plano]['valor'] ?? 0.00);
}

function rotulo_plano(mixed $plano): string
{
    $planoTexto = (string) $plano;
    $planos = planos_matricula();

    if (!isset($planos[$planoTexto])) {
        return $planoTexto ?: 'Nao informado';
    }

    if ((float) $planos[$planoTexto]['valor'] <= 0) {
        return $planos[$planoTexto]['rotulo'] . ' - gratuito';
    }

    return $planos[$planoTexto]['rotulo'] . ' - ' . dinheiro($planos[$planoTexto]['valor']);
}

function altura_grafico(mixed $valor, mixed $maximo, int $alturaMaxima = 230, int $alturaMinima = 42): int
{
    $valorNumerico = max((float) $valor, 0);
    $maximoNumerico = max((float) $maximo, 1);

    return max($alturaMinima, (int) (($valorNumerico / $maximoNumerico) * $alturaMaxima));
}

function cor_grafico(int $indice): string
{
    $cores = [
        '#00bfff',
        '#ff4fd8',
        '#39ff88',
        '#ffd166',
        '#8fd6ff',
        '#ff7a7a',
        '#a78bfa',
        '#2dd4bf',
    ];

    return $cores[$indice % count($cores)];
}

function total_grafico(array $linhas, string $campoValor = 'total'): float
{
    return array_reduce(
        $linhas,
        fn (float $total, array $linha): float => $total + max((float) ($linha[$campoValor] ?? 0), 0),
        0.0
    );
}

function percentual_grafico(mixed $valor, mixed $total): string
{
    $totalNumerico = max((float) $total, 0);

    if ($totalNumerico <= 0) {
        return '0%';
    }

    return number_format(((float) $valor / $totalNumerico) * 100, 1, ',', '.') . '%';
}

function estilo_grafico_pizza(array $linhas, string $campoValor = 'total'): string
{
    $total = total_grafico($linhas, $campoValor);
    $estiloBase = 'width:min(220px, 100%); max-width:220px; aspect-ratio:1 / 1; display:grid; place-items:center; border-radius:50%; position:relative; overflow:hidden;';

    if ($total <= 0) {
        return $estiloBase . ' background: conic-gradient(rgba(188, 200, 209, 0.24) 0% 100%);';
    }

    $inicio = 0.0;
    $partes = [];

    foreach (array_values($linhas) as $indice => $linha) {
        $valor = max((float) ($linha[$campoValor] ?? 0), 0);

        if ($valor <= 0) {
            continue;
        }

        $fim = $inicio + (($valor / $total) * 100);
        $partes[] = cor_grafico($indice) . ' ' . round($inicio, 2) . '% ' . round($fim, 2) . '%';
        $inicio = $fim;
    }

    return $estiloBase . ' background: conic-gradient(' . implode(', ', $partes) . ');';
}

function mes_ano_curto(mixed $mes, mixed $ano): string
{
    $meses = [
        1 => 'Jan',
        2 => 'Fev',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'Mai',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Set',
        10 => 'Out',
        11 => 'Nov',
        12 => 'Dez',
    ];

    return ($meses[(int) $mes] ?? (string) $mes) . '/' . substr((string) $ano, -2);
}

function data_br(?string $data): string
{
    if (!$data) {
        return 'Não informado';
    }

    $partes = explode('-', $data);
    if (count($partes) !== 3) {
        return $data;
    }

    return "{$partes[2]}/{$partes[1]}/{$partes[0]}";
}

function nome_mes(mixed $mes): string
{
    $meses = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

    return $meses[(int) $mes] ?? (string) $mes;
}

function classe_status(?string $status): string
{
    $normalizado = mb_strtolower((string) $status, 'UTF-8');

    return match ($normalizado) {
        'pago', 'ativa', 'resolvida', 'online' => 'success',
        'atrasado', 'cancelada' => 'danger',
        'pendente', 'trancada', 'aberta', 'em andamento' => 'warning',
        default => '',
    };
}

function badge_status(?string $status): string
{
    $texto = $status ?: 'Sem status';
    return '<span class="status-badge ' . classe_status($texto) . '">' . h($texto) . '</span>';
}

function mensagem_regra_senha(): string
{
    return 'Senha incorreta.';
}

function senha_tem_formato_valido(string $senha): bool
{
    return preg_match('/^\d{6}$/', $senha) === 1;
}

function validar_senha_sistema(string $senha): void
{
    if (!senha_tem_formato_valido($senha)) {
        throw new RuntimeException(mensagem_regra_senha());
    }
}

function senha_criptografada(string $senha): string
{
    return hash('sha256', $senha);
}
