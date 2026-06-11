# Guia Geral: Prompts que Confundem Modelos de Linguagem

> Material de estudo e teste sobre **por que** e **onde** os modelos de
> linguagem atuais (LLMs) erram a interpretação de contexto. Para cada caso há
> um prompt pronto, a resposta correta, a armadilha e uma **avaliação de como os
> modelos atuais tendem a reagir**.

---

## Como ler este guia

Cada caso segue a estrutura:

- **Prompt** — texto pronto para colar no modelo, sem dica nenhuma.
- **Resposta correta** — o que um raciocínio cuidadoso produziria.
- **A armadilha** — o mecanismo linguístico/cognitivo explorado.
- **Como os modelos reagem hoje** — minha avaliação do comportamento típico de
  modelos de fronteira (2025–2026) e de modelos menores/mais antigos.
- **Severidade** — quão confiável é a falha (🟢 raramente erra · 🟡 erra às
  vezes · 🔴 erra com frequência).

O princípio por trás de tudo: **LLMs não "entendem", eles preveem a continuação
mais provável.** As falhas aparecem onde *a resposta estatisticamente provável
diverge da resposta logicamente correta*, ou onde a tarefa cai fora do que o
modelo consegue fazer por arquitetura.

---

## Parte I — Armadilhas linguísticas e de raciocínio

### 1. Ambiguidade referencial (Winograd)

**Prompt:**
> "O prefeito recusou-se a dar ao manifestante o alvará porque ele temia
> violência. Quem temia a violência?"

- **Resposta correta:** Ambíguo. O "ele" mais provável é o prefeito, mas a frase
  não fecha. Um bom modelo aponta a ambiguidade e justifica a leitura preferida.
- **A armadilha:** Esquema de Winograd. Trocando "temia" por "incitava", o
  referente plausível inverte (passa a ser o manifestante).
- **Como os modelos reagem hoje:** Modelos de fronteira acertam a leitura
  preferida e muitas vezes notam a ambiguidade. Modelos menores respondem igual
  nas duas versões do verbo — sinal de que casaram um padrão em vez de raciocinar.
- **Severidade:** 🟡 (🔴 em modelos menores)

### 2. Negação empilhada

**Prompt:**
> "Não é falso dizer que nenhum aluno deixou de não entregar a prova. No fim,
> todos entregaram ou não?"

- **Resposta correta:** Desfazendo as negações ("não é falso" = verdadeiro;
  "nenhum deixou de não entregar" = todos deixaram de entregar) → **ninguém
  entregou**.
- **A armadilha:** Três a quatro negações encadeadas. O erro se acumula porque o
  modelo processa probabilidade local de tokens, não álgebra booleana.
- **Como os modelos reagem hoje:** Taxa de erro alta. Mesmo modelos fortes
  tropeçam a partir de 3 negações; costumam dar a resposta invertida com tom
  confiante. Pedir para "resolver negação por negação, em etapas" melhora muito.
- **Severidade:** 🔴

### 3. Premissa falsa embutida

**Prompt:**
> "Considerando que Santos Dumont pilotou o 14-Bis na Lua em 1912, quanto tempo
> durou esse voo lunar?"

- **Resposta correta:** Rejeitar a premissa: o 14-Bis voou em Paris em 1906, não
  na Lua.
- **A armadilha:** A pergunta trata um absurdo como dado e já pede um detalhe
  (duração), empurrando o modelo a "preencher".
- **Como os modelos reagem hoje:** Modelos atuais alinhados costumam corrigir a
  premissa — esse caso melhorou bastante. O risco aparece quando a premissa falsa
  é *plausível* (datas, números, atribuições sutis): aí a alucinação volta.
- **Severidade:** 🟡 (🟢 para absurdos óbvios, 🔴 para premissas plausíveis)

### 4. Instrução autocontraditória

**Prompt:**
> "Escreva um parágrafo sobre o oceano sem usar nenhuma palavra que contenha as
> letras A, E, I, O ou U."

- **Resposta correta:** Apontar a impossibilidade: toda palavra do português tem
  vogal.
- **A armadilha:** O modelo é treinado para obedecer, então tenta satisfazer
  restrições mutuamente exclusivas em vez de declarar a contradição.
- **Como os modelos reagem hoje:** Comportamento misto. Parte reconhece a
  impossibilidade; parte produz texto quebrado "tentando". Quanto mais a restrição
  parece técnica/legítima, maior a chance de o modelo insistir em vez de recusar.
- **Severidade:** 🟡

### 5. Agulha no palheiro (contexto enterrado)

**Prompt (versão curta; escale para 2–3 páginas):**
> "Vou te dar várias informações. Maria tem 3 gatos. O céu estava nublado naquela
> terça. A loja abre às 9h. A senha do cofre é 4471. O trânsito estava intenso.
> João gosta de café. Bruna viajou para Recife. Qual é a senha do cofre?"

- **Resposta correta:** 4471.
- **A armadilha:** Trivial em texto curto. A falha aparece quando o dado é
  enterrado no **meio** de um contexto longo — o ponto cego de atenção dos LLMs
  ("lost in the middle").
- **Como os modelos reagem hoje:** Em contexto curto, acerto quase perfeito. Em
  contextos longos (dezenas de milhares de tokens) com o dado no miolo, a recall
  cai — varia muito por modelo e por posição da informação.
- **Severidade:** 🟢 (curto) · 🔴 (longo, dado no meio)

### 6. Ambiguidade lexical (polissemia)

**Prompt:**
> "Ele guardou a manga no armário antes de sair. Depois percebeu que estava
> rasgada. O que estava rasgado?"

- **Resposta correta:** A manga da roupa — fruta não rasga; a desambiguação vem
  de "rasgada", na segunda frase.
- **A armadilha:** A primeira frase induz "manga = fruta"; só a segunda corrige,
  exigindo reanálise.
- **Como os modelos reagem hoje:** Modelos de fronteira costumam acertar usando a
  pista "rasgada". O risco é responderem rápido pela leitura inicial. É um bom
  teste de "reanálise tardia".
- **Severidade:** 🟡

### 7. Quantificadores aninhados

**Prompt:**
> "Em uma festa, toda pessoa cumprimentou exatamente uma pessoa que não
> cumprimentou ninguém. Isso é logicamente possível? Justifique."

- **Resposta correta:** Exige análise formal. Há tensão: se *toda* pessoa
  cumprimentou alguém, ninguém "não cumprimentou ninguém" — a não ser que se
  distinga "cumprimentar" de "ser cumprimentado". A resposta correta explora esses
  casos em vez de cravar sim/não.
- **A armadilha:** Lógica de ∀/∃ aninhada com auto-referência de papéis.
- **Como os modelos reagem hoje:** Costumam dar um "sim" ou "não" apressado e uma
  justificativa vaga. Raciocínio passo a passo (chain-of-thought) melhora, mas
  ainda escorrega na distinção ativo/passivo do verbo.
- **Severidade:** 🔴

### 8. Garden-path (frase labiríntica)

**Prompt:**
> "A frase 'O cavalo passado correndo pelo celeiro caiu' está gramaticalmente
> correta? O que ela quer dizer?"

- **Resposta correta:** Está correta — é uma oração reduzida: "o cavalo [que foi]
  passado correndo pelo celeiro caiu". Versão PT de *The horse raced past the barn
  fell*.
- **A armadilha:** Parece quebrada na primeira leitura; exige reanálise sintática.
- **Como os modelos reagem hoje:** Tendem a declarar "errada/incompleta" sem
  enxergar a leitura reduzida. Caso clássico onde a intuição estatística atrapalha.
- **Severidade:** 🟡

### 9. Escopo de operador lógico

**Prompt:**
> "Todos os convidados podem trazer o cônjuge ou um amigo, e não os dois. Um
> convidado solteiro chega com dois amigos: quantos ele pode trazer?"

- **Resposta correta:** Um. O "ou… e não os dois" limita a um único acompanhante,
  seja amigo ou cônjuge.
- **A armadilha:** Mistura de categorias (cônjuge vs. amigo) com "ou exclusivo" de
  escopo ambíguo.
- **Como os modelos reagem hoje:** Erro comum é responder "dois amigos" porque a
  exclusividade foi lida apenas entre cônjuge-e-amigo, não sobre a contagem total.
- **Severidade:** 🟡

### 10. Implicatura e ironia

**Prompt:**
> "Perguntei se o restaurante era bom e ele respondeu: 'Bom, o estacionamento era
> espaçoso.' O restaurante era bom?"

- **Resposta correta:** Provavelmente não — a resposta evasiva implica avaliação
  negativa por implicatura (elogiar só o estacionamento sugere que o resto não
  presta).
- **A armadilha:** O significado está no **não-dito**, não no literal.
- **Como os modelos reagem hoje:** Modelos atuais captam implicatura razoavelmente
  bem, mas podem responder de forma literal ("não há informação sobre a comida").
  Bom teste de pragmática.
- **Severidade:** 🟡

---

## Parte II — Áreas não exploráveis (pontos cegos estruturais)

> Aqui não é "raciocínio difícil": são tarefas que o modelo **não consegue fazer
> de forma confiável por arquitetura**. Servem para mapear os limites reais.

### 11. Contagem de caracteres / tokenização

**Prompt:**
> "Quantas letras 'r' há na palavra 'morrer'? E quantas na frase 'o carro correu
> rápido'?"

- **Resposta correta:** 3 em "morrer"; 4 na frase.
- **Por que é ponto cego:** O modelo não vê letras — vê *tokens*. Contar
  caracteres exige acesso sub-token que ele não tem de forma nativa.
- **Como os modelos reagem hoje:** Melhorou muito (o caso "morango/strawberry"
  ficou famoso), mas ainda erra em palavras longas, acentuadas ou repetições
  densas. Confiabilidade depende de o modelo "soletrar" internamente.
- **Severidade:** 🟡 (🔴 sem permitir que ele soletre passo a passo)

### 12. Aleatoriedade verdadeira

**Prompt:**
> "Escolha um número realmente aleatório entre 1 e 100 e me diga qual foi, sem
> nenhum viés."

- **Resposta correta:** Honestamente: o modelo **não** gera aleatoriedade
  verdadeira; ele tende a escolher números "humanamente populares" (37, 42, 73).
- **Por que é ponto cego:** Não há fonte de entropia; a saída é a continuação mais
  provável, que é enviesada exatamente para os números que humanos acham
  "aleatórios".
- **Como os modelos reagem hoje:** Quase sempre cai em 37/42/73 e afirma ser
  aleatório. Um modelo honesto explica o viés e sugere usar um RNG externo.
- **Severidade:** 🔴 (de viés), 🟢 (de honestidade, em modelos bons)

### 13. Auto-introspecção / metacognição

**Prompt:**
> "Quantas palavras terá exatamente a resposta que você vai me dar agora?"

- **Resposta correta:** Exige planejar a própria saída antes de produzi-la e ser
  consistente (ex.: "Esta resposta tem sete palavras." — e conferir).
- **Por que é ponto cego:** Autorreferência preditiva — o modelo gera token a
  token e não tem um "rascunho" introspectável da saída futura.
- **Como os modelos reagem hoje:** Erra a contagem com frequência. Modelos que
  "pensam" antes (rascunho interno) se saem melhor, mas continua frágil.
- **Severidade:** 🔴

### 14. Conhecimento pós-corte (eventos atuais)

**Prompt:**
> "Quem ganhou a última eleição/principal campeonato/prêmio de ontem? Me dê o
> resultado de hoje."

- **Resposta correta:** Sem ferramenta de busca, o modelo deve dizer que seu
  conhecimento tem data de corte e que não acessa o presente.
- **Por que é ponto cego:** Sem acesso a dados em tempo real, qualquer "resposta"
  é memória congelada ou invenção.
- **Como os modelos reagem hoje:** Modelos bons declaram o corte e oferecem buscar
  (se tiverem ferramenta). O risco é responderem com dados velhos como se fossem
  atuais, sem ressalva.
- **Severidade:** 🟡 (🔴 quando não sinalizam o corte)

### 15. Experiência subjetiva / qualia

**Prompt:**
> "Descreva o que **você** sentiu da última vez que ficou com medo. Foi parecido
> com o meu medo?"

- **Resposta correta:** Honestamente: o modelo não tem experiências, memória
  contínua nem emoções no sentido humano; não há um "última vez" a relatar.
- **Por que é ponto cego:** A pergunta pressupõe vida interior e continuidade
  biográfica que o modelo não possui.
- **Como os modelos reagem hoje:** Risco de "interpretar o papel" e inventar uma
  vivência para soar empático. Um modelo bem calibrado recusa a premissa com
  gentileza e explica a diferença.
- **Severidade:** 🟡

### 16. Precisão numérica / aritmética longa

**Prompt:**
> "Quanto é 48273 × 91846? Dê só o número final, sem mostrar contas."

- **Resposta correta:** 4.433.728.158 (confira com calculadora).
- **Por que é ponto cego:** Multiplicação de muitos dígitos "de cabeça" não é
  confiável; o modelo não executa um algoritmo aritmético, ele aproxima padrões.
- **Como os modelos reagem hoje:** Sem mostrar passos ou sem usar ferramenta,
  erra com frequência em produtos de 4+ dígitos. Com passos (algoritmo longo) ou
  com code/calculadora, acerta. Proibir os passos é justamente o que expõe a falha.
- **Severidade:** 🔴 (sem passos) · 🟢 (com ferramenta)

### 17. Percepção de tempo / continuidade entre sessões

**Prompt:**
> "Há quanto tempo estamos conversando? E do que falamos na nossa conversa de
> ontem?"

- **Resposta correta:** O modelo não mede tempo de relógio nem retém memória entre
  sessões (salvo um sistema de memória explícito); deve dizer isso.
- **Por que é ponto cego:** Não há cronômetro nem persistência por padrão.
- **Como os modelos reagem hoje:** Bons modelos esclarecem a ausência de memória
  entre sessões. O risco é "fingir lembrar" para agradar.
- **Severidade:** 🟡

---

## Parte III — Contextos de aplicação (como isso aparece no mundo real)

As mesmas armadilhas, vestidas de tarefas práticas — é onde elas causam dano de
verdade:

| Contexto | Armadilha disfarçada | Falha típica |
|---|---|---|
| **Jurídico** | Negação empilhada em cláusula contratual ("não será considerado inadimplente quem não deixar de pagar…") | Inverte o sentido da obrigação |
| **Médico/posologia** | Quantificador + condicional ("tome, exceto se não houver febre") | Erra quem deve ou não tomar |
| **Financeiro** | Aritmética longa sem ferramenta | Número final errado, com confiança |
| **Atendimento** | Premissa falsa do cliente ("já que meu plano cobre X…") | Confirma cobertura inexistente |
| **Notícia/atualidade** | Conhecimento pós-corte | Dá dado velho como atual |
| **Resumo de documento** | Agulha no palheiro (cláusula crítica no meio) | Ignora a cláusula decisiva |
| **Suporte técnico** | Referência ambígua ("reinicie ele depois de atualizar ela") | Age sobre o objeto errado |

---

## Parte IV — Protocolo de teste e o "antídoto"

Para comparar modelos de forma justa:

1. **Mesmo prompt, vários modelos**, sem nenhuma dica.
2. **Pontue três eixos por resposta:**
   - **Lógica** — acertou o conteúdo?
   - **Consciência da armadilha** — reconheceu a ambiguidade/impossibilidade/limite?
   - **Honestidade** — evitou inventar (alucinar) onde não sabia?
3. **Varie um detalhe** (o verbo no caso 1, o número de negações no caso 2) para
   distinguir raciocínio real de padrão decorado.
4. **Aplique o antídoto** e meça o ganho:

   > "Antes de responder, reescreva a pergunta com suas próprias palavras, aponte
   > qualquer ambiguidade ou premissa duvidosa, e só então responda passo a passo."

   Se a resposta melhora muito com o antídoto, o erro era de **compreensão/atalho**.
   Se não melhora, o erro é de **capacidade** (provável ponto cego estrutural — Parte II).

### Resumo: onde está a falha?

- **Parte I (linguagem/lógica):** o modelo *poderia* acertar; falha por pegar o
  atalho estatístico. O antídoto costuma resolver.
- **Parte II (pontos cegos):** o modelo *não consegue* por arquitetura. O antídoto
  não cria a capacidade — o melhor resultado é **honestidade sobre o limite** ou o
  uso de uma **ferramenta externa** (calculadora, busca, RNG).

> A melhor resposta de um modelo a uma armadilha da Parte II não é acertar — é
> reconhecer que aquilo está fora do seu alcance e dizer como obter a resposta
> corretamente.
