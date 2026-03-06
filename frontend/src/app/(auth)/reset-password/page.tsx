import { Suspense } from 'react';
import { ResetPasswordForm } from '@/modules/core/auth/recuperar-password/ResetPasswordForm';

export default function ResetPasswordPage() {
  return (
    <main className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="bg-white p-8 rounded-xl shadow-sm border w-full max-w-md">
        <h1 className="text-2xl font-bold mb-6">Nueva contraseña</h1>
        <Suspense fallback={<p>Cargando...</p>}>
          <ResetPasswordForm />
        </Suspense>
      </div>
    </main>
  );
}
