'use client'

import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts'
import type { MrrHistorico } from './use-dashboard'

interface Props {
  data: MrrHistorico[]
}

export default function MrrChart({ data }: Props) {
  return (
    <div className="bg-gray-800 rounded-xl p-5 border border-gray-700">
      <h3 className="text-white font-medium mb-4">MRR Histórico (6 meses)</h3>
      <ResponsiveContainer width="100%" height={200}>
        <LineChart data={data}>
          <CartesianGrid strokeDasharray="3 3" stroke="#374151" />
          <XAxis dataKey="mes" stroke="#9CA3AF" tick={{ fontSize: 12 }} />
          <YAxis stroke="#9CA3AF" tick={{ fontSize: 12 }} />
          <Tooltip
            contentStyle={{ backgroundColor: '#1F2937', border: '1px solid #374151', borderRadius: '8px' }}
            labelStyle={{ color: '#F9FAFB' }}
            formatter={(value: number | undefined) => [`S/ ${(value ?? 0).toFixed(2)}`, 'MRR']}
          />
          <Line type="monotone" dataKey="mrr" stroke="#6366F1" strokeWidth={2} dot={{ fill: '#6366F1' }} />
        </LineChart>
      </ResponsiveContainer>
    </div>
  )
}
