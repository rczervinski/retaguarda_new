#!/bin/bash

echo "🚀 Iniciando build para produção..."

# 1. Instalar dependências
echo "📦 Instalando dependências..."
npm install

# 2. Gerar Prisma Client
echo "🔧 Gerando Prisma Client..."
npx prisma generate

# 3. Build da aplicação
echo "🏗️ Fazendo build da aplicação..."
npm run build

# 4. Verificar se foi gerado corretamente
if [ -d "out" ]; then
    echo "✅ Build estático gerado com sucesso!"
    echo "📁 Arquivos estão na pasta 'out/'"
    echo "📤 Faça upload do conteúdo da pasta 'out/' para public_html/ no cPanel"
else
    echo "❌ Erro no build estático"
    echo "🔄 Tentando build padrão..."
    npm run build
    if [ -d ".next" ]; then
        echo "✅ Build padrão gerado com sucesso!"
        echo "⚠️ Para cPanel, você precisará de hospedagem Node.js"
    fi
fi

echo "🎉 Build concluído!"
