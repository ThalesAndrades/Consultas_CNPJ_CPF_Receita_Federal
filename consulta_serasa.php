<?php
// Cliente base da API B2B do Serasa Experian — consulta de crédito (Score / Relatórios).
//
// ATENÇÃO — natureza do uso:
// Esta API consulta dados de crédito de TERCEIROS (clientes da sua empresa). NÃO é um
// "direito de acesso" como a consulta do próprio CPF: é um serviço contratado que exige,
// para CADA consulta, uma BASE LEGAL da LGPD — tipicamente análise de risco/concessão de
// crédito (LGPD art. 7º; Lei do Cadastro Positivo 12.414/2011). Consultar sem finalidade
// legítima é irregular mesmo com contrato. Registre a finalidade do tratamento.
//
// Pré-requisitos (não é self-service):
//   - Contrato com o Serasa Experian (ou distribuidor autorizado).
//   - clientId, usuário e senha fornecidos no contrato, e os produtos habilitados.
//
// Configuração por variáveis de ambiente (nunca deixe credenciais no código):
//   SERASA_CLIENT_ID   clientId do contrato (vai na query string do login)
//   SERASA_USUARIO     usuário de integração
//   SERASA_SENHA       senha de integração
//   SERASA_BASE_URL    (opcional) base da API (padrão: https://api.serasaexperian.com.br)
//   SERASA_MAX_RETRIES (opcional) tentativas em falha transitória (padrão 3)
//
// IMPORTANTE: a camada de AUTENTICAÇÃO abaixo segue o fluxo público documentado
// (login -> token). Os PAYLOADS/CAMPOS de cada produto (Score, Relatório PJ) ficam na
// documentação fechada do portal do contrato e variam por contrato — os métodos de
// consulta abaixo são ESQUELETOS: confirme endpoint, corpo e parsing na sua doc antes
// de usar em produção. Os pontos a ajustar estão marcados com "AJUSTAR:".
//
// Uso (função): require 'consulta_serasa.php';
//               $t = serasaLogin(); if ($t['status']==='OK') { ... }

const SERASA_BASE_URL_PADRAO = 'https://api.serasaexperian.com.br';
const SERASA_LOGIN_PATH      = '/security/iam/v1/user-identities/login';

/**
 * Base da API, configurável por ambiente (sem barra final).
 */
function serasaBaseUrl()
{
    return rtrim(getenv('SERASA_BASE_URL') ?: SERASA_BASE_URL_PADRAO, '/');
}

/**
 * Executa cURL com retry/backoff em falhas transitórias (429, 5xx, conexão).
 *
 * @return array ['resp' => string|false, 'code' => int, 'err' => string]
 */
function serasaRequestComRetry(callable $configura, $maxTentativas)
{
    $espera = 1; // segundos; dobra a cada tentativa
    for ($tentativa = 1; ; $tentativa++) {
        $ch = curl_init();
        $configura($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        $transitorio = ($resp === false) || $code === 429 || ($code >= 500 && $code <= 599);
        if (!$transitorio || $tentativa >= $maxTentativas) {
            return ['resp' => $resp, 'code' => $code, 'err' => $err];
        }
        sleep($espera);
        $espera = min($espera * 2, 60); // teto de 60s para o backoff
    }
}

/**
 * Autentica no Serasa Experian e devolve um token de acesso.
 *
 * Fluxo público documentado: POST no endpoint de login com clientId na query string e
 * header Authorization = base64("usuario:senha"). Retorna um token usado nas consultas.
 *
 * @return array ['status' => 'OK', 'token' => string, 'bruto' => array] ou ['status' => erro]
 */
function serasaLogin($clientId = null, $usuario = null, $senha = null, $maxTentativas = null)
{
    $clientId      = $clientId      ?? getenv('SERASA_CLIENT_ID');
    $usuario       = $usuario       ?? getenv('SERASA_USUARIO');
    $senha         = $senha         ?? getenv('SERASA_SENHA');
    $maxTentativas = $maxTentativas ?? max(1, (int) (getenv('SERASA_MAX_RETRIES') ?: 3));

    if (!$clientId || !$usuario || !$senha) {
        return ['status' => 'Defina SERASA_CLIENT_ID, SERASA_USUARIO e SERASA_SENHA (credenciais do contrato Serasa)'];
    }

    $url   = serasaBaseUrl() . SERASA_LOGIN_PATH . '?clientId=' . urlencode($clientId);
    $basic = base64_encode($usuario . ':' . $senha);

    $r = serasaRequestComRetry(function ($ch) use ($url, $basic) {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $basic,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
    }, $maxTentativas);

    if ($r['resp'] === false) {
        return ['status' => 'Falha de conexão no login: ' . $r['err']];
    }
    if ($r['code'] === 401 || $r['code'] === 403) {
        return ['status' => 'Credenciais recusadas (HTTP ' . $r['code'] . '). Verifique clientId/usuário/senha.'];
    }
    $json = json_decode($r['resp'], true);
    if ($r['code'] !== 200 || !is_array($json)) {
        return ['status' => 'Falha no login (HTTP ' . $r['code'] . ')'];
    }

    // AJUSTAR: o nome do campo do token varia por contrato (ex.: 'token', 'accessToken',
    // 'access_token'). Confirme na doc do seu contrato. Abaixo tentamos os mais comuns.
    $token = $json['token'] ?? $json['accessToken'] ?? $json['access_token'] ?? null;
    if (!$token) {
        return ['status' => 'Login OK mas token não localizado na resposta — ajuste o campo do token', 'bruto' => $json];
    }
    return ['status' => 'OK', 'token' => $token, 'bruto' => $json];
}

/**
 * ESQUELETO — Consulta de Score (0 a 1000) de um CPF ou CNPJ.
 *
 * AJUSTAR: endpoint, corpo da requisição e parsing da resposta conforme a doc do contrato.
 * Os nomes abaixo são placeholders prováveis, não confirmados.
 *
 * @param string $documento  CPF ou CNPJ (só dígitos).
 * @param string $token       Token obtido em serasaLogin().
 * @return array              ['status' => 'OK', 'dados' => array] ou ['status' => erro]
 */
function serasaConsultaScore($documento, $token, $maxTentativas = null)
{
    $documento     = preg_replace('/\D/', '', (string) $documento);
    $maxTentativas = $maxTentativas ?? max(1, (int) (getenv('SERASA_MAX_RETRIES') ?: 3));

    if ($documento === '') {
        return ['status' => 'Documento (CPF/CNPJ) é obrigatório'];
    }

    // AJUSTAR: caminho real do produto Score contratado.
    $url = serasaBaseUrl() . '/credit-services/score/v1/scores';

    // AJUSTAR: corpo conforme a doc (tipo de pessoa, código do produto, etc.).
    $body = json_encode([
        'document' => $documento,
        // 'personType' => strlen($documento) === 14 ? 'LEGAL' : 'NATURAL',
    ]);

    $r = serasaRequestComRetry(function ($ch) use ($url, $token, $body) {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            // AJUSTAR: alguns produtos usam 'Authorization: Bearer <token>'; outros um header próprio.
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
    }, $maxTentativas);

    return serasaTrataResposta($r, 'Score');
}

/**
 * ESQUELETO — Relatório PJ/PF (restrições, pendências, dados cadastrais).
 *
 * AJUSTAR: endpoint, corpo e parsing conforme a doc (ex.: Relatório Básico PJ, RE02).
 *
 * @param string $documento  CPF ou CNPJ (só dígitos).
 * @param string $token       Token obtido em serasaLogin().
 * @return array              ['status' => 'OK', 'dados' => array] ou ['status' => erro]
 */
function serasaConsultaRelatorio($documento, $token, $maxTentativas = null)
{
    $documento     = preg_replace('/\D/', '', (string) $documento);
    $maxTentativas = $maxTentativas ?? max(1, (int) (getenv('SERASA_MAX_RETRIES') ?: 3));

    if ($documento === '') {
        return ['status' => 'Documento (CPF/CNPJ) é obrigatório'];
    }

    // AJUSTAR: caminho real do relatório contratado (e o código de transação, ex. RE02).
    $url = serasaBaseUrl() . '/credit-services/reports/v1/reports';

    $body = json_encode([
        'document' => $documento,
        // 'reportCode' => 'RE02',
    ]);

    $r = serasaRequestComRetry(function ($ch) use ($url, $token, $body) {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
    }, $maxTentativas);

    return serasaTrataResposta($r, 'Relatório');
}

/**
 * Trata a resposta de um produto, padronizando o retorno e os erros por código HTTP.
 */
function serasaTrataResposta(array $r, $produto)
{
    if ($r['resp'] === false) {
        return ['status' => "Falha de conexão na consulta de $produto: " . $r['err']];
    }
    $dados = json_decode($r['resp'], true);
    $code  = $r['code'];

    if ($code === 200 || $code === 201) {
        return ['status' => 'OK', 'http' => $code, 'dados' => $dados];
    }
    if ($code === 401 || $code === 403) {
        return ['status' => "Não autorizado em $produto (HTTP $code). Token expirado ou produto não habilitado no contrato."];
    }
    if ($code === 404) {
        return ['status' => "Documento não encontrado para $produto (HTTP 404)"];
    }
    if ($code === 422) {
        return ['status' => "Requisição inválida em $produto (HTTP 422). Verifique o corpo enviado.", 'dados' => $dados];
    }
    if ($code === 429) {
        return ['status' => "Limite de requisições excedido em $produto (HTTP 429) após retries."];
    }
    $msg = is_array($dados) && isset($dados['message']) ? $dados['message'] : 'HTTP ' . $code;
    return ['status' => "Retorno inesperado em $produto: " . $msg, 'http' => $code, 'dados' => $dados];
}
