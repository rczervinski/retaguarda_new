/** @type {import('next').NextConfig} */
const nextConfig = {
  // Removido output: 'export' por causa das APIs dinâmicas
  // output: 'export',
  trailingSlash: true,
  images: {
    unoptimized: true,
    domains: ['localhost'],
  },
  // Configuração para cPanel
  assetPrefix: process.env.NODE_ENV === 'production' ? '/seu-subdiretorio' : '',
}

module.exports = nextConfig
