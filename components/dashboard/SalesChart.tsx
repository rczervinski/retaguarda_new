'use client'

import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts'

const data = [
  { name: 'Jan', vendas: 4000, produtos: 240 },
  { name: 'Fev', vendas: 3000, produtos: 139 },
  { name: 'Mar', vendas: 2000, produtos: 980 },
  { name: 'Abr', vendas: 2780, produtos: 390 },
  { name: 'Mai', vendas: 1890, produtos: 480 },
  { name: 'Jun', vendas: 2390, produtos: 380 },
  { name: 'Jul', vendas: 3490, produtos: 430 },
]

export function SalesChart() {
  return (
    <div className="card">
      <h3 className="text-lg font-semibold text-gray-900 mb-4">Vendas dos Últimos Meses</h3>
      <div className="h-80">
        <ResponsiveContainer width="100%" height="100%">
          <LineChart data={data}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis />
            <Tooltip 
              formatter={(value, name) => [
                name === 'vendas' ? `R$ ${value}` : value,
                name === 'vendas' ? 'Vendas' : 'Produtos Vendidos'
              ]}
            />
            <Line 
              type="monotone" 
              dataKey="vendas" 
              stroke="#3b82f6" 
              strokeWidth={2}
              dot={{ fill: '#3b82f6' }}
            />
            <Line 
              type="monotone" 
              dataKey="produtos" 
              stroke="#10b981" 
              strokeWidth={2}
              dot={{ fill: '#10b981' }}
            />
          </LineChart>
        </ResponsiveContainer>
      </div>
    </div>
  )
}
