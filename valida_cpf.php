<?php
// Validação de CPF (dígitos verificadores) — 100% offline, sem consultar dado de ninguém.
//
// IMPORTANTE — leia antes de procurar uma "consulta de CPF por API":
// Não existe API pública que devolva a situação cadastral de um CPF a partir do
// número. A página da Receita usada por este projeto (ConsultaPublicaSonoro.asp)
// foi DESATIVADA (retorna HTTP 404). A consulta de situação cadastral de CPF hoje
// só ocorre dentro do portal gov.br, autenticada pelo próprio titular, e protegida
// por reCAPTCHA — justamente para impedir consulta automatizada de dado pessoal.
//
// Ou seja: pelo CPF, o que se pode fazer em código de forma legítima e automática
// é VALIDAR o formato (os dígitos verificadores), como abaixo. Acessar a situação
// cadastral do SEU CPF é um direito seu, mas o canal é o gov.br autenticado — não
// um endpoint REST. Ver docs/COMO-CONSULTAR.md.
//
// Uso (CLI):    php valida_cpf.php 123.456.789-09
// Uso (função): require 'valida_cpf.php'; $ok = validaCPF('12345678909');

/**
 * Valida um CPF pelos dígitos verificadores.
 *
 * @param string $cpf  CPF com ou sem máscara.
 * @return bool        true se os dígitos verificadores conferem.
 */
function validaCPF($cpf)
{
    $cpf = preg_replace('/\D/', '', (string) $cpf);

    if (strlen($cpf) !== 11) {
        return false;
    }

    // rejeita sequências repetidas (000..., 111..., etc.), que passam na conta mas são inválidas
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    // calcula os dois dígitos verificadores
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int) $cpf[$t] !== $digito) {
            return false;
        }
    }

    return true;
}

// Execução direta via linha de comando.
if (PHP_SAPI === 'cli' && isset($argv[1])) {
    $cpf = $argv[1];
    if (validaCPF($cpf)) {
        echo "CPF $cpf: válido (dígitos verificadores conferem)\n";
        exit(0);
    }
    echo "CPF $cpf: inválido\n";
    exit(1);
}
