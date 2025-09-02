# Resumo: Como Configurar no cPanel

## 🚨 RESPOSTA DIRETA

**Sua aplicação Next.js NÃO PODE rodar no cPanel tradicional** porque:
- ❌ cPanel não suporta Node.js
- ❌ Suas APIs precisam de servidor Node.js
- ❌ Prisma ORM não funciona em hospedagem PHP

## ✅ SOLUÇÕES RECOMENDADAS

### 1. **VERCEL** (Melhor opção - GRATUITO)
- ✅ Deploy em 5 minutos
- ✅ Detecta Next.js automaticamente
- ✅ SSL grátis + CDN global
- ✅ **TUTORIAL**: [vercel.com](https://vercel.com) → conectar GitHub → deploy

### 2. **Railway** (Alternativa)
- ✅ $5/mês
- ✅ Deploy direto do GitHub
- ✅ PostgreSQL incluído

### 3. **Render** (Alternativa)
- ✅ Tier gratuito disponível
- ✅ Deploy automático

## 🔧 SE VOCÊ TEM VPS/SERVIDOR

```bash
# 1. Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# 2. Upload e configuração
cd /var/www/retaguarda_new
npm install
npm run build
npm start
```

## 📋 VARIÁVEIS DE AMBIENTE NECESSÁRIAS

```env
DATABASE_URL="postgresql://u01414955000158:014158%40%40@postgresql-198445-0.cloudclusters.net:19627/01414955000158"
NEXTAUTH_URL="https://seudominio.com"
NEXTAUTH_SECRET="gere-um-secret-seguro"
NODE_ENV="production"
```

## 🎯 RECOMENDAÇÃO FINAL

**Use Vercel** - É gratuito, fácil e feito especificamente para Next.js!
