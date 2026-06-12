<?php
// Atalho pessoal para consultar a situação cadastral do CPF 050.341.539-12 na Receita
// Federal — gratuitamente, sem token, quando você quiser (seu direito de acesso:
// CDC art. 43, LGPD art. 18).
//
// IMPORTANTE: este atalho NÃO pula o captcha, de propósito. O captcha é a forma de
// você provar que é uma pessoa acessando o próprio dado — e não um robô consultando
// CPF de terceiros. O direito de acesso garante a ENTREGA do dado pelos canais
// oficiais; não autoriza contornar o controle técnico que protege a página (e que
// protege o seu próprio CPF de consultas alheias). Por isso o passo do captcha é seu.
//
// Uso: php meu_cpf.php <dd/mm/aaaa>
//      (a data de nascimento não fica gravada no arquivo; você informa na hora)

require_once __DIR__ . '/valida_cpf.php';

const MEU_CPF = '050.341.539-12';
if (!defined('URL_CONSULTA_PUBLICA')) {
    define('URL_CONSULTA_PUBLICA',
        'https://servicos.receita.fazenda.gov.br/servicos/cpf/consultasituacao/consultapublica.asp');
}

function validaDataNascimentoMeuCpf($data)
{
    if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $data, $m)) {
        return false;
    }
    [, $dia, $mes, $ano] = $m;
    return checkdate((int) $mes, (int) $dia, (int) $ano) && (int) $ano >= 1900;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    if (!validaCPF(MEU_CPF)) {
        fwrite(STDERR, "CPF configurado é inválido: " . MEU_CPF . "\n");
        exit(1);
    }

    $nasc = $argv[1] ?? '';
    if (!$nasc) {
        echo "Uso: php meu_cpf.php <dd/mm/aaaa>\n";
        echo "Ex.: php meu_cpf.php 25/12/1990\n\n";
        echo "Seu CPF já está configurado: " . MEU_CPF . " (validado). Falta só a data.\n";
        exit(2);
    }
    if (!validaDataNascimentoMeuCpf($nasc)) {
        fwrite(STDERR, "Data de nascimento inválida (use dd/mm/aaaa): $nasc\n");
        exit(1);
    }

    echo "Consulta gratuita da situação cadastral — CPF " . MEU_CPF . " (sem token):\n\n";
    echo "  1. Abra: " . URL_CONSULTA_PUBLICA . "\n";
    echo "  2. CPF: " . MEU_CPF . "\n";
    echo "  3. Data de nascimento: $nasc\n";
    echo "  4. Resolva o captcha (você provando que é uma pessoa) e clique em Consultar.\n";
    echo "  5. A página mostra nome, situação cadastral e data de inscrição.\n\n";
    echo "Faça isso quando quiser — é o seu direito de acesso, sem custo.\n";
    echo "Alternativa autenticada: gov.br > 'Meu CPF'.\n\n";

    // Tenta abrir a página oficial no navegador, se houver ambiente gráfico.
    foreach (['xdg-open', 'open', 'start'] as $cmd) {
        $caminho = trim((string) @shell_exec('command -v ' . escapeshellarg($cmd) . ' 2>/dev/null'));
        if ($caminho !== '') {
            @shell_exec(escapeshellarg($caminho) . ' ' . escapeshellarg(URL_CONSULTA_PUBLICA) . ' >/dev/null 2>&1 &');
            echo "(Tentei abrir a página no seu navegador padrão.)\n";
            break;
        }
    }
    exit(0);
}
