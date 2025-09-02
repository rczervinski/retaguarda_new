'use client'

import { BellIcon, UserCircleIcon, Bars3Icon, XMarkIcon } from '@heroicons/react/24/outline'
import { useSidebar } from '@/contexts/SidebarContext'

export function Header() {
  const { isOpen, toggle } = useSidebar()

  return (
    <header className="bg-white shadow-sm border-b border-gray-200">
      <div className="flex items-center justify-between px-4 lg:px-6 py-4">
        <div className="flex items-center space-x-4">
          {/* Menu Hambúrguer - Visível apenas no mobile/tablet */}
          <button
            onClick={toggle}
            className="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all duration-200 lg:hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            aria-label={isOpen ? 'Fechar menu' : 'Abrir menu'}
          >
            {isOpen ? (
              <XMarkIcon className="w-6 h-6" />
            ) : (
              <Bars3Icon className="w-6 h-6" />
            )}
          </button>

          {/* Logo/Título responsivo */}
          <div className="flex items-center">
            <h1 className="text-lg lg:text-xl font-bold text-blue-600 lg:hidden">
              GUTTY
            </h1>
            <h2 className="hidden lg:block text-lg font-semibold text-gray-900">
              Sistema de gestão
            </h2>
          </div>
        </div>
        
        <div className="flex items-center space-x-2 lg:space-x-4">
          {/* Notifications */}
          <button className="p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-full transition-colors duration-200">
            <BellIcon className="w-5 h-5 lg:w-6 lg:h-6" />
          </button>
          
          {/* User Menu */}
          <button className="flex items-center space-x-2 p-2 text-gray-700 hover:bg-gray-100 rounded-md transition-colors duration-200">
            <UserCircleIcon className="w-6 h-6 lg:w-8 lg:h-8" />
            <span className="hidden sm:block text-sm font-medium">Usuário</span>
          </button>
        </div>
      </div>
    </header>
  )
}
