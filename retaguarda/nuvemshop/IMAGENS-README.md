# 📸 Sistema de Imagens para Nuvemshop

## 🎯 Visão Geral

O sistema de imagens foi implementado para automaticamente verificar e incluir imagens de produtos ao criar ou atualizar produtos na Nuvemshop. As imagens são buscadas no diretório `/upload/` usando o código GTIN do produto.

## 🔧 Como Funciona

### Padrão de Nomenclatura
As imagens devem seguir este padrão de nomenclatura:

- **Imagem Principal (posição 1)**: `{codigo_gtin}.{extensao}`
  - Exemplo: `7898933880010.jpeg`

- **Imagens Secundárias (posições 2-4)**: `{codigo_gtin}_{numero}.{extensao}`
  - Exemplo: `7898933880010_2.jpeg`, `7898933880010_3.png`, `7898933880010_4.webp`

### Extensões Suportadas
- `.jpg`
- `.jpeg` 
- `.png`
- `.gif`
- `.webp`

### URL Base
- **Produção**: `https://demo.gutty.app.br/upload/`

## 🚀 Implementação

### Arquivos Criados/Modificados

1. **NOVO**: `nuvemshop/js/image-manager.js`
   - Classe responsável por verificar e preparar imagens

2. **MODIFICADO**: `nuvemshop/js/product-updater.js`
   - Integração do ImageManager
   - Verificação automática de imagens em `createProduct()` e `updateProduct()`

3. **MODIFICADO**: `produtos.php`
   - Inclusão do script `image-manager.js`

4. **NOVO**: `nuvemshop/teste-imagens.html`
   - Página de teste para verificar funcionamento

### Fluxo de Funcionamento

1. **Ao criar/atualizar produto**:
   - Sistema verifica automaticamente se existem imagens para o código GTIN
   - Testa todas as 4 posições possíveis (principal + 3 secundárias)
   - Testa todas as extensões suportadas para cada posição
   - Inclui imagens encontradas no JSON enviado para a API

2. **Verificação sequencial**:
   - Posição 1: `codigo.ext`
   - Posição 2: `codigo_2.ext` 
   - Posição 3: `codigo_3.ext`
   - Posição 4: `codigo_4.ext`

3. **Logs detalhados**:
   - Todas as verificações são logadas no console (quando debug=true)
   - Mostra quais imagens foram encontradas/não encontradas

## 📋 Formato da API

As imagens são enviadas no seguinte formato para a Nuvemshop:

```json
{
  "name": { "pt": "Nome do Produto" },
  "description": { "pt": "Descrição" },
  "images": [
    {
      "src": "https://demo.gutty.app.br/upload/7898933880010.jpeg",
      "position": 1
    },
    {
      "src": "https://demo.gutty.app.br/upload/7898933880010_2.png", 
      "position": 2
    }
  ],
  "variants": [...],
  "categories": [...]
}
```

## 🧪 Como Testar

### 1. Teste Manual de Imagens
Acesse: `nuvemshop/teste-imagens.html`
- Digite um código GTIN
- Clique em "Verificar Imagens"
- Veja quais imagens foram encontradas

### 2. Teste de Integração Completa
1. Vá para a página de produtos
2. Selecione um produto que tenha imagens em `/upload/`
3. Envie para a Nuvemshop
4. Verifique no console os logs de verificação de imagens
5. Confirme na Nuvemshop se as imagens foram incluídas

### 3. Estrutura de Teste Recomendada
```
/upload/
├── 7898933880010.jpeg          # Imagem principal
├── 7898933880010_2.jpeg        # Imagem secundária 1  
├── 7898933880010_3.png         # Imagem secundária 2
├── 7898933880010_4.webp        # Imagem secundária 3
├── 1234567890123.jpg           # Outro produto
└── 1234567890123_2.gif         # Imagem secundária
```

## 🔍 Logs e Debug

Para ativar logs detalhados, o ImageManager é inicializado com `debug: true`:

```javascript
this.imageManager = new ImageManager({
    debug: this.debug  // Herda do ProductUpdater
});
```

### Exemplos de Logs:
```
[ImageManager 2024-01-15T10:30:00.000Z] Iniciando verificação de imagens para código: 7898933880010
[ImageManager 2024-01-15T10:30:00.100Z] Verificando posição 1 (5 extensões)
[ImageManager 2024-01-15T10:30:00.200Z] ✅ Imagem encontrada na posição 1: 7898933880010.jpeg
[ImageManager 2024-01-15T10:30:00.300Z] ❌ Nenhuma imagem encontrada na posição 2
[ImageManager 2024-01-15T10:30:00.400Z] Verificação concluída. Total de imagens encontradas: 1
```

## ⚠️ Considerações Importantes

1. **Performance**: O sistema verifica até 20 URLs por produto (4 posições × 5 extensões)
2. **Timeout**: Cada verificação tem timeout de 5 segundos
3. **Fallback**: Se houver erro na verificação de imagens, o produto é criado/atualizado sem imagens
4. **Sobrescrita**: Imagens na Nuvemshop são sobrescritas a cada atualização
5. **Ordem**: As imagens são ordenadas por posição (1, 2, 3, 4)

## 🔄 Compatibilidade

- ✅ Funciona com produtos com variantes
- ✅ Funciona com produtos sem variantes  
- ✅ Compatível com sistema de categorias existente
- ✅ Mantém compatibilidade com código anterior
- ✅ Funciona com Fetch API e jQuery AJAX
