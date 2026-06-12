<?php
// Lançador da consulta GRATUITA de situação cadastral do próprio CPF na Receita Federal.
//
// Este é o caminho para exercer seu direito de acesso (CDC art. 43, LGPD art. 18) SEM
// token e SEM contrato: a página pública da Receita. Ele NÃO resolve o captcha por você
// — e isso é proposital. O captcha (hCaptcha) é a forma de você provar que é uma pessoa
// acessando o próprio dado, e não um robô varrendo CPFs de terceiros. Burlá-lo não é
// parte do direito de acesso; o direito garante a ENTREGA do dado pelos canais oficiais.
//
// O que este script faz de útil:
//   1. Valida localmente o CPF (dígitos verificadores) e o formato da data de nascimento,
//      para você não gastar uma resolução de captcha à toa por causa de um erro de digitação.
//   2. Mostra a URL oficial e o passo a passo. Tenta abrir no navegador, se houver ambiente.
//
// Uso: php abrir_consulta_cpf.php 111.444.777-35 25/12/1990

require_once __DIR__ . '/valida_cpf.php';

if (!defined('URL_CONSULTA_PUBLICA')) {
    define('URL_CONSULTA_PUBLICA',
        'https://servicos.receita.fazenda.gov.br/servicos/cpf/consultasituacao/consultapublica.asp');
}

/**
 * Valida data no formato dd/mm/aaaa e confere se é uma data real.
 */
function validaDataNascimento($data)
{
    if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $data, $m)) {
        return false;
    }
    [, $dia, $mes, $ano] = $m;
    return checkdate((int) $mes, (int) $dia, (int) $ano) && (int) $ano >= 1900;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    $cpf  = $argv[1] ?? '';
    $nasc = $argv[2] ?? '';

    if (!$cpf || !$nasc) {
        echo "Uso: php abrir_consulta_cpf.php <CPF> <dd/mm/aaaa>\n";
        echo "Ex.: php abrir_consulta_cpf.php 111.444.777-35 25/12/1990\n";
        exit(2);
    }

    $erros = [];
    if (!validaCPF($cpf)) {
        $erros[] = "CPF inválido (dígitos verificadores não conferem): $cpf";
    }
    if (!validaDataNascimento($nasc)) {
        $erros[] = "Data de nascimento inválida (use dd/mm/aaaa): $nasc";
    }
    if ($erros) {
        fwrite(STDERR, "Corrija antes de consultar:\n - " . implode("\n - ", $erros) . "\n");
        exit(1);
    }

    echo "CPF e data válidos. Para consultar sua situação cadastral (grátis, sem token):\n\n";
    echo "  1. Abra: " . URL_CONSULTA_PUBLICA . "\n";
    echo "  2. Informe o CPF: $cpf\n";
    echo "  3. Informe a data de nascimento: $nasc\n";
    echo "  4. Resolva o captcha (você provando que é uma pessoa) e clique em Consultar.\n";
    echo "  5. A página exibe nome, situação cadastral e data de inscrição.\n\n";
    echo "Alternativa autenticada: acesse o gov.br > 'Meu CPF'.\n\n";

    // Tenta abrir no navegador, se o ambiente tiver interface gráfica.
    $abridores = ['xdg-open', 'open', 'start'];
    foreach ($abridores as $cmd) {
        $caminho = trim((string) @shell_exec('command -v ' . escapeshellarg($cmd) . ' 2>/dev/null'));
        if ($caminho !== '') {
            @shell_exec(escapeshellarg($caminho) . ' ' . escapeshellarg(URL_CONSULTA_PUBLICA) . ' >/dev/null 2>&1 &');
            echo "(Tentei abrir a página no seu navegador padrão.)\n";
            break;
        }
    }
    exit(0);
}
