import { LoginForm } from '@/modules/core/auth/login/LoginForm';
import Link from 'next/link';

export default function LoginPage() {
  return (
    <main className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="bg-white p-8 rounded-xl shadow-sm border w-full max-w-md">
        <h1 className="text-2xl font-bold mb-2">Iniciar sesión</h1>
        <p className="text-gray-500 text-sm mb-6">
          ¿No tienes cuenta?{' '}
          <Link href="/register" className="text-blue-600 hover:underline">
            Regístrate gratis
          </Link>
        </p>
        <LoginForm />
      </div>
    </main>
  );
}
