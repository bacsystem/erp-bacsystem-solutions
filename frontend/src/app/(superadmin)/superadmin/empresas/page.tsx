import EmpresasTable from '@/modules/superadmin/empresas/EmpresasTable'

export default function SuperadminEmpresasPage() {
  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold text-white">Empresas</h1>
      <EmpresasTable />
    </div>
  )
}
