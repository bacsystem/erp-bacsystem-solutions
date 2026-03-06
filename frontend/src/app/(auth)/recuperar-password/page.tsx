import { RecuperarPasswordForm } from '@/modules/core/auth/recuperar-password/RecuperarPasswordForm';
import Link from 'next/link';

export default function RecuperarPasswordPage() {
  return (
    <main className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="bg-white p-8 rounded-xl shadow-sm border w-full max-w-md">
        <h1 className="text-2xl font-bold mb-2">Recuperar contraseña</h1>
        <p className="text-gray-500 text-sm mb-6">
          Te enviaremos un enlace para restablecer tu contraseña.
        </p>
        <RecuperarPasswordForm />
        <p className="text-center text-sm text-gray-500 mt-4">
          <Link href="/login" className="text-blue-600 hover:underline">
            Volver al inicio de sesión
          </Link>
        </p>
      </div>
    </main>
  );
}
