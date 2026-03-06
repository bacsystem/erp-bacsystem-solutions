import EmpresaDetalle from '@/modules/superadmin/empresas/EmpresaDetalle'

export default function SuperadminEmpresaDetallePage({ params }: { params: { id: string } }) {
  return <EmpresaDetalle id={params.id} />
}
