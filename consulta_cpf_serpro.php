<?php
// Cliente da API oficial "Consulta CPF" do Serpro (Receita Federal) — pronto para produção.
//
// Esta é a forma LEGÍTIMA e por requisição de consultar a situação cadastral de um CPF:
// a API é autenticada por contrato (OAuth2), por isso NÃO usa captcha. O captcha só
// existe na página pública do navegador, para impedir automação anônima.
//
// Recursos de produção:
//   - Cache do token Bearer (expira em ~1h): evita pedir token a cada consulta.
//   - Retry com backoff exponencial em falhas transitórias (429, 5xx, conexão).
//   - Tradução dos códigos de situação cadastral (Regular, Suspensa, etc.).
//   - Consulta em lote com pausa configurável entre chamadas (rate limit).
//
// Pré-requisitos (PRODUÇÃO):
//   - Contrato da API Consulta CPF na Loja Serpro (exige certificado digital e-CNPJ).
//   - Consumer Key e Consumer Secret (Área do Cliente Serpro).
//
// Configuração por variáveis de ambiente (nunca deixe credenciais no código):
//   SERPRO_CONSUMER_KEY     Consumer Key do contrato
//   SERPRO_CONSUMER_SECRET  Consumer Secret do contrato
//   SERPRO_CPF_AMBIENTE     'trial' (padrão) ou 'producao'
//   SERPRO_BEARER           (opcional) Bearer já obtido, para testes no trial
//   SERPRO_TOKEN_CACHE      (opcional) caminho do arquivo de cache do token
//   SERPRO_MAX_RETRIES      (opcional) nº de tentativas em falha transitória (padrão 3)
//
// Uso (CLI 1 CPF):   php consulta_cpf_serpro.php 40442820135
// Uso (CLI lote):    php consulta_cpf_serpro.php --lote cpfs.txt    (um CPF por linha)
// Uso (função):      require '...'; $r = consultaCpfSerpro('40442820135');

const SERPRO_TOKEN_URL         = 'https://gateway.apiserpro.serpro.gov.br/token';
const SERPRO_CPF_PATH_TRIAL    = '/consulta-cpf-df-trial/v1/cpf/';
const SERPRO_CPF_PATH_PRODUCAO = '/consulta-cpf-df/v1/cpf/';
const SERPRO_GATEWAY           = 'https://gateway.apiserpro.serpro.gov.br';

// Códigos de situação cadastral do CPF retornados pela API (campo situacao.codigo).
const SERPRO_SITUACOES = [
    '0' => 'Regular',
    '2' => 'Suspensa',
    '3' => 'Titular falecido',
    '4' => 'Pendente de regularização',
    '5' => 'Cancelada por multiplicidade',
    '8' => 'Nula',
    '9' => 'Cancelada de ofício',
];

/**
 * Caminho do arquivo de cache do token para um dado contrato/ambiente.
 */
function serproCaminhoCacheToken($consumerKey, $ambiente)
{
    $custom = getenv('SERPRO_TOKEN_CACHE');
    if ($custom) {
        return $custom;
    }
    $hash = substr(hash('sha256', $consumerKey . '|' . $ambiente), 0, 16);
    return sys_get_temp_dir() . '/serpro_token_' . $hash . '.json';
}

/**
 * Lê um token válido do cache, se existir e não estiver expirado.
 *
 * @return string|null  access_token válido ou null.
 */
function serproTokenDoCache($consumerKey, $ambiente)
{
    $arquivo = serproCaminhoCacheToken($consumerKey, $ambiente);
    if (!is_file($arquivo)) {
        return null;
    }
    $dados = json_decode((string) @file_get_contents($arquivo), true);
    if (!is_array($dados) || empty($dados['access_token']) || empty($dados['expira_em'])) {
        return null;
    }
    // margem de 60s para não usar um token prestes a expirar
    if ($dados['expira_em'] - 60 <= time()) {
        return null;
    }
    return $dados['access_token'];
}

/**
 * Grava o token no cache com permissão restrita (0600).
 */
function serproGravaCacheToken($consumerKey, $ambiente, $accessToken, $expiresIn)
{
    $arquivo = serproCaminhoCacheToken($consumerKey, $ambiente);
    $payload = json_encode([
        'access_token' => $accessToken,
        'expira_em'    => time() + (int) $expiresIn,
    ]);
    // cria com permissão restrita antes de escrever credencial
    $fp = @fopen($arquivo, 'c');
    if ($fp === false) {
        return;
    }
    @chmod($arquivo, 0600);
    @ftruncate($fp, 0);
    @fwrite($fp, $payload);
    @fclose($fp);
}

/**
 * Executa uma chamada cURL com retry/backoff em falhas transitórias (429, 5xx, conexão).
 *
 * @return array ['resp' => string|false, 'code' => int, 'err' => string]
 */
function serproRequestComRetry(callable $configura, $maxTentativas)
{
    $espera = 1; // segundos; dobra a cada tentativa
    for ($tentativa = 1; ; $tentativa++) {
        $ch = curl_init();
        $configura($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        $transitorio = ($resp === false) || $code === 429 || ($code >= 500 && $code <= 599);
        if (!$transitorio || $tentativa >= $maxTentativas) {
            return ['resp' => $resp, 'code' => $code, 'err' => $err];
        }
        sleep($espera);
        $espera *= 2;
    }
}

/**
 * Obtém um token Bearer (válido por ~1h) via OAuth2 client_credentials, com retry.
 *
 * @return array ['status' => 'OK', 'access_token' => string, 'expires_in' => int] ou ['status' => erro]
 */
function serproObterToken($consumerKey, $consumerSecret, $maxTentativas = 3)
{
    $basic = base64_encode($consumerKey . ':' . $consumerSecret);

    $r = serproRequestComRetry(function ($ch) use ($basic) {
        curl_setopt($ch, CURLOPT_URL, SERPRO_TOKEN_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $basic,
            'Content-Type: application/x-www-form-urlencoded',
        ]);
    }, $maxTentativas);

    if ($r['resp'] === false) {
        return ['status' => 'Falha de conexão no token: ' . $r['err']];
    }
    $json = json_decode($r['resp'], true);
    if ($r['code'] !== 200 || empty($json['access_token'])) {
        return ['status' => 'Falha ao autenticar (HTTP ' . $r['code'] . '). Verifique Consumer Key/Secret.'];
    }
    return [
        'status'       => 'OK',
        'access_token' => $json['access_token'],
        'expires_in'   => $json['expires_in'] ?? 3600,
    ];
}

/**
 * Resolve um Bearer válido: usa SERPRO_BEARER, ou o cache, ou pede um novo e cacheia.
 *
 * @return array ['status' => 'OK', 'access_token' => string] ou ['status' => erro]
 */
function serproResolveToken($consumerKey, $consumerSecret, $ambiente, $maxTentativas)
{
    $bearer = getenv('SERPRO_BEARER');
    if ($bearer) {
        return ['status' => 'OK', 'access_token' => $bearer];
    }
    if (!$consumerKey || !$consumerSecret) {
        return ['status' => 'Defina SERPRO_CONSUMER_KEY e SERPRO_CONSUMER_SECRET (contrato Serpro), ou SERPRO_BEARER para testar o trial'];
    }
    $cache = serproTokenDoCache($consumerKey, $ambiente);
    if ($cache !== null) {
        return ['status' => 'OK', 'access_token' => $cache];
    }
    $token = serproObterToken($consumerKey, $consumerSecret, $maxTentativas);
    if ($token['status'] !== 'OK') {
        return $token;
    }
    serproGravaCacheToken($consumerKey, $ambiente, $token['access_token'], $token['expires_in']);
    return ['status' => 'OK', 'access_token' => $token['access_token']];
}

/**
 * Consulta a situação cadastral de um CPF na API do Serpro.
 *
 * @param string $cpf  CPF com ou sem máscara.
 * @return array       ['status' => 'OK', 'dados' => array, 'situacao_descricao' => string]
 *                     ou ['status' => mensagem de erro].
 */
function consultaCpfSerpro($cpf, $consumerKey = null, $consumerSecret = null, $ambiente = null)
{
    $cpf            = preg_replace('/\D/', '', (string) $cpf);
    $consumerKey    = $consumerKey    ?? getenv('SERPRO_CONSUMER_KEY');
    $consumerSecret = $consumerSecret ?? getenv('SERPRO_CONSUMER_SECRET');
    $ambiente       = $ambiente       ?? (getenv('SERPRO_CPF_AMBIENTE') ?: 'trial');
    $maxTentativas  = max(1, (int) (getenv('SERPRO_MAX_RETRIES') ?: 3));

    if (strlen($cpf) !== 11) {
        return ['status' => 'CPF deve ter 11 dígitos'];
    }

    $token = serproResolveToken($consumerKey, $consumerSecret, $ambiente, $maxTentativas);
    if ($token['status'] !== 'OK') {
        return $token;
    }

    $path = $ambiente === 'producao' ? SERPRO_CPF_PATH_PRODUCAO : SERPRO_CPF_PATH_TRIAL;
    $url  = SERPRO_GATEWAY . $path . $cpf;

    $r = serproRequestComRetry(function ($ch) use ($url, $token) {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Bearer ' . $token['access_token'],
        ]);
    }, $maxTentativas);

    if ($r['resp'] === false) {
        return ['status' => 'Falha de conexão na consulta: ' . $r['err']];
    }
    $dados = json_decode($r['resp'], true);
    $code  = $r['code'];

    // A API usa códigos HTTP específicos (ver docs "Códigos de retorno").
    if ($code === 200 || $code === 206) {
        $codSit = is_array($dados) && isset($dados['situacao']['codigo']) ? (string) $dados['situacao']['codigo'] : null;
        return [
            'status'             => 'OK',
            'http'               => $code,
            'dados'              => $dados,
            'situacao_descricao' => $codSit !== null ? (SERPRO_SITUACOES[$codSit] ?? 'Desconhecida') : null,
        ];
    }
    if ($code === 404) {
        return ['status' => 'CPF não encontrado na base'];
    }
    if ($code === 401 || $code === 403) {
        return ['status' => 'Não autorizado (HTTP ' . $code . '). Token expirado/sem permissão — limpe o cache e tente de novo.'];
    }
    if ($code === 429) {
        return ['status' => 'Limite de requisições excedido (HTTP 429) após retries. Reduza o ritmo.'];
    }
    $msg = is_array($dados) && isset($dados['mensagem']) ? $dados['mensagem'] : 'HTTP ' . $code;
    return ['status' => 'Retorno inesperado: ' . $msg, 'http' => $code, 'dados' => $dados];
}

/**
 * Consulta uma lista de CPFs, com pausa entre chamadas para respeitar o rate limit.
 *
 * @param array $cpfs            Lista de CPFs.
 * @param float $pausaSegundos   Pausa entre consultas (padrão 0.5s).
 * @return array                 Mapa cpf => resultado de consultaCpfSerpro().
 */
function consultaCpfSerproLote(array $cpfs, $pausaSegundos = 0.5)
{
    $resultados = [];
    $total = count($cpfs);
    $i = 0;
    foreach ($cpfs as $cpf) {
        $i++;
        $resultados[$cpf] = consultaCpfSerpro($cpf);
        if ($i < $total && $pausaSegundos > 0) {
            usleep((int) ($pausaSegundos * 1_000_000));
        }
    }
    return $resultados;
}

// Execução direta via linha de comando (não dispara quando incluído via require).
if (PHP_SAPI === 'cli' && isset($argv[1]) && realpath($argv[0] ?? '') === __FILE__) {
    if ($argv[1] === '--lote') {
        $arquivo = $argv[2] ?? '';
        if (!is_file($arquivo)) {
            fwrite(STDERR, "Arquivo de lote não encontrado: $arquivo\n");
            exit(2);
        }
        $cpfs = array_filter(array_map('trim', file($arquivo, FILE_IGNORE_NEW_LINES)));
        $resultados = consultaCpfSerproLote(array_values($cpfs));
        $saida = [];
        foreach ($resultados as $cpf => $r) {
            $saida[$cpf] = $r['status'] === 'OK'
                ? ['situacao' => $r['situacao_descricao'], 'nome' => $r['dados']['nome'] ?? null]
                : ['erro' => $r['status']];
        }
        echo json_encode($saida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
        exit(0);
    }

    $r = consultaCpfSerpro($argv[1]);
    if ($r['status'] !== 'OK') {
        fwrite(STDERR, "Erro: {$r['status']}\n");
        exit(1);
    }
    if (isset($r['situacao_descricao'])) {
        $r['dados']['situacao_descricao'] = $r['situacao_descricao'];
    }
    echo json_encode($r['dados'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
}
