IMPLEMENTACAO DA FUNCIONALIDADE DA GRADE

1. melhorar o layout e design, esta tudo meio achatado, e nao funcional.
quero que vc deixe um formato agradevel e facil de visualizar.

2. funcoes de busca de informacao se o codigo fornecido, ja existir no banco.
uma funcao que, quando o user digitar o codigo_gtin no campo, ao clicar em outro lugar, ele da um select em produtos com esse codigo gtin,e pega a descricao do produto e preenche em descricao.
tbm podemos pegar informacoes como preco, estoque, dimensoes para alterar isso no produto diretamente na grade.

SELECT na tabela produtos, where codigo_gtin = $codigo_fornecido

para pegar dimensoes,preco, estoque, vamos na tabela produtos_ou
porem, nao temos um codigo_gtin, sao vinculados por condigo_interno, que podemos obter dando um select em produtos com o codigo_gtin, e nessa tabela temos o campo codigo_interno.
assim, temos o codigo interno, e damos um select qtde, comprimento, largura, algura, peso
porem o preco fica em produtos_ib, vinculado pelo codigo interno tbm, mas o campo se chama preco_venda.

vc pode fazer por etapas ou simplesmente fazer uma unica consulta com join, ou fazer da melhor forma que vc acha essa funcao, e nao usaremos prisma pra isso, consulta em sql normal.


3. registrar em produtos_gd
a tabela produtos_gd tem os campos :
codigo( auto increment)
nome varchar 150 (sera a descricao do produto, se ele ja existir)
variacao varchar100
caracteristica 100
codigo_gtin varchar15
codigo interno varchar15

codigo_gtin quer dizer o codigo que o usuario informou, no caso, codigo da variaante
codigo_interno quer dizer o produto em que o produto de codigo gtin esta vinculado, no caso, o produto pai ou o produto em que estamos adicionando a variante.



4. ao fazer isso, vamos ter uma tela de edicao rapida da variante, onde podemos mexer no preco, qtde, dimensoes e tbm alterar as caracteristicas e variacoes, isso tudo eh um update nas tabelas que ja te mandei.

5. ao editar o produto variante, podemos atribuir uma imagem para ele tbm, ja que vamos comecar o sistema de imagens logo logo, vc nao precisa implementar isso agora, mas deixe um campo de implementacao, no caso, edicao da imagem, crop dinamico, resolucao, etc.
vamos chamar um componente que edita as imagens, entao ja deixe pre montado.

