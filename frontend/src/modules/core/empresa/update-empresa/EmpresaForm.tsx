'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Building2, Check, FileText, Loader2, Lock, MapPin } from 'lucide-react';
import { api } from '@/shared/lib/api';
import { useEmpresa } from '../get-empresa/use-empresa';
import { empresaUpdateSchema, type EmpresaUpdateData } from './empresa.schema';

const REGIMENES = [
  { value: 'RER', label: 'Nuevo RUS (RER)', desc: 'Hasta S/. 525,000 anuales' },
  { value: 'RMT', label: 'Régimen MYPE Tributario (RMT)', desc: 'Hasta 1,700 UIT — tasa progresiva' },
  { value: 'RG', label: 'Régimen General (RG)', desc: 'Sin límite de ingresos — 29.5%' },
];

const INPUT_CLASS = 'w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition';
const READONLY_CLASS = 'w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-gray-50 text-gray-500 cursor-not-allowed';

export function EmpresaForm() {
  const { data: empresa, isLoading } = useEmpresa();
  const queryClient = useQueryClient();

  const { register, handleSubmit, watch, setValue, formState: { errors } } = useForm<EmpresaUpdateData>({
    resolver: zodResolver(empresaUpdateSchema),
    values: empresa ? {
      nombre_comercial: empresa.nombre_comercial ?? '',
      direccion: empresa.direccion ?? '',
      ubigeo: empresa.ubigeo ?? '',
      regimen_tributario: (empresa.regimen_tributario as EmpresaUpdateData['regimen_tributario']) ?? undefined,
    } : undefined,
  });

  const { mutate, isPending, isSuccess } = useMutation({
    mutationFn: (data: EmpresaUpdateData) => api.put('/empresa', data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['empresa'] }),
  });

  const regimenSelected = watch('regimen_tributario');

  if (isLoading) {
    return (
      <div className="flex items-center gap-2 text-gray-400 py-4">
        <Loader2 className="w-4 h-4 animate-spin" />
        <span className="text-sm">Cargando...</span>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit((d) => mutate(d))} className="space-y-5">
      {/* Read-only fields */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label htmlFor="ruc" className="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
            RUC
          </label>
          <div className="relative">
            <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
            <input id="ruc" value={empresa?.ruc ?? ''} readOnly className={`${READONLY_CLASS} pl-8`} />
          </div>
          <p className="text-xs text-gray-400 mt-1">No puede modificarse.</p>
        </div>
        <div>
          <label htmlFor="razon_social" className="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
            Razón Social
          </label>
          <div className="relative">
            <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
            <input id="razon_social" value={empresa?.razon_social ?? ''} readOnly className={`${READONLY_CLASS} pl-8`} />
          </div>
        </div>
      </div>

      {/* Editable fields */}
      <div>
        <label htmlFor="nombre_comercial" className="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
          Nombre Comercial
        </label>
        <div className="relative">
          <FileText className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
          <input
            id="nombre_comercial"
            {...register('nombre_comercial')}
            placeholder="Nombre que ven tus clientes"
            className={`${INPUT_CLASS} pl-8`}
          />
        </div>
        {errors.nombre_comercial && <p className="text-red-500 text-xs mt-1">{errors.nombre_comercial.message}</p>}
      </div>

      <div>
        <label htmlFor="direccion" className="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
          Dirección
        </label>
        <div className="relative">
          <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
          <input
            id="direccion"
            {...register('direccion')}
            placeholder="Av. Ejemplo 123, Lima"
            className={`${INPUT_CLASS} pl-8`}
          />
        </div>
        {errors.direccion && <p className="text-red-500 text-xs mt-1">{errors.direccion.message}</p>}
      </div>

      {/* Régimen tributario */}
      <div>
        <p className="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
          Régimen Tributario
        </p>
        <div className="space-y-2">
          {REGIMENES.map((r) => {
            const isSelected = regimenSelected === r.value;
            return (
              <button
                key={r.value}
                type="button"
                onClick={() => setValue('regimen_tributario', r.value as EmpresaUpdateData['regimen_tributario'])}
                className={`flex items-center justify-between w-full border-2 rounded-xl px-4 py-3 text-left transition-all ${
                  isSelected
                    ? 'border-blue-600 bg-blue-50'
                    : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'
                }`}
              >
                <div>
                  <p className={`text-sm font-semibold ${isSelected ? 'text-blue-700' : 'text-gray-800'}`}>{r.label}</p>
                  <p className="text-xs text-gray-500 mt-0.5">{r.desc}</p>
                </div>
                <div className={`w-4 h-4 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition ${
                  isSelected ? 'border-blue-600' : 'border-gray-300'
                }`}>
                  {isSelected && <div className="w-2 h-2 rounded-full bg-blue-600" />}
                </div>
              </button>
            );
          })}
        </div>
        <input type="hidden" {...register('regimen_tributario')} />
        {errors.regimen_tributario && <p className="text-red-500 text-xs mt-1">{errors.regimen_tributario.message}</p>}
      </div>

      <div className="flex items-center gap-4 pt-1">
        <button
          type="submit"
          disabled={isPending}
          className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-5 py-2.5 text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {isPending ? (
            <><Loader2 className="w-4 h-4 animate-spin" /> Guardando...</>
          ) : (
            'Guardar cambios'
          )}
        </button>
        {isSuccess && (
          <span className="flex items-center gap-1.5 text-green-600 text-sm font-medium">
            <Check className="w-4 h-4" /> Guardado correctamente
          </span>
        )}
      </div>
    </form>
  );
}
