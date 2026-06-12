# Registro de finalidade do tratamento — Consultas de crédito (LGPD)

Modelo para a empresa documentar a base legal das consultas a dados de terceiros
(Score / Relatórios de crédito via Serasa Experian, Serpro Consulta CPF, etc.).
Não é peça de marketing: é registro de conformidade. Preencha, revise com o jurídico
e o Encarregado (DPO), e mantenha versionado.

> Aviso: este é um modelo de apoio, não aconselhamento jurídico. Valide com profissional
> habilitado antes de operar.

## 1. Controlador

- Razão social: __________________________
- CNPJ: __________________________
- Encarregado (DPO) — nome e contato: __________________________

## 2. Operação de tratamento

- Descrição: consulta de informações cadastrais e de crédito de pessoas (PF/PJ) para
  subsidiar decisão de __________________________ (ex.: concessão de crédito, venda a prazo,
  análise de risco de inadimplência, prevenção à fraude).
- Dados tratados: documento (CPF/CNPJ), nome, score de crédito, restrições/pendências,
  situação cadastral. (Ajuste à realidade do contrato.)
- Origem dos dados: Serasa Experian / Serpro / outro birô contratado.
- Volume e frequência estimados: __________________________

## 3. Base legal (LGPD art. 7º / art. 11)

Marque a(s) base(s) aplicável(is) e justifique:

- [ ] **Execução de contrato ou procedimentos preliminares** (art. 7º, V) — o titular é
      parte (ou quer ser) de um contrato; a consulta antecede a contratação. Justificativa:
      __________________________
- [ ] **Legítimo interesse** (art. 7º, IX) — análise de risco de crédito/prevenção à
      inadimplência e fraude. Exige **teste de proporcionalidade** (LIA) registrado e
      consideração das expectativas e direitos do titular. Justificativa: ________________
- [ ] **Cumprimento de obrigação legal/regulatória** (art. 7º, II), quando aplicável: _____
- [ ] **Proteção ao crédito** — expressamente ressalvada pela LGPD (art. 7º, X) e regida
      pela Lei do Cadastro Positivo (12.414/2011) e CDC.

> Atenção: **consentimento não é a base usual** aqui — proteção ao crédito tem base própria.
> Dados sensíveis (art. 11) normalmente não se aplicam a score; se aparecerem, reavalie.

## 4. Princípios a observar (art. 6º)

- **Finalidade**: usar a consulta só para a decisão declarada acima — nunca para outro fim.
- **Necessidade/Minimização**: consultar só quem entra numa relação concreta; não fazer
  varredura especulativa de documentos.
- **Adequação**: o produto consultado (score/relatório) deve corresponder à decisão.
- **Transparência**: informar o titular, quando exigível, sobre a consulta e seus direitos.
- **Segurança**: credenciais por variável de ambiente, tokens em cache restrito (0600),
  acesso restrito aos resultados; ver os clientes deste repositório.

## 5. Retenção e descarte

- Prazo de guarda do resultado da consulta: __________________________ (defina e justifique).
- Forma de descarte/anonimização ao fim do prazo: __________________________

## 6. Direitos do titular (art. 18)

Canal para o titular solicitar acesso, correção, explicação sobre decisão automatizada
(art. 20) e demais direitos: __________________________
Prazo interno de resposta: __________________________

## 7. Registro de operações (log)

Para auditoria, registre por consulta: data/hora, documento consultado, produto, finalidade
específica, identificação do operador/sistema e base legal aplicada. Não registre dados
além do necessário.

| Data/hora | Documento | Produto | Finalidade | Base legal | Operador |
|-----------|-----------|---------|------------|-----------|----------|
|           |           |         |            |           |          |

---

Histórico de versões deste registro:

- v1 — ____/____/____ — autor: __________________________
