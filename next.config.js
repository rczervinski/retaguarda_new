/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    unoptimized: true,
    domains: ['localhost'],
  },
  // Configuração para Vercel
  experimental: {
    serverActions: true,
  },
}

module.exports = nextConfig
