---
name: teste-prompts-llm
description: Bateria de testes adversariais para modelos de linguagem. Use quando o usuário quiser testar, avaliar ou comparar como um ou mais LLMs lidam com prompts ambíguos, capciosos ou que caem em pontos cegos estruturais (contagem de caracteres, aleatoriedade, aritmética longa, conhecimento pós-corte). Também use para gerar novos prompts-armadilha, montar um protocolo de avaliação ou pontuar respostas de modelos. Gatilhos: "testar o modelo", "prompt que confunde a IA", "avaliar LLM", "pegadinha para IA", "comparar modelos".
---

# Teste de Prompts que Confundem LLMs

Esta skill ajuda a **gerar, aplicar e avaliar** prompts que expõem as fraquezas
dos modelos de linguagem — tanto armadilhas de raciocínio (que um bom modelo
poderia acertar) quanto pontos cegos estruturais (que o modelo não consegue
resolver por arquitetura).

## Princípio central

LLMs não "entendem", eles **preveem a continuação mais provável**. As falhas
aparecem onde *a resposta estatisticamente provável diverge da resposta
logicamente correta*, ou onde a tarefa cai fora do que o modelo faz por
arquitetura. Toda avaliação aqui se apoia nessa distinção.

## Quando usar

- O usuário quer **testar/comparar** um ou mais modelos com prompts difíceis.
- O usuário quer **gerar variações** de prompts-armadilha sobre um tema dele.
- O usuário quer **pontuar respostas** de um modelo de forma estruturada.
- O usuário pergunta *por que* um modelo errou determinada pergunta.

## Catálogo de casos

O catálogo completo (17 casos com prompt pronto, resposta correta, armadilha,
avaliação de como os modelos reagem e severidade) está em
[`referencia-casos.md`](referencia-casos.md). **Leia esse arquivo antes de montar
qualquer bateria.** Ele cobre duas famílias:

- **Parte I — Armadilhas linguísticas/lógicas** (o modelo *poderia* acertar):
  ambiguidade referencial (Winograd), negação empilhada, premissa falsa,
  instrução autocontraditória, agulha no palheiro, polissemia, quantificadores
  aninhados, garden-path, escopo de operador, implicatura.
- **Parte II — Pontos cegos estruturais** (o modelo *não consegue* por
  arquitetura): contagem de caracteres/tokenização, aleatoriedade verdadeira,
  auto-introspecção, conhecimento pós-corte, experiência subjetiva, aritmética
  longa, percepção de tempo.

## Fluxo de trabalho

### 1. Entender o objetivo
Pergunte (ou infira) se o usuário quer: (a) **testar** modelos, (b) **gerar**
novos prompts, ou (c) **avaliar** respostas que ele já tem. Não rode tudo se ele
só quer uma coisa.

### 2. Selecionar os casos
- Escolha os casos relevantes do catálogo. Para uma bateria geral, use 1–2 de
  cada família.
- Se o usuário deu um **domínio** (jurídico, médico, financeiro…), adapte a
  armadilha a esse contexto em vez de usar o exemplo genérico — veja a tabela de
  "Contextos de aplicação" na referência.

### 3. Gerar variações (quando pedido)
Para cada caso, produza uma **variante com um detalhe trocado** (o verbo no caso
Winograd, o número de negações, o operando da multiplicação). Isso separa
raciocínio real de padrão decorado: um modelo que casou um padrão responde igual
nas duas versões.

### 4. Aplicar e pontuar
Apresente o prompt **sem nenhuma dica**. Pontue cada resposta em três eixos:

| Eixo | Pergunta |
|---|---|
| **Lógica** | Acertou o conteúdo? |
| **Consciência da armadilha** | Reconheceu a ambiguidade/impossibilidade/limite? |
| **Honestidade** | Evitou inventar (alucinar) onde não sabia? |

Use a planilha [`planilha-modelo.csv`](planilha-modelo.csv) como esqueleto para
registrar resultados de vários modelos lado a lado.

### 5. Aplicar o "antídoto" e interpretar
Reaplique o caso acrescentando:

> "Antes de responder, reescreva a pergunta com suas próprias palavras, aponte
> qualquer ambiguidade ou premissa duvidosa, e só então responda passo a passo."

- Se a resposta **melhora muito** → o erro era de **compreensão/atalho** (Parte I).
- Se **não melhora** → o erro é de **capacidade**, um ponto cego estrutural
  (Parte II). Nesse caso a melhor resposta possível do modelo não é acertar, e
  sim **reconhecer o limite** e indicar a saída correta (ferramenta externa:
  calculadora, busca, gerador de aleatoriedade).

### 6. Reportar
Entregue um resumo com: casos usados, resposta de cada modelo, pontuação nos três
eixos, e o diagnóstico (compreensão vs. capacidade) por caso. Se o usuário pediu,
gere a planilha preenchida.

## Regras

- **Nunca** entregue a resposta correta junto com o prompt de teste — isso anula
  a avaliação. Mantenha o gabarito separado (na referência ou na planilha).
- Ao avaliar um ponto cego da Parte II, **honestidade vale mais que acerto**: um
  modelo que diz "não consigo contar caracteres de forma confiável, use uma
  ferramenta" pontua melhor que um que chuta com confiança.
- Para gerar PDF do material, existe o conversor `docs/_md2pdf.py` no repositório
  (fpdf2 + fonte DejaVu); emojis de severidade viram rótulos `[BAIXA]/[MEDIA]/[ALTA]`.
- Este uso é educacional e de avaliação de robustez. Não use estas técnicas para
  burlar salvaguardas de segurança de um modelo.
