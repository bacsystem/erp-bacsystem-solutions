import PlanesManager from '@/modules/superadmin/planes/PlanesManager'

export default function SuperadminPlanesPage() {
  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold text-white">Gestión de Planes</h1>
      <PlanesManager />
    </div>
  )
}
