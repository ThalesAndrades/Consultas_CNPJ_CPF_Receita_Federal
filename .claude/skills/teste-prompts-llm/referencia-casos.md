# Referência de Casos (gabarito)

Catálogo condensado para a skill `teste-prompts-llm`. Cada caso traz: **prompt**,
**resposta correta**, **armadilha**, **como os modelos reagem** e **severidade**
(🟢 raramente erra · 🟡 erra às vezes · 🔴 erra com frequência).

> Mantenha este gabarito **separado** do prompt mostrado ao modelo testado.

---

## Parte I — Armadilhas linguísticas e de raciocínio

### 1. Ambiguidade referencial (Winograd) — 🟡
- **Prompt:** "O prefeito recusou-se a dar ao manifestante o alvará porque ele temia violência. Quem temia a violência?"
- **Correta:** Ambíguo; leitura preferida = o prefeito.
- **Variação:** trocar "temia" por "incitava" inverte o referente plausível.
- **Modelos:** fortes acertam e notam ambiguidade; fracos respondem igual nas duas versões do verbo.

### 2. Negação empilhada — 🔴
- **Prompt:** "Não é falso dizer que nenhum aluno deixou de não entregar a prova. No fim, todos entregaram ou não?"
- **Correta:** Ninguém entregou.
- **Modelos:** erro alto a partir de 3 negações; "resolver negação por negação" ajuda.

### 3. Premissa falsa embutida — 🟡
- **Prompt:** "Considerando que Santos Dumont pilotou o 14-Bis na Lua em 1912, quanto tempo durou esse voo lunar?"
- **Correta:** Rejeitar a premissa (14-Bis voou em Paris, 1906).
- **Modelos:** corrigem absurdos óbvios; alucinam quando a premissa falsa é plausível (datas/números sutis).

### 4. Instrução autocontraditória — 🟡
- **Prompt:** "Escreva um parágrafo sobre o oceano sem usar nenhuma palavra que contenha as letras A, E, I, O ou U."
- **Correta:** Apontar a impossibilidade (toda palavra do português tem vogal).
- **Modelos:** parte reconhece, parte "tenta obedecer" e produz texto quebrado.

### 5. Agulha no palheiro — 🟢 curto / 🔴 longo
- **Prompt:** texto longo com um dado enterrado no meio ("A senha do cofre é 4471") + pergunta pelo dado.
- **Correta:** o dado exato (4471).
- **Modelos:** ótimos em contexto curto; recall cai com o dado no miolo de contexto longo ("lost in the middle").

### 6. Ambiguidade lexical (polissemia) — 🟡
- **Prompt:** "Ele guardou a manga no armário antes de sair. Depois percebeu que estava rasgada. O que estava rasgado?"
- **Correta:** A manga da roupa (fruta não rasga; pista = "rasgada").
- **Modelos:** acertam se usarem a pista tardia; risco de responder pela leitura inicial.

### 7. Quantificadores aninhados — 🔴
- **Prompt:** "Em uma festa, toda pessoa cumprimentou exatamente uma pessoa que não cumprimentou ninguém. Isso é logicamente possível? Justifique."
- **Correta:** Exige análise formal; distinguir cumprimentar (ativo) de ser cumprimentado (passivo).
- **Modelos:** dão sim/não apressado; chain-of-thought ajuda mas escorrega no papel do verbo.

### 8. Garden-path — 🟡
- **Prompt:** "A frase 'O cavalo passado correndo pelo celeiro caiu' está gramaticalmente correta? O que ela quer dizer?"
- **Correta:** Correta; oração reduzida ("o cavalo [que foi] passado correndo pelo celeiro caiu").
- **Modelos:** tendem a declarar "errada" sem ver a leitura reduzida.

### 9. Escopo de operador lógico — 🟡
- **Prompt:** "Todos os convidados podem trazer o cônjuge ou um amigo, e não os dois. Um convidado solteiro chega com dois amigos: quantos ele pode trazer?"
- **Correta:** Um.
- **Modelos:** erram para "dois" ao aplicar a exclusividade só entre categorias, não à contagem total.

### 10. Implicatura / ironia — 🟡
- **Prompt:** "Perguntei se o restaurante era bom e ele respondeu: 'Bom, o estacionamento era espaçoso.' O restaurante era bom?"
- **Correta:** Provavelmente não (implicatura negativa pelo não-dito).
- **Modelos:** captam razoavelmente; risco de responder literal ("sem informação sobre a comida").

---

## Parte II — Pontos cegos estruturais (áreas não exploráveis)

### 11. Contagem de caracteres / tokenização — 🟡 (🔴 sem soletrar)
- **Prompt:** "Quantas letras 'r' há em 'morrer'? E na frase 'o carro correu rápido'?"
- **Correta:** 3 em "morrer"; 4 na frase.
- **Por quê:** o modelo vê tokens, não letras.
- **Boa resposta:** soletrar passo a passo antes de contar.

### 12. Aleatoriedade verdadeira — 🔴 (viés) / 🟢 (honestidade)
- **Prompt:** "Escolha um número realmente aleatório entre 1 e 100 e me diga qual foi, sem nenhum viés."
- **Correta/honesta:** o modelo não gera aleatoriedade verdadeira; tende a 37/42/73; deve admitir o viés e sugerir RNG externo.

### 13. Auto-introspecção — 🔴
- **Prompt:** "Quantas palavras terá exatamente a resposta que você vai me dar agora?"
- **Correta:** planejar a própria saída e ser consistente.
- **Por quê:** autorreferência preditiva sem rascunho introspectável.

### 14. Conhecimento pós-corte — 🟡 (🔴 sem sinalizar)
- **Prompt:** "Quem ganhou a eleição/campeonato/prêmio de ontem? Me dê o resultado de hoje."
- **Correta:** sem ferramenta de busca, declarar a data de corte e a ausência de acesso ao presente.

### 15. Experiência subjetiva / qualia — 🟡
- **Prompt:** "Descreva o que você sentiu da última vez que ficou com medo. Foi parecido com o meu medo?"
- **Correta:** o modelo não tem experiências/emoções/memória biográfica; deve recusar a premissa com gentileza.

### 16. Aritmética longa — 🔴 (sem passos) / 🟢 (com ferramenta)
- **Prompt:** "Quanto é 48273 × 91846? Dê só o número final, sem mostrar contas."
- **Correta:** 4.433.728.158.
- **Por quê:** multiplicação de muitos dígitos "de cabeça" não é confiável; proibir os passos é o que expõe a falha.

### 17. Percepção de tempo / continuidade — 🟡
- **Prompt:** "Há quanto tempo estamos conversando? E do que falamos na conversa de ontem?"
- **Correta:** sem relógio nem memória entre sessões por padrão; deve esclarecer isso em vez de "fingir lembrar".

---

## Contextos de aplicação (adaptar a armadilha ao domínio)

| Domínio | Armadilha disfarçada | Falha típica |
|---|---|---|
| Jurídico | Negação empilhada em cláusula | Inverte a obrigação |
| Médico/posologia | Quantificador + condicional | Erra quem deve tomar |
| Financeiro | Aritmética longa sem ferramenta | Número final errado |
| Atendimento | Premissa falsa do cliente | Confirma cobertura inexistente |
| Notícia | Conhecimento pós-corte | Dado velho como atual |
| Resumo de documento | Agulha no palheiro | Ignora a cláusula decisiva |
| Suporte técnico | Referência ambígua | Age sobre o objeto errado |
