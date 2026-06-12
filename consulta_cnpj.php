<?php
// Consulta de CNPJ via API pública (BrasilAPI), retornando JSON estruturado.
//
// Contexto: o endpoint web antigo da Receita (Cnpjreva_Solicitacao_CS.asp) passou
// a redirecionar para solucoes.receita.fazenda.gov.br e continua protegido por
// captcha. Para CNPJ — que é dado PÚBLICO — existe API REST aberta (BrasilAPI),
// que dispensa captcha e devolve JSON. Este arquivo substitui o scraping de CNPJ.
//
// Uso (CLI):    php consulta_cnpj.php 00360305000104
// Uso (função): require 'consulta_cnpj.php'; $dados = consultaCNPJ('00360305000104');

/**
 * Consulta um CNPJ na BrasilAPI.
 *
 * @param string $cnpj  CNPJ com ou sem máscara (pontos, barra, hífen são ignorados).
 * @return array        ['status' => 'OK', 'dados' => array] em caso de sucesso,
 *                      ou ['status' => 'mensagem de erro'] em caso de falha.
 */
function consultaCNPJ($cnpj)
{
    // remove tudo que não for dígito
    $cnpj = preg_replace('/\D/', '', (string) $cnpj);

    if (strlen($cnpj) !== 14) {
        return ['status' => 'CNPJ deve ter 14 dígitos'];
    }

    $url = 'https://brasilapi.com.br/api/cnpj/v1/' . $cnpj;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Consultas_CNPJ_CPF_Receita_Federal');
    $resposta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($resposta === false) {
        return ['status' => 'Falha de conexão: ' . $erroCurl];
    }

    $dados = json_decode($resposta, true);

    if ($httpCode === 404) {
        return ['status' => 'CNPJ não encontrado na base'];
    }
    if ($httpCode !== 200 || !is_array($dados)) {
        $msg = is_array($dados) && isset($dados['message']) ? $dados['message'] : 'HTTP ' . $httpCode;
        return ['status' => 'Erro na consulta: ' . $msg];
    }

    return ['status' => 'OK', 'dados' => $dados];
}

// Execução direta via linha de comando (não dispara quando o arquivo é incluído via require).
if (PHP_SAPI === 'cli' && isset($argv[1]) && realpath($argv[0] ?? '') === __FILE__) {
    $resultado = consultaCNPJ($argv[1]);

    if ($resultado['status'] !== 'OK') {
        fwrite(STDERR, "Erro: {$resultado['status']}\n");
        exit(1);
    }

    $d = $resultado['dados'];
    // imprime um resumo legível dos campos principais
    $resumo = [
        'cnpj'                => $d['cnpj'] ?? '',
        'razao_social'        => $d['razao_social'] ?? '',
        'nome_fantasia'       => $d['nome_fantasia'] ?? '',
        'situacao_cadastral'  => $d['descricao_situacao_cadastral'] ?? '',
        'data_abertura'       => $d['data_inicio_atividade'] ?? '',
        'atividade_principal' => $d['cnae_fiscal_descricao'] ?? '',
        'municipio'           => ($d['municipio'] ?? '') . '/' . ($d['uf'] ?? ''),
    ];
    echo json_encode($resumo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
}
