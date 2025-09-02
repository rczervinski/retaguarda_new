'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { Sidebar } from '@/components/layout/Sidebar'
import { Header } from '@/components/layout/Header'
import { ProductEditForm } from '@/components/forms/ProductEditForm'
import { ArrowLeftIcon } from '@heroicons/react/24/outline'
import Link from 'next/link'
import { ProdutoCompleto } from '@/types/produto'

export default function NovoProdutoPage() {
  const router = useRouter()
  const [error, setError] = useState<string | null>(null)

  const handleSave = async (produtoData: Partial<ProdutoCompleto>) => {
    try {
      const response = await fetch('/api/produtos', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(produtoData),
      })

      if (response.ok) {
        router.push('/cadastros/produtos')
      } else {
        const errorData = await response.json()
        setError(errorData.error || 'Erro ao criar produto')
      }
    } catch (error) {
      console.error('Erro ao criar produto:', error)
      setError('Erro ao criar produto')
    }
  }

  return (
    <div className="flex h-screen bg-gray-50">
      <Sidebar />
      
      <div className="flex-1 flex flex-col overflow-hidden lg:ml-0">
        <Header />
        
        <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
          <div className="max-w-7xl mx-auto">
            {/* Header */}
            <div className="mb-6">
              <div className="flex items-center mb-4">
                <Link
                  href="/cadastros/produtos"
                  className="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700"
                >
                  <ArrowLeftIcon className="w-5 h-5 mr-1" />
                  Voltar para produtos
                </Link>
              </div>
              <h1 className="text-2xl font-bold text-gray-900">
                Novo Produto
              </h1>
              <p className="text-gray-600">
                Cadastre um novo produto no sistema
              </p>
            </div>

            {/* Error Message */}
            {error && (
              <div className="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                <div className="flex">
                  <div className="ml-3">
                    <h3 className="text-sm font-medium text-red-800">
                      Erro
                    </h3>
                    <div className="mt-2 text-sm text-red-700">
                      <p>{error}</p>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Form */}
            <ProductEditForm
              produto={null}
              onSave={handleSave}
              onCancel={() => router.push('/cadastros/produtos')}
            />
          </div>
        </main>
      </div>
    </div>
  )
}
