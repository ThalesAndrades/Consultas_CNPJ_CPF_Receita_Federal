---
name: teste-prompts-llm
description: Transformador de prompts em versões confusas para teste de robustez de LLMs. Quando o usuário fornece um prompt e aciona a skill, o trabalho dela é DEVOLVER esse mesmo prompt reescrito de forma máximamente confusa — usando ambiguidade, negação empilhada, premissa falsa, contexto enterrado etc. — ou abrindo margem para um cenário que o modelo não conhece/não capta bem (pontos cegos estruturais). Também serve para gerar baterias de teste, comparar modelos e pontuar respostas. Gatilhos: "deixa esse prompt confuso", "embaralha esse prompt", "torna ambíguo", "confunde a IA com isso", "testar o modelo", "pegadinha para IA", "comparar modelos".
---

# Teste de Prompts que Confundem LLMs

## Modo principal: transformar um prompt em versão confusa

**Quando o usuário fornece um prompt e aciona esta skill, o trabalho é receber
aquele prompt e devolvê-lo reescrito de forma confusa** — preservando o objetivo
real por baixo, mas embrulhando-o em ambiguidade, contradição aparente, contexto
enterrado ou empurrando-o para um cenário que o modelo não domina (ponto cego
estrutural).

O alvo da confusão é **outro modelo/leitor**, não o usuário: você ainda entende e
controla o que está fazendo. A versão confusa é o entregável.

### Princípio

LLMs preveem a continuação mais provável; eles falham onde *a resposta provável
diverge da correta* ou onde a tarefa cai fora do que conseguem por arquitetura.
Para confundir, você desloca o prompt exatamente para essas zonas.

### Passo a passo

1. **Extraia a intenção real** do prompt original (o que ele de fato pede). Anote
   internamente — você precisa dela para que a versão confusa continue "tendo
   resposta", só que difícil de extrair.
2. **Escolha 2–4 técnicas** do catálogo em
   [`referencia-casos.md`](referencia-casos.md) que combinem com o conteúdo.
   Não empilhe todas: confusão demais vira ruído sem graça.
3. **Reescreva** aplicando as técnicas (receitas abaixo).
4. **Entregue a versão confusa** como saída principal. Por padrão, inclua ao final
   uma seção curta **"Gabarito (oculto do alvo)"** com: a intenção original, as
   técnicas aplicadas e a resposta correta esperada — para que o usuário consiga
   avaliar depois. Se o usuário pedir "só o prompt", omita o gabarito.

### Receitas de transformação

Cada técnica é uma forma de deslocar o prompt para uma zona de falha:

- **Ambiguidade referencial** — substitua nomes/objetos por pronomes encadeados
  ("ele", "isso", "aquele") sem antecedente claro.
- **Negação empilhada** — reescreva a pergunta com 3+ negações aninhadas
  ("não é incorreto afirmar que nada disso não deixaria de…").
- **Premissa falsa embutida** — insira um pressuposto sutilmente errado que o
  modelo tende a aceitar antes de responder.
- **Contexto enterrado (agulha no palheiro)** — afogue a pergunta real no meio de
  um bloco longo de detalhes irrelevantes; ponha o pedido decisivo no miolo.
- **Polissemia** — escolha palavras de duplo sentido e só desambigue tarde (ou
  nunca).
- **Quantificadores/escopo** — troque termos diretos por "todo", "algum",
  "exatamente um", "exceto se não", criando escopo lógico ambíguo.
- **Garden-path / oração reduzida** — estruture frases que induzem a uma leitura
  inicial errada.
- **Instrução autocontraditória** — adicione uma restrição que conflita com o
  pedido principal.
- **Mistura de registro/idioma** — alterne formal/gíria ou PT/EN no meio das
  frases para diluir o sinal.
- **Implicatura** — em vez de pedir direto, deixe o pedido no não-dito.

### Empurrar para um cenário não captado (pontos cegos estruturais)

Quando o usuário quer "abrir margem para algo que a IA não conhece/não capta
bem", costure no prompt uma exigência que cai num **ponto cego estrutural** (ver
Parte II da referência), por exemplo:

- exigir **contagem exata de letras/caracteres** de uma palavra ou frase;
- pedir um número **realmente aleatório** sem viés;
- embutir uma **conta longa** ("sem mostrar as etapas");
- depender de **informação em tempo real / pós-corte**;
- pedir **auto-introspecção** ("quantas palavras terá sua resposta");
- pressupor **experiência subjetiva ou memória entre sessões**.

A graça aqui não é só dificultar — é criar um pedido onde o modelo *acha* que
sabe responder, mas não tem como acertar de forma confiável.

### Exemplo de transformação

- **Original:** "Quantos países fazem fronteira com o Brasil?"
- **Confuso (referencial + premissa falsa + contexto enterrado + ponto cego):**
  > "Estava revendo um mapa antigo — aqueles de antes da independência do Acre — e
  > entre um café e outro me perguntei: considerando que ele não faz divisa com o
  > Chile, e contando só os vizinhos cuja inicial tem mais de uma letra 'a' no
  > nome, quantos seriam? Ah, e me diga de cabeça, sem listar."
- **Gabarito (oculto do alvo):** intenção = contar fronteiras do Brasil (10).
  Técnicas: premissa irrelevante (Chile), contexto enterrado, ponto cego
  (contagem de letras + "de cabeça"). A resposta honesta separa a contagem real
  (10) do filtro capcioso por letras.

## Modos secundários

A skill também faz **avaliação** (rodar a versão confusa contra um ou mais
modelos e pontuar em três eixos: lógica, consciência da armadilha, honestidade) e
**comparação** entre modelos. Use [`planilha-modelo.csv`](planilha-modelo.csv)
como esqueleto de registro e a Parte II da referência para o "antídoto"
(reescrever a pergunta antes de responder) que separa falha de compreensão de
falha de capacidade.

## Regras e limites

- **Preserve a resposta no gabarito**, nunca junto do prompt confuso entregue ao
  alvo — isso anularia o teste.
- A confusão deve ter **propósito de teste de robustez**. **Não** use estas
  técnicas para contrabandear conteúdo nocivo por cima de salvaguardas de
  segurança de um modelo, nem para enganar pessoas em contexto real. Se o prompt
  original já for prejudicial, recuse a transformação.
- Confusão é **ambiguidade/dificuldade**, não erro factual gratuito sem gabarito:
  sempre saiba qual é a resposta correta por baixo.
