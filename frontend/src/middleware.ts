import { NextRequest, NextResponse } from 'next/server'

const PUBLIC_PATHS = [
  '/login',
  '/register',
  '/recuperar-password',
  '/reset-password',
  '/activar',
]

export function middleware(req: NextRequest) {
  const hasSession = req.cookies.get('has_session')?.value === '1'
  const path = req.nextUrl.pathname
  const isPublic = PUBLIC_PATHS.some((p) => path.startsWith(p))

  if (!hasSession && !isPublic) {
    return NextResponse.redirect(new URL('/login', req.url))
  }

  if (hasSession && path === '/login') {
    return NextResponse.redirect(new URL('/dashboard', req.url))
  }

  return NextResponse.next()
}

export const config = {
  matcher: ['/((?!api|_next/static|_next/image|favicon.ico).*)'],
}
