'use client';

import { Building2 } from 'lucide-react';
import { Sidebar } from '@/shared/components/Sidebar';
import { SuscripcionBanner } from '@/shared/components/SuscripcionBanner';
import { EmpresaForm } from '@/modules/core/empresa/update-empresa/EmpresaForm';
import { LogoUpload } from '@/modules/core/empresa/upload-logo/LogoUpload';
import { useEmpresa } from '@/modules/core/empresa/get-empresa/use-empresa';

export default function EmpresaPage() {
  const { data: empresa } = useEmpresa();

  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 flex flex-col overflow-hidden">
        <SuscripcionBanner />
        <div className="flex-1 overflow-y-auto bg-gray-50">
          <div className="max-w-4xl mx-auto p-8 space-y-8">
            {/* Page header */}
            <div>
              <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <Building2 className="w-4 h-4" />
                <span>Configuración</span>
                <span>/</span>
                <span className="text-gray-900 font-medium">Datos de la Empresa</span>
              </div>
              <h1 className="text-2xl font-bold text-gray-900">Datos de la Empresa</h1>
              <p className="text-sm text-gray-500 mt-1">Actualiza la información fiscal y comercial de tu empresa</p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {/* Logo card */}
              <div className="lg:col-span-1">
                <div className="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
                  <h2 className="text-sm font-semibold text-gray-700">Imagen de marca</h2>
                  <LogoUpload currentLogo={empresa?.logo_url} />
                </div>
              </div>

              {/* Form card */}
              <div className="lg:col-span-2">
                <div className="bg-white rounded-2xl border border-gray-200 p-6">
                  <h2 className="text-sm font-semibold text-gray-700 mb-5">Información fiscal</h2>
                  <EmpresaForm />
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
