<?php
// Exemplo de uso do cliente Serasa Experian (consulta_serasa.php).
//
// Mostra o fluxo típico B2B: autentica (login -> token), consulta Score e Relatório de
// um documento, e imprime o resultado tratado. NÃO roda de verdade sem credenciais de
// contrato (SERASA_CLIENT_ID/SERASA_USUARIO/SERASA_SENHA) e sem ajustar os endpoints/
// payloads marcados com "AJUSTAR:" em consulta_serasa.php conforme a doc do seu contrato.
//
// LEMBRETE LGPD: só consulte um documento de terceiro com base legal (análise de risco/
// concessão de crédito) e registre a finalidade — veja docs/REGISTRO-FINALIDADE-LGPD.md.
//
// Uso: SERASA_CLIENT_ID=... SERASA_USUARIO=... SERASA_SENHA=... \
//      php exemplo_serasa.php 12345678000190

require_once __DIR__ . '/consulta_serasa.php';

if (PHP_SAPI !== 'cli' || realpath($argv[0] ?? '') !== __FILE__) {
    return;
}

$documento = $argv[1] ?? '';
if ($documento === '') {
    fwrite(STDERR, "Uso: php exemplo_serasa.php <CPF ou CNPJ>\n");
    exit(2);
}

// 1) Autentica uma vez e reaproveita o token nas consultas seguintes.
$login = serasaLogin();
if ($login['status'] !== 'OK') {
    fwrite(STDERR, "Falha no login: {$login['status']}\n");
    exit(1);
}
$token = $login['token'];
echo "Login OK. Consultando documento: $documento\n\n";

// 2) Score (0–1000).
$score = serasaConsultaScore($documento, $token);
echo "== Score ==\n";
if ($score['status'] === 'OK') {
    echo json_encode($score['dados'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
} else {
    echo "  {$score['status']}\n";
}

echo "\n";

// 3) Relatório PJ/PF (restrições, pendências).
$relatorio = serasaConsultaRelatorio($documento, $token);
echo "== Relatório ==\n";
if ($relatorio['status'] === 'OK') {
    echo json_encode($relatorio['dados'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
} else {
    echo "  {$relatorio['status']}\n";
}

exit($score['status'] === 'OK' || $relatorio['status'] === 'OK' ? 0 : 1);
