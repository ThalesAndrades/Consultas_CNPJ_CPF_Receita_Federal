# Consultas_CNPJ_CPF_Receita_Federal
Consulta CNPJ e CPF na Receita com Captcha

## Aviso importante (2026)

Os scripts originais (`index.php`, `getcaptcha.php`, `processa.php`, `funcoes.php`)
raspavam as páginas da Receita protegidas por captcha. Testando os endpoints hoje:

- **CPF**: a página `ConsultaPublicaSonoro.asp` retorna **HTTP 404** — foi desativada.
  A consulta de situação cadastral de CPF migrou para o **gov.br autenticado** (reCAPTCHA),
  sem endpoint público. O fluxo de CPF deste repositório **não funciona mais**.
- **CNPJ**: o endpoint antigo redireciona (301) para um novo domínio e segue com captcha.

### Alternativas modernas incluídas neste repositório

- **`consulta_cnpj.php`** — consulta CNPJ via API pública (BrasilAPI), JSON, sem captcha:
  ```bash
  php consulta_cnpj.php 00360305000104
  ```
- **`valida_cpf.php`** — valida os dígitos verificadores de um CPF (offline):
  ```bash
  php valida_cpf.php 111.444.777-35
  ```
- **`consulta_cpf_serpro.php`** — cliente da API oficial Serpro "Consulta CPF" (situação
  cadastral por requisição, JSON, sem captcha). Pronto para produção: cache de token (1h),
  retry com backoff, tradução das situações cadastrais e consulta em lote. Em produção
  exige contrato com e-CNPJ; há ambiente trial para testes:
  ```bash
  # uma consulta
  SERPRO_BEARER="<token-trial>" php consulta_cpf_serpro.php 63017285995
  # lote (um CPF por linha)
  SERPRO_CONSUMER_KEY=... SERPRO_CONSUMER_SECRET=... SERPRO_CPF_AMBIENTE=producao \
    php consulta_cpf_serpro.php --lote cpfs.txt
  ```

- **`consulta_serasa.php`** — cliente base da API B2B do **Serasa Experian** (consulta de
  crédito de terceiros: Score 0–1000 e Relatórios PJ/PF). Camada de autenticação
  (login → token) implementada; os payloads de cada produto são esqueletos marcados com
  `AJUSTAR:` para confirmar na doc do contrato. Exige contrato Serasa Experian e base
  legal LGPD por consulta (análise de risco/crédito) — não é consulta do próprio CPF.

Detalhes do que dá e do que não dá para consultar por código em
[`docs/COMO-CONSULTAR.md`](docs/COMO-CONSULTAR.md).

##  Utilização (scripts originais — legado)

###  index.php

Basta digitar os campos CNPJ + Captcha , ou CPF + Data de Nascimanto + Captcha Para consulta os registros na Receita Federal

Especial atenção para a pasta cookies, pois é lá que serão salvos os cookies de sessão com a Receita Federal. A constante COOKIELOCAL devem apontar para a sua localização.

##  Recomendações

Ao Utilizar esta solução em sua aplicação, recomendo o tratamento dos campos de formulário $_GET , $_POST ,afim de evitar possiveis injections em suas aplicações

## Autor

Marcos Peli: [facebook.com/pelimarcos][facebook]

## Licensa

Licensa [MIT][mit]. Aproveite

[facebook]: https://www.facebook.com/pelimarcos
[mit]: http://www.opensource.org/licenses/mit-license.php

