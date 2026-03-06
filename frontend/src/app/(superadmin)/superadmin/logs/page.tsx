import LogsViewer from '@/modules/superadmin/logs/LogsViewer'

export default function SuperadminLogsPage() {
  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold text-white">Logs Globales</h1>
      <LogsViewer />
    </div>
  )
}
