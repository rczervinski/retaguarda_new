'use client'

import { ClockIcon } from '@heroicons/react/24/outline'

const activities = [
  {
    id: 1,
    type: 'produto',
    message: 'Produto "Camiseta Polo Azul" foi cadastrado',
    time: '2 minutos atrás',
    user: 'João Silva',
  },
  {
    id: 2,
    type: 'sync',
    message: 'Sincronização com Nuvemshop concluída',
    time: '15 minutos atrás',
    user: 'Sistema',
  },
  {
    id: 3,
    type: 'venda',
    message: 'Nova venda online recebida - Pedido #1234',
    time: '1 hora atrás',
    user: 'E-commerce',
  },
  {
    id: 4,
    type: 'produto',
    message: 'Estoque do produto "Tênis Esportivo" atualizado',
    time: '2 horas atrás',
    user: 'Maria Santos',
  },
  {
    id: 5,
    type: 'cliente',
    message: 'Novo cliente cadastrado - Ana Costa',
    time: '3 horas atrás',
    user: 'Pedro Lima',
  },
]

const getActivityIcon = (type: string) => {
  switch (type) {
    case 'produto':
      return '📦'
    case 'sync':
      return '🔄'
    case 'venda':
      return '🛒'
    case 'cliente':
      return '👤'
    default:
      return '📋'
  }
}

export function RecentActivity() {
  return (
    <div className="card">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-semibold text-gray-900">Atividade Recente</h3>
        <ClockIcon className="w-5 h-5 text-gray-400" />
      </div>
      
      <div className="space-y-4">
        {activities.map((activity) => (
          <div key={activity.id} className="flex items-start space-x-3">
            <div className="flex-shrink-0 text-lg">
              {getActivityIcon(activity.type)}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm text-gray-900">{activity.message}</p>
              <div className="flex items-center mt-1 text-xs text-gray-500">
                <span>{activity.user}</span>
                <span className="mx-1">•</span>
                <span>{activity.time}</span>
              </div>
            </div>
          </div>
        ))}
      </div>
      
      <div className="mt-4 pt-4 border-t border-gray-200">
        <button className="text-sm text-primary-600 hover:text-primary-700 font-medium">
          Ver todas as atividades
        </button>
      </div>
    </div>
  )
}
