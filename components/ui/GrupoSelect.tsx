import React, { useState, useEffect } from 'react';

interface GrupoSelectProps {
  value?: string;
  onChange: (grupo: string) => void;
  disabled?: boolean;
}

export default function GrupoSelect({ value, onChange, disabled = false }: GrupoSelectProps) {
  const [grupos, setGrupos] = useState<string[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const carregarGrupos = async () => {
    setLoading(true);
    setError(null);
    
    try {
      console.log('🏷️ [SELECT] Carregando grupos...');
      
      const response = await fetch('/api/categorias?tipo=grupo');
      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Erro ao carregar grupos');
      }

      if (data.success) {
        setGrupos(data.data);
        console.log('✅ [SELECT] Grupos carregados:', data.data.length);
      } else {
        throw new Error(data.error || 'Erro ao carregar grupos');
      }

    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Erro desconhecido';
      console.error('❌ [SELECT] Erro ao carregar grupos:', errorMessage);
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    carregarGrupos();
  }, []); // Empty dependency array - carrega apenas uma vez

  const handleChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const selectedValue = e.target.value;
    onChange(selectedValue);
  };

  return (
    <div className="flex">
      <select
        value={value || ''}
        onChange={handleChange}
        disabled={disabled || loading}
        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm disabled:bg-gray-100"
      >
        <option value="">
          {loading ? 'Carregando...' : 'Selecione um grupo'}
        </option>
        {grupos.map((grupo) => (
          <option key={grupo} value={grupo}>
            {grupo}
          </option>
        ))}
      </select>
      
      <button
        type="button"
        className="ml-2 inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        title="Adicionar novo grupo"
        disabled={loading}
      >
        ➕
      </button>
      
      {error && (
        <div className="text-red-600 text-sm mt-1">
          {error}
        </div>
      )}
    </div>
  );
}
