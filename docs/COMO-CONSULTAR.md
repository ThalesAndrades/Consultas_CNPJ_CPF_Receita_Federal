# Como consultar CPF e CNPJ por código — situação real (2026)

Resumo honesto do que dá e do que não dá para fazer "via request em código", depois
de testar os endpoints que este repositório usava.

## CNPJ — ✅ tem API JSON, funciona 100% automático

CNPJ é **dado público**. Não precisa de captcha.

- O endpoint web antigo da Receita (`servicos.receita.fazenda.gov.br/.../Cnpjreva_Solicitacao_CS.asp`)
  passou a **redirecionar (HTTP 301)** para `solucoes.receita.fazenda.gov.br` e segue
  protegido por captcha.
- Em vez de raspar essa página, use a **BrasilAPI**, que devolve JSON estruturado:

```bash
php consulta_cnpj.php 00360305000104
```

ou em código:

```php
require 'consulta_cnpj.php';
$resultado = consultaCNPJ('00360305000104');
// $resultado['dados'] -> razão social, situação cadastral, sócios, endereço, CNAEs...
```

Alternativas de API pública de CNPJ: BrasilAPI, ReceitaWS, CNPJá, Minha Receita.

## CPF — ❌ não existe API pública; o scraper antigo morreu

CPF é **dado pessoal protegido (LGPD)**. Por isso não há API aberta que devolva a
situação cadastral a partir do número.

- O endpoint que este repositório usava
  (`servicos.receita.fazenda.gov.br/Servicos/CPF/ConsultaSituacao/ConsultaPublicaSonoro.asp`)
  hoje retorna **HTTP 404** — foi desativado.
- A consulta de situação cadastral de CPF migrou para o portal **gov.br**,
  **autenticada pelo próprio titular** e protegida por **reCAPTCHA**. Não há endpoint
  REST público; o captcha existe justamente para impedir consulta automatizada.

### O que dá para fazer com CPF em código

1. **Validar o CPF** (dígitos verificadores) — offline, sem consultar dado de ninguém:

   ```bash
   php valida_cpf.php 111.444.777-35
   ```

2. **API oficial do Serpro (Consulta CPF)** — a forma legítima e por requisição de
   obter a situação cadastral. É REST/JSON, **sem captcha** (o controle de acesso é o
   contrato + token OAuth2, não o captcha). Ver `consulta_cpf_serpro.php`:

   ```bash
   # Teste no ambiente trial (CPFs de demonstração, não são pessoas reais):
   SERPRO_BEARER="<token-trial>" php consulta_cpf_serpro.php 63017285995

   # Produção (após contratar):
   SERPRO_CONSUMER_KEY=... SERPRO_CONSUMER_SECRET=... SERPRO_CPF_AMBIENTE=producao \
     php consulta_cpf_serpro.php 12345678909
   ```

   Limitação importante: a contratação em produção **exige certificado digital e-CNPJ**
   (uma empresa) e é **paga por volume**. Não há contrato self-service para pessoa
   física sem CNPJ. Docs: https://apicenter.estaleiro.serpro.gov.br/documentacao/consulta-cpf/

3. **Acessar o SEU próprio CPF gratuitamente** — é um direito seu (CDC art. 43,
   Lei 12.414/2011, LGPD art. 18), pelos canais:
   - **página pública da Receita** (`servicos.receita.fazenda.gov.br/servicos/cpf/consultasituacao/consultapublica.asp`)
     — CPF + data de nascimento + **hCaptcha resolvido por você, no navegador**; ou
   - **gov.br autenticado** → "Meu CPF"; ou
   - **requisição formal ao Encarregado (DPO)** da Receita pedindo acesso aos seus dados
     (a lei garante a entrega, não que seja por API).

4. **API paga de terceiro** (Infosimples, cpfcnpj.com.br etc.) — resolvem a integração
   e revendem por request/JSON. Não é oficial e exige confiar o CPF ao intermediário.

### Sobre automatizar o captcha da página pública

O direito de acesso (LGPD/CDC) garante que a Receita te **entregue** seus dados — não
cria direito de **contornar o controle técnico** (o hCaptcha) que protege a página. Um
programa que resolve o captcha sozinho para consultar CPFs é exatamente o vetor de
varredura em massa que essa proteção existe para impedir, e é indistinguível dele —
por isso este repositório não inclui um "quebra-captcha". Para acesso por requisição,
o caminho correto e sem captcha é a **API oficial do Serpro** (item 2). Para o acesso
pessoal gratuito, você resolve o captcha **você mesmo**, no navegador (item 3).

## Por que CNPJ tem API e CPF não

CNPJ é informação empresarial pública. CPF identifica uma pessoa física e é dado
pessoal protegido — abrir uma API "consulte qualquer CPF" seria exatamente o vetor
de varredura que a lei e o captcha procuram impedir.
