<?php
// Testes das funções de validação/sanitização.
// Execução: php tests/ValidacaoTest.php
// Não depende de PHPUnit nem de rede — roda em qualquer instalação PHP.

require __DIR__ . '/../validacao.php';

$falhas = 0;
$total = 0;

function checa($descricao, $esperado, $obtido)
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) {
        echo "  ok  - $descricao\n";
    } else {
        $falhas++;
        echo "FALHA - $descricao (esperado " . var_export($esperado, true)
            . ", obtido " . var_export($obtido, true) . ")\n";
    }
}

echo "somente_numeros:\n";
checa('remove máscara de CPF', '12345678909', somente_numeros('123.456.789-09'));
checa('remove máscara de CNPJ', '11222333000181', somente_numeros('11.222.333/0001-81'));

echo "valida_cpf:\n";
checa('CPF válido com máscara', true, valida_cpf('529.982.247-25'));
checa('CPF válido sem máscara', true, valida_cpf('52998224725'));
checa('CPF com dígito verificador errado', false, valida_cpf('529.982.247-24'));
checa('CPF com tamanho errado', false, valida_cpf('1234567890'));
checa('CPF com todos dígitos iguais', false, valida_cpf('111.111.111-11'));
checa('CPF vazio', false, valida_cpf(''));

echo "valida_cnpj:\n";
checa('CNPJ válido com máscara', true, valida_cnpj('11.222.333/0001-81'));
checa('CNPJ válido sem máscara', true, valida_cnpj('11222333000181'));
checa('CNPJ com dígito verificador errado', false, valida_cnpj('11.222.333/0001-80'));
checa('CNPJ com tamanho errado', false, valida_cnpj('112223330001'));
checa('CNPJ com todos dígitos iguais', false, valida_cnpj('00000000000000'));

echo "valida_data:\n";
checa('data válida', true, valida_data('29/02/2020'));
checa('data inexistente (29/02 ano não bissexto)', false, valida_data('29/02/2019'));
checa('dia inexistente', false, valida_data('31/04/2021'));
checa('formato errado', false, valida_data('2021-04-30'));
checa('vazio', false, valida_data(''));

echo "valida_captcha:\n";
checa('captcha alfanumérico de 6', true, valida_captcha('a1B2c3'));
checa('captcha curto', false, valida_captcha('abc'));
checa('captcha com símbolo', false, valida_captcha('abc!23'));

echo "\n";
if ($falhas === 0) {
    echo "Todos os $total testes passaram.\n";
    exit(0);
}
echo "$falhas de $total testes falharam.\n";
exit(1);
