'use client'

import { CubeIcon, CurrencyDollarIcon, ShoppingCartIcon, UsersIcon } from '@heroicons/react/24/outline'

const stats = [
  {
    name: 'Total de Produtos',
    value: '2.847',
    change: '+12%',
    changeType: 'positive',
    icon: CubeIcon,
  },
  {
    name: 'Vendas do Mês',
    value: 'R$ 45.231',
    change: '+8%',
    changeType: 'positive',
    icon: CurrencyDollarIcon,
  },
  {
    name: 'Produtos no E-commerce',
    value: '1.234',
    change: '+5%',
    changeType: 'positive',
    icon: ShoppingCartIcon,
  },
  {
    name: 'Clientes Ativos',
    value: '892',
    change: '+3%',
    changeType: 'positive',
    icon: UsersIcon,
  },
]

export function DashboardStats() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      {stats.map((stat) => (
        <div key={stat.name} className="card">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <stat.icon className="w-8 h-8 text-primary-600" />
            </div>
            <div className="ml-4 flex-1">
              <p className="text-sm font-medium text-gray-600">{stat.name}</p>
              <div className="flex items-baseline">
                <p className="text-2xl font-semibold text-gray-900">{stat.value}</p>
                <span className={`ml-2 text-sm font-medium ${
                  stat.changeType === 'positive' ? 'text-green-600' : 'text-red-600'
                }`}>
                  {stat.change}
                </span>
              </div>
            </div>
          </div>
        </div>
      ))}
    </div>
  )
}
