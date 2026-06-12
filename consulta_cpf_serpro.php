<?php
// Cliente da API oficial "Consulta CPF" do Serpro (Receita Federal).
//
// Esta é a forma LEGÍTIMA e por requisição de consultar a situação cadastral de um
// CPF: a API é autenticada por contrato (OAuth2), por isso NÃO usa captcha. O captcha
// só existe na página pública do navegador, justamente para impedir automação anônima.
//
// Pré-requisitos para o ambiente de PRODUÇÃO:
//   - Contrato da API Consulta CPF na Loja Serpro (exige certificado digital e-CNPJ).
//   - Consumer Key e Consumer Secret, obtidos na Área do Cliente Serpro.
//
// Para TESTE: existe o ambiente trial, com CPFs de demonstração (não são pessoas reais).
//
// Configuração por variáveis de ambiente (não deixe credenciais no código):
//   SERPRO_CONSUMER_KEY     Consumer Key do contrato
//   SERPRO_CONSUMER_SECRET  Consumer Secret do contrato
//   SERPRO_CPF_AMBIENTE     'trial' (padrão) ou 'producao'
//
// Uso (CLI):    SERPRO_CONSUMER_KEY=... SERPRO_CONSUMER_SECRET=... php consulta_cpf_serpro.php 40442820135
// Uso (função): require 'consulta_cpf_serpro.php'; $r = consultaCpfSerpro('40442820135');

const SERPRO_TOKEN_URL = 'https://gateway.apiserpro.serpro.gov.br/token';
const SERPRO_CPF_PATH_TRIAL    = '/consulta-cpf-df-trial/v1/cpf/';
const SERPRO_CPF_PATH_PRODUCAO = '/consulta-cpf-df/v1/cpf/';
const SERPRO_GATEWAY = 'https://gateway.apiserpro.serpro.gov.br';

/**
 * Obtém um token Bearer (válido por ~1h) via OAuth2 client_credentials.
 *
 * @return array ['status' => 'OK', 'access_token' => string] ou ['status' => erro]
 */
function serproObterToken($consumerKey, $consumerSecret)
{
    $basic = base64_encode($consumerKey . ':' . $consumerSecret);

    $ch = curl_init(SERPRO_TOKEN_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $basic,
        'Content-Type: application/x-www-form-urlencoded',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['status' => 'Falha de conexão no token: ' . $err];
    }
    $json = json_decode($resp, true);
    if ($code !== 200 || empty($json['access_token'])) {
        return ['status' => 'Falha ao autenticar (HTTP ' . $code . '). Verifique Consumer Key/Secret.'];
    }
    return ['status' => 'OK', 'access_token' => $json['access_token']];
}

/**
 * Consulta a situação cadastral de um CPF na API do Serpro.
 *
 * @param string $cpf  CPF com ou sem máscara.
 * @return array       ['status' => 'OK', 'dados' => array] ou ['status' => erro]
 */
function consultaCpfSerpro($cpf, $consumerKey = null, $consumerSecret = null, $ambiente = null)
{
    $cpf            = preg_replace('/\D/', '', (string) $cpf);
    $consumerKey    = $consumerKey    ?? getenv('SERPRO_CONSUMER_KEY');
    $consumerSecret = $consumerSecret ?? getenv('SERPRO_CONSUMER_SECRET');
    $ambiente       = $ambiente       ?? (getenv('SERPRO_CPF_AMBIENTE') ?: 'trial');

    if (strlen($cpf) !== 11) {
        return ['status' => 'CPF deve ter 11 dígitos'];
    }

    // Atalho para testes no trial: um Bearer já obtido pode ser passado em SERPRO_BEARER,
    // dispensando o passo de token (útil para experimentar com os CPFs de demonstração).
    $bearer = getenv('SERPRO_BEARER');
    if ($bearer) {
        $accessToken = $bearer;
    } else {
        if (!$consumerKey || !$consumerSecret) {
            return ['status' => 'Defina SERPRO_CONSUMER_KEY e SERPRO_CONSUMER_SECRET (credenciais do contrato Serpro), ou SERPRO_BEARER para testar o trial'];
        }
        $token = serproObterToken($consumerKey, $consumerSecret);
        if ($token['status'] !== 'OK') {
            return $token;
        }
        $accessToken = $token['access_token'];
    }

    $path = $ambiente === 'producao' ? SERPRO_CPF_PATH_PRODUCAO : SERPRO_CPF_PATH_TRIAL;
    $url  = SERPRO_GATEWAY . $path . $cpf;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $accessToken,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['status' => 'Falha de conexão na consulta: ' . $err];
    }
    $dados = json_decode($resp, true);

    // A API usa códigos HTTP para situações específicas (ver docs "Códigos de retorno").
    if ($code === 200 || $code === 206) {
        return ['status' => 'OK', 'http' => $code, 'dados' => $dados];
    }
    if ($code === 404) {
        return ['status' => 'CPF não encontrado na base'];
    }
    if ($code === 401 || $code === 403) {
        return ['status' => 'Não autorizado (HTTP ' . $code . '). Token ou contrato sem permissão.'];
    }
    $msg = is_array($dados) && isset($dados['mensagem']) ? $dados['mensagem'] : 'HTTP ' . $code;
    return ['status' => 'Retorno inesperado: ' . $msg, 'http' => $code, 'dados' => $dados];
}

// Execução direta via linha de comando.
if (PHP_SAPI === 'cli' && isset($argv[1])) {
    $r = consultaCpfSerpro($argv[1]);
    if ($r['status'] !== 'OK') {
        fwrite(STDERR, "Erro: {$r['status']}\n");
        exit(1);
    }
    echo json_encode($r['dados'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
}
