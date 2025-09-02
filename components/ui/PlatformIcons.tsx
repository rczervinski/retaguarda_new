'use client'

import { ComputerDesktopIcon, CloudIcon, ShoppingBagIcon } from '@heroicons/react/24/outline'

interface PlatformIconsProps {
  status?: string
  className?: string
}

export function PlatformIcons({ status, className = '' }: PlatformIconsProps) {
  const getIcons = () => {
    const icons = []
    
    if (!status || status === '') {
      // Produto local
      icons.push(
        <ComputerDesktopIcon 
          key="local" 
          className="w-5 h-5 text-gray-600" 
          title="Produto Local"
        />
      )
    }
    
    // Nuvemshop - baseado no sistema PHP
    if (status === 'ENS' || status === 'ENSVI' || status === 'ENSV' || status === 'E') {
      icons.push(
        <CloudIcon 
          key="nuvemshop" 
          className="w-5 h-5 text-blue-500" 
          title="Nuvemshop"
        />
      )
    }
    
    // MercadoLivre - quando implementado
    if (status?.includes('ML')) {
      icons.push(
        <ShoppingBagIcon 
          key="mercadolivre" 
          className="w-5 h-5 text-yellow-500" 
          title="MercadoLivre"
        />
      )
    }
    
    return icons
  }

  return (
    <div className={`flex items-center gap-1 ${className}`}>
      {getIcons()}
    </div>
  )
}
