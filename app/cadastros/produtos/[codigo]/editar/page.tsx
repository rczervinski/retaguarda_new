'use client'

import { useState, useEffect } from 'react'
import { useRouter, useParams } from 'next/navigation'
import { Sidebar } from '@/components/layout/Sidebar'
import { Header } from '@/components/layout/Header'
import { ProductEditForm } from '@/components/forms/ProductEditForm'
import { ArrowLeftIcon } from '@heroicons/react/24/outline'
import Link from 'next/link'
import { ProdutoCompleto } from '@/types/produto'

export default function EditarProdutoPage() {
  const router = useRouter()
  const params = useParams()
  const codigo = params.codigo as string
  
  const [produto, setProduto] = useState<ProdutoCompleto | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (codigo) {
      fetchProduto()
    }
  }, [codigo])

  const fetchProduto = async () => {
    try {
      setLoading(true)
      setError(null)
      
      const response = await fetch(`/api/produtos/${codigo}`)
      
      if (response.ok) {
        const result = await response.json()
        console.log('📡 [DEBUG] Resposta da API:', result)
        
        // A API retorna { success: true, data: { produto } }
        if (result.success && result.data) {
          console.log('📦 [DEBUG] Dados do produto:', result.data)
          setProduto(result.data)
        } else {
          console.error('❌ [DEBUG] Formato inválido:', result)
          setError('Formato de resposta inválido')
        }
      } else {
        const errorData = await response.json()
        setError(errorData.error || 'Erro ao carregar produto')
      }
    } catch (error) {
      console.error('Erro ao carregar produto:', error)
      setError('Erro ao carregar produto')
    } finally {
      setLoading(false)
    }
  }

  const handleSave = async (produtoData: Partial<ProdutoCompleto>) => {
    try {
      const response = await fetch(`/api/produtos/${codigo}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(produtoData),
      })

      if (response.ok) {
        // Recarregar dados do produto em vez de redirecionar
        await fetchProduto()
        setError(null)
        console.log('✅ Produto salvo com sucesso!')
      } else {
        const errorData = await response.json()
        setError(errorData.error || 'Erro ao salvar produto')
      }
    } catch (error) {
      console.error('Erro ao salvar produto:', error)
      setError('Erro ao salvar produto')
    }
  }

  if (loading) {
    return (
      <div className="flex h-screen bg-gray-50">
        <Sidebar />
        <div className="flex-1 flex flex-col overflow-hidden lg:ml-0">
          <Header />
          <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
            <div className="max-w-7xl mx-auto">
              <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-primary-600"></div>
              </div>
            </div>
          </main>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex h-screen bg-gray-50">
        <Sidebar />
        <div className="flex-1 flex flex-col overflow-hidden lg:ml-0">
          <Header />
          <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
            <div className="max-w-7xl mx-auto">
              <div className="bg-red-50 border border-red-200 rounded-md p-4">
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
            </div>
          </main>
        </div>
      </div>
    )
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
                Editar Produto - {produto?.codigo_interno}
              </h1>
              <p className="text-gray-600">
                {produto?.descricao || 'Produto sem descrição'}
              </p>
            </div>

            {/* Form */}
            {produto && (
              <div className="space-y-8">
                <div className="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
                  <h3 className="font-bold">DEBUG: Dados carregados</h3>
                  <p><strong>Código:</strong> {produto.codigo_interno}</p>
                  <p><strong>Descrição:</strong> {produto.descricao}</p>
                  <p><strong>GTIN:</strong> {produto.codigo_gtin}</p>
                  <p><strong>Preço Venda:</strong> {produto.preco_venda}</p>
                  <p><strong>Status:</strong> {produto.status}</p>
                </div>
                
                {/* Formulário Principal */}
                <ProductEditForm
                  produto={produto}
                  onSave={handleSave}
                  onCancel={() => router.push('/cadastros/produtos')}
                />
              </div>
            )}
            
            {!produto && !loading && !error && (
              <div className="text-center text-gray-500">
                Nenhum produto carregado
              </div>
            )}
          </div>
        </main>
      </div>
    </div>
  )
}