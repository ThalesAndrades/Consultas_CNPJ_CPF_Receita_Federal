# Consultas_CNPJ_CPF_Receita_Federal
Consulta CNPJ e CPF na Receita com Captcha

##  Utilização

###  index.php

Basta digitar os campos CNPJ + Captcha , ou CPF + Data de Nascimanto + Captcha Para consulta os registros na Receita Federal

Especial atenção para a pasta cookies, pois é lá que serão salvos os cookies de sessão com a Receita Federal. A constante COOKIELOCAL devem apontar para a sua localização.

##  Recomendações

Ao Utilizar esta solução em sua aplicação, recomendo o tratamento dos campos de formulário $_GET , $_POST ,afim de evitar possiveis injections em suas aplicações

##  Validação de entradas

O arquivo `validacao.php` traz funções para tratar e validar as entradas antes de
consultar a Receita, reduzindo consultas inúteis e mitigando injeções:

- `somente_numeros($valor)` — remove máscara (pontos, hífens, barras).
- `valida_cpf($cpf)` — confere os dígitos verificadores do CPF.
- `valida_cnpj($cnpj)` — confere os dígitos verificadores do CNPJ.
- `valida_data($data)` — valida data `dd/mm/aaaa` existente no calendário.
- `valida_captcha($captcha)` — confere o formato alfanumérico de 6 caracteres.

O `processa.php` já usa essas funções: entradas inválidas retornam um `status`
descritivo sem chamar a Receita.

##  Testes

Os testes não dependem de PHPUnit nem de acesso à rede:

```
php tests/ValidacaoTest.php
```

## Autor

Marcos Peli: [facebook.com/pelimarcos][facebook]

## Licensa

Licensa [MIT][mit]. Aproveite

[facebook]: https://www.facebook.com/pelimarcos
[mit]: http://www.opensource.org/licenses/mit-license.php

