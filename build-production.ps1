# Script de Build para Produção - Windows
Write-Host "🚀 Iniciando build para produção..." -ForegroundColor Green

# 1. Instalar dependências
Write-Host "📦 Instalando dependências..." -ForegroundColor Yellow
npm install

# 2. Gerar Prisma Client
Write-Host "🔧 Gerando Prisma Client..." -ForegroundColor Yellow
npx prisma generate

# 3. Build da aplicação
Write-Host "🏗️ Fazendo build da aplicação..." -ForegroundColor Yellow
npm run build

# 4. Verificar se foi gerado corretamente
if (Test-Path "out") {
    Write-Host "✅ Build estático gerado com sucesso!" -ForegroundColor Green
    Write-Host "📁 Arquivos estão na pasta 'out/'" -ForegroundColor Cyan
    Write-Host "📤 Faça upload do conteúdo da pasta 'out/' para public_html/ no cPanel" -ForegroundColor Cyan
} else {
    Write-Host "❌ Erro no build estático" -ForegroundColor Red
    Write-Host "🔄 Tentando build padrão..." -ForegroundColor Yellow
    npm run build
    if (Test-Path ".next") {
        Write-Host "✅ Build padrão gerado com sucesso!" -ForegroundColor Green
        Write-Host "⚠️ Para cPanel, você precisará de hospedagem Node.js" -ForegroundColor Yellow
    }
}

Write-Host "🎉 Build concluído!" -ForegroundColor Green
