<?php
// Funções de validação e sanitização de entradas (CPF, CNPJ e data).
// Objetivo: evitar consultas inúteis à Receita e mitigar injeções, conforme
// recomendado no README e nos comentários dos demais arquivos do projeto.

/**
 * Remove tudo que não for dígito de uma string.
 * Útil para normalizar CPF/CNPJ digitados com pontos, hífens e barras.
 */
function somente_numeros($valor)
{
    return preg_replace('/\D/', '', (string) $valor);
}

/**
 * Valida um CPF conferindo os dígitos verificadores.
 * Aceita o número com ou sem máscara (ex.: 123.456.789-09 ou 12345678909).
 *
 * @return bool true se o CPF for válido.
 */
function valida_cpf($cpf)
{
    $cpf = somente_numeros($cpf);

    // precisa ter 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }

    // rejeita sequências repetidas (000..., 111..., etc.), que passam no
    // cálculo dos dígitos mas não são CPFs válidos
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    // calcula e confere os dois dígitos verificadores
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int) $cpf[$t] != $digito) {
            return false;
        }
    }

    return true;
}

/**
 * Valida um CNPJ conferindo os dígitos verificadores.
 * Aceita o número com ou sem máscara (ex.: 11.222.333/0001-81 ou 11222333000181).
 *
 * @return bool true se o CNPJ for válido.
 */
function valida_cnpj($cnpj)
{
    $cnpj = somente_numeros($cnpj);

    // precisa ter 14 dígitos
    if (strlen($cnpj) != 14) {
        return false;
    }

    // rejeita sequências repetidas
    if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
        return false;
    }

    // pesos para o primeiro e o segundo dígito verificador
    $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    foreach ([[12, $pesos1], [13, $pesos2]] as $par) {
        list($tamanho, $pesos) = $par;
        $soma = 0;
        for ($i = 0; $i < $tamanho; $i++) {
            $soma += (int) $cnpj[$i] * $pesos[$i];
        }
        $resto = $soma % 11;
        $digito = ($resto < 2) ? 0 : 11 - $resto;
        if ((int) $cnpj[$tamanho] != $digito) {
            return false;
        }
    }

    return true;
}

/**
 * Valida uma data no formato dd/mm/aaaa, garantindo que ela exista de fato
 * no calendário (ex.: rejeita 31/02/2020).
 *
 * @return bool true se a data for válida.
 */
function valida_data($data)
{
    if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', (string) $data, $m)) {
        return false;
    }
    list(, $dia, $mes, $ano) = $m;
    return checkdate((int) $mes, (int) $dia, (int) $ano);
}

/**
 * Valida o texto digitado para o captcha: apenas letras e números, com o
 * comprimento esperado pela Receita (6 caracteres).
 *
 * @return bool true se o captcha tiver o formato esperado.
 */
function valida_captcha($captcha)
{
    return (bool) preg_match('/^[A-Za-z0-9]{6}$/', (string) $captcha);
}
