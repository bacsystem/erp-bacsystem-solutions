import { RegisterForm } from '@/modules/core/auth/register/RegisterForm';
import { Building2 } from 'lucide-react';
import Link from 'next/link';

export default function RegisterPage() {
  return (
    <main className="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 flex items-center justify-center py-10 px-4">
      <div className="w-full max-w-4xl">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-12 h-12 bg-blue-600 rounded-xl mb-4">
            <Building2 className="w-6 h-6 text-white" />
          </div>
          <h1 className="text-3xl font-bold text-gray-900">Crea tu empresa en OperaAI</h1>
          <p className="text-gray-500 mt-2">
            30 días gratis, sin tarjeta de crédito.{' '}
            <Link href="/login" className="text-blue-600 hover:underline font-medium">
              ¿Ya tienes cuenta?
            </Link>
          </p>
        </div>
        {/* Form card */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
          <RegisterForm />
        </div>
      </div>
    </main>
  );
}
