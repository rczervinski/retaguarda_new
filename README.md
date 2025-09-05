# Retaguarda ERP - Sistema de Gestão

Sistema completo de gestão empresarial desenvolvido em Next.js 15 com TypeScript.

## 🚀 Deploy no Vercel

### 1. Preparação
```bash
# Commit final
git add .
git commit -m "Deploy ready for Vercel"
git push origin main
```

### 2. Deploy
1. Acesse [vercel.com](https://vercel.com)
2. Faça login com GitHub
3. Clique "New Project" → Importe este repositório
4. Configure variável de ambiente:
   - `DATABASE_URL`: `postgresql://u01414955000158:014158%40%40@postgresql-198445-0.cloudclusters.net:19627/01414955000158`
5. Deploy automático!

## ⚡ Funcionalidades

- ✅ Sistema completo de produtos com grades e variantes  
- ✅ Upload e crop profissional de imagens (react-image-crop)
- ✅ Sistema de composição de produtos
- ✅ Controle de estoque, preços e dimensões  
- ✅ Interface responsiva moderna

## 🛠️ Stack Tecnológica

- **Framework**: Next.js 15, React 18, TypeScript 5
- **Styling**: Tailwind CSS, Headless UI
- **Database**: PostgreSQL (CloudClusters) 
- **Images**: Sharp, React Image Crop
- **Icons**: Lucide React

## 💻 Desenvolvimento Local

```bash
npm install
npm run dev  # http://localhost:3000
```