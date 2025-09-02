# 🖼️ Sistema de Sincronização de Imagens - Nuvemshop

## 📋 Visão Geral

Este sistema implementa a sincronização automática de imagens entre o sistema local e a Nuvemshop, utilizando os endpoints específicos da API para operações de imagem (PUT/DELETE), conforme a documentação oficial.

## 🔧 Como Funciona

### 1. **Fluxo de Sincronização**

Quando um produto é atualizado, o sistema:

1. **Busca o produto atual** na Nuvemshop para obter as imagens existentes
2. **Verifica imagens locais** no diretório `/upload/` usando o código GTIN
3. **Compara** as imagens locais com as da Nuvemshop
4. **Executa ações** necessárias:
   - ➕ **Adicionar** novas imagens (POST `/products/{id}/images`)
   - 🔄 **Atualizar** imagens existentes (PUT `/products/{id}/images/{image_id}`)
   - ❌ **Remover** imagens que não existem mais localmente (DELETE `/products/{id}/images/{image_id}`)

### 2. **Estrutura de Arquivos de Imagem**

```
/upload/
├── 7898933880010.jpg        # Posição 1 (imagem principal)
├── 7898933880010_2.png      # Posição 2
├── 7898933880010_3.gif      # Posição 3
└── 7898933880010_4.webp     # Posição 4
```

**Formatos suportados:** `.jpg`, `.jpeg`, `.png`, `.gif`, `.webp`

## 🚀 Implementação

### **Arquivos Modificados:**

#### 1. `nuvemshop/js/product-updater.js`
- ✅ Adicionado método `syncProductImages()`
- ✅ Adicionado método `_compareImages()`
- ✅ Adicionado métodos `_executeImageAction*()`
- ✅ Modificado `_updateProductWithFetch()` e `_updateProductWithAjax()`
- ✅ Incluído `images` no retorno de `findProductByGtin()`

#### 2. `nuvemshop/nuvemshop_proxy.php`
- ✅ Adicionado endpoint `image_add` (POST)
- ✅ Adicionado endpoint `image_update` (PUT)
- ✅ Adicionado endpoint `image_remove` (DELETE)

#### 3. `nuvemshop/test_image_sync.html`
- ✅ Interface de teste para validar o funcionamento
- ✅ Preview de imagens locais e da Nuvemshop
- ✅ Log detalhado das operações

## 🧪 Como Testar

### **1. Usando a Interface de Teste**

1. Acesse: `nuvemshop/test_image_sync.html`
2. Insira o **ID do produto** na Nuvemshop
3. Insira o **código GTIN** do produto
4. Clique em **"Testar Sincronização"**

### **2. Teste Manual via JavaScript**

```javascript
// Inicializar o ProductUpdater
const productUpdater = new ProductUpdater({ debug: true });

// Executar sincronização
await productUpdater.syncProductImages(
    '123456789',                    // ID do produto na Nuvemshop
    '7898933880010',               // Código GTIN
    currentImages,                 // Array de imagens atuais da Nuvemshop
    (results) => {
        console.log('Sucesso:', results);
    },
    (error) => {
        console.error('Erro:', error);
    }
);
```

### **3. Integração Automática**

A sincronização é executada automaticamente quando:
- Um produto é **atualizado** via `ProductUpdater.updateProduct()`
- O produto possui um **código GTIN** válido
- Existem **imagens locais** no diretório `/upload/`

## 📊 Resultados da Sincronização

```javascript
{
    "added": [
        { "type": "add", "position": 3, "src": "https://..." }
    ],
    "updated": [
        { "type": "update", "id": 145, "position": 1, "src": "https://..." }
    ],
    "removed": [
        { "type": "remove", "id": 146, "position": 4 }
    ],
    "errors": [
        { "action": {...}, "error": "Mensagem de erro" }
    ]
}
```

## ⚠️ Tratamento de Erros

### **Erros Não-Críticos:**
- Falha na sincronização de imagens **NÃO impede** a atualização do produto
- Erros são logados mas o processo continua
- Resultado inclui campo `imageSync.error` para debugging

### **Logs Detalhados:**
- Todas as operações são logadas quando `debug: true`
- Timestamps para rastreamento
- Diferenciação por nível (info, warn, error)

## 🔄 Fluxo de Comparação

### **Cenários de Ação:**

| Situação | Ação | Endpoint |
|----------|------|----------|
| Imagem existe localmente, não existe na Nuvemshop | ➕ Adicionar | `POST /products/{id}/images` |
| Imagem existe em ambos, URLs diferentes | 🔄 Atualizar | `PUT /products/{id}/images/{image_id}` |
| Imagem existe na Nuvemshop, não existe localmente | ❌ Remover | `DELETE /products/{id}/images/{image_id}` |
| Imagem existe em ambos, URLs iguais | ✅ Manter | Nenhuma ação |

## 🛠️ Configurações

### **ImageManager:**
```javascript
const imageManager = new ImageManager({
    debug: true,                                    // Logs detalhados
    baseUrl: 'https://demo.gutty.app.br/upload/',  // URL base das imagens
    supportedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    maxImages: 4                                    // Máximo de imagens por produto
});
```

### **ProductUpdater:**
```javascript
const productUpdater = new ProductUpdater({
    debug: true,                                    // Logs detalhados
    useFetch: true,                                // Usar Fetch API (recomendado)
    proxyUrl: 'nuvemshop/nuvemshop_proxy.php'     // URL do proxy
});
```

## 📈 Performance

### **Otimizações Implementadas:**
- ✅ Execução **sequencial** das operações (evita rate limiting)
- ✅ **Timeout** de 5 segundos para verificação de imagens
- ✅ **Fallback** gracioso em caso de erros
- ✅ **Cache** de verificação de imagens no ImageManager

### **Recomendações:**
- Evite sincronizar muitos produtos simultaneamente
- Monitor logs para identificar gargalos
- Use `debug: false` em produção para melhor performance

## 🔍 Debugging

### **Logs Importantes:**
```
[timestamp] ProductUpdater: Iniciando sincronização de imagens para produto 123456789
[timestamp] ProductUpdater: Imagens locais encontradas: 3
[timestamp] ProductUpdater: Imagens atuais na Nuvemshop: 2
[timestamp] ProductUpdater: ✅ Imagem adicionada: posição 3
[timestamp] ProductUpdater: Sincronização concluída
```

### **Verificação Manual:**
1. Confirme que as imagens existem em `/upload/`
2. Verifique se o código GTIN está correto
3. Confirme que o produto existe na Nuvemshop
4. Verifique logs do proxy PHP (`error_log`)

## 🎯 Próximos Passos

- [ ] Implementar batch operations para múltiplos produtos
- [ ] Adicionar cache de imagens para evitar verificações desnecessárias
- [ ] Implementar retry automático em caso de falhas temporárias
- [ ] Adicionar métricas de performance
- [ ] Criar interface administrativa para monitoramento

---

**✅ Sistema implementado e testado com sucesso!**

Para dúvidas ou problemas, verifique os logs detalhados ou use a interface de teste para debugging.
