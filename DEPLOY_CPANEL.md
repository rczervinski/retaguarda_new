# Guia de Deploy para cPanel

## ⚠️ LIMITAÇÕES IMPORTANTES

**cPanel tradicional NÃO suporta aplicações Next.js** pois requer Node.js no servidor. Este guia apresenta alternativas viáveis.

## 🎯 Status do Build

✅ **Build realizado com sucesso!**
- 33 páginas geradas
- APIs dinâmicas funcionando (λ)
- Páginas estáticas otimizadas (○)

## 🚀 Opção 1: Hospedagem Node.js (Recomendado)

Sua aplicação usa recursos avançados do Next.js que **requerem Node.js no servidor**:

### Recursos que precisam de Node.js:
- ✅ APIs dinâmicas (`/api/*`)
- ✅ Server-Side Rendering 
- ✅ Prisma ORM com PostgreSQL
- ✅ Rotas dinâmicas `[codigo]`

### Hospedagens compatíveis:

#### A) **Vercel** (Criadores do Next.js) - RECOMENDADO
```bash
npm install -g vercel
vercel --prod
```
- ✅ Deploy automático via Git
- ✅ SSL gratuito
- ✅ CDN global
- ✅ Tier gratuito generoso

#### B) **Railway**
```bash
npm install -g @railway/cli
railway login
railway deploy
```

#### C) **Render**
- Conecte seu repositório GitHub
- Build Command: `npm run build`
- Start Command: `npm start`

#### D) **DigitalOcean App Platform**
- Deploy direto do GitHub
- Auto-detecção Next.js

## 🔧 Opção 2: VPS/Servidor próprio

Se você tem um VPS com acesso SSH:

### 1. Preparar servidor
```bash
# Instalar Node.js 18+
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Instalar PM2 para gerenciar processos
npm install -g pm2
```

### 2. Deploy da aplicação
```bash
# Upload dos arquivos (substitua dados do servidor)
scp -r ./retaguarda_new user@servidor:/var/www/

# No servidor
cd /var/www/retaguarda_new
npm install
npx prisma generate
npm run build

# Iniciar com PM2
pm2 start npm --name "retaguarda" -- start
pm2 save
pm2 startup
```

### 3. Configurar proxy reverso (Nginx)
```nginx
server {
    listen 80;
    server_name seudominio.com;
    
    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
```

## �️ Configuração do Banco de Dados

Sua aplicação já está configurada para PostgreSQL externo:
- **Host**: `postgresql-198445-0.cloudclusters.net:19627`
- **Database**: `01414955000158`
- **User**: `u01414955000158`

### Executar migrações (após deploy):
```bash
npx prisma migrate deploy
npx prisma generate
```

## � Variáveis de Ambiente

Configure no seu provedor de hospedagem:

```env
DATABASE_URL="postgresql://u01414955000158:014158%40%40@postgresql-198445-0.cloudclusters.net:19627/01414955000158"
NEXTAUTH_URL="https://seudominio.com"
NEXTAUTH_SECRET="gere-um-secret-super-seguro"
NODE_ENV="production"
```

## � Estrutura de Build

Sua aplicação gera:
```
.next/
├── server/
│   ├── app/
│   └── pages/
├── static/
└── standalone/ (se configurado)

Páginas estáticas: 18 páginas
APIs dinâmicas: 15 endpoints
Total: 33 rotas
```

## 🎯 Recomendação Final - Vercel

**Para sua aplicação completa, recomendo usar Vercel**, pois:
- ✅ Suporte nativo ao Next.js 14
- ✅ Deploy automático via Git
- ✅ Todas as APIs funcionam perfeitamente
- ✅ SSL automático
- ✅ CDN global
- ✅ Monitoramento incluído

### Deploy rápido no Vercel:

1. **Crie conta** em [vercel.com](https://vercel.com)
2. **Conecte seu repositório** GitHub/GitLab
3. **Configure variáveis de ambiente** no dashboard
4. **Deploy automático** - Vercel detecta Next.js automaticamente!

### Variáveis para configurar no Vercel:
```
DATABASE_URL=postgresql://u01414955000158:014158%40%40@postgresql-198445-0.cloudclusters.net:19627/01414955000158
NEXTAUTH_URL=https://seu-projeto.vercel.app
NEXTAUTH_SECRET=seu-secret-super-seguro
```

## ⚠️ Importante sobre cPanel

**cPanel tradicional NÃO funciona** porque:
- ❌ Não suporte Node.js por padrão
- ❌ Apenas hospedagem PHP/HTML estático
- ❌ Suas APIs não funcionarão
- ❌ Prisma não funcionará

Se seu provedor oferece "Node.js no cPanel", verifique:
- ✅ Versão Node.js 18+
- ✅ Acesso SSH ou terminal
- ✅ Possibilidade de instalar dependências npm
- ✅ Portas personalizadas permitidas
