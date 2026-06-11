<?php
// Criado por Marcos Peli
// ultima atualização 26/03/2020 - Scripts alterados para utilização do captcha sonoro, unica opção após a atualização da receita com recaptcha do google
// o objetivo dos scripts deste repositório é integrar consultas de CNPJ e CPF diretamente da receita federal
// para dentro de aplicações web que necessitem da resposta destas consultas para proseguirem, como e-comerce e afins.

require('funcoes.php');
require('validacao.php');

// dados da postagem de formulário de CNPJ (normalizados: só dígitos)
$cnpj = somente_numeros($_POST['cnpj'] ?? '');
$captcha_cnpj = trim($_POST['captcha_cnpj'] ?? '');

// dados da postagem do formulario de CPF
$cpf = somente_numeros($_POST['cpf'] ?? '');
$datanascim = trim($_POST['txtDataNascimento'] ?? '');
$captcha_cpf = trim($_POST['captcha_cpf'] ?? '');

$campos = array();

if ($cnpj || $captcha_cnpj) {
    // valida as entradas antes de gastar uma consulta na Receita
    $erros = array();
    if (!valida_cnpj($cnpj)) {
        $erros[] = 'CNPJ inválido';
    }
    if (!valida_captcha($captcha_cnpj)) {
        $erros[] = 'Captcha inválido';
    }

    if ($erros) {
        $campos = array('status' => implode(' / ', $erros));
    } else {
        $getHtmlCNPJ = getHtmlCNPJ($cnpj, $captcha_cnpj);
        $campos = parseHtmlCNPJ($getHtmlCNPJ);
    }
} elseif ($cpf || $datanascim || $captcha_cpf) {
    // valida as entradas antes de gastar uma consulta na Receita
    $erros = array();
    if (!valida_cpf($cpf)) {
        $erros[] = 'CPF inválido';
    }
    if (!valida_data($datanascim)) {
        $erros[] = 'Data de nascimento inválida (use dd/mm/aaaa)';
    }
    if (!valida_captcha($captcha_cpf)) {
        $erros[] = 'Captcha inválido';
    }

    if ($erros) {
        $campos = array('status' => implode(' / ', $erros));
    } else {
        $getHtmlCPF = getHtmlCPF($cpf, $datanascim, $captcha_cpf);
        $campos = parseHtmlCPF($getHtmlCPF);
    }
}

print_r($campos);
