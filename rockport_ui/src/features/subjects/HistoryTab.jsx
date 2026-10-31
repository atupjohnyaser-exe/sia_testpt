import { useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { EmptyState, Pagination, Panel, QueryState, StatusTag } from '../../components/ui'
import { useStatusHistory } from '../../hooks/queries'
import { dateTimeText } from '../../lib/format'

export function HistoryTab() {
  const { subject } = useOutletContext()
  const [page, setPage] = useState(1)
  const query = useStatusHistory(subject.id, { page, per_page: 15 })

  return (
    <Panel eyebrow="EVERY STATUS CHANGE, WITH WHY" title="STATUS HISTORY">
      <QueryState query={query}>
        {query.data?.data.length ? (
          <div className="table-scroll">
            <table className="data-table">
              <thead><tr><th>WHEN</th><th>CHANGE</th><th>REASON</th><th>BY</th></tr></thead>
              <tbody>
                {query.data.data.map((change) => (
                  <tr key={change.id}>
                    <td>{dateTimeText(change.created_at)}</td>
                    <td>
                      <span className="change-flow">
                        {change.from ? <StatusTag status={change.from} /> : <small>NEW</small>}
                        <span>→</span>
                        <StatusTag status={change.to} />
                      </span>
                    </td>
                    <td>{change.reason}</td>
                    <td>{change.changed_by?.name ?? 'SYSTEM'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <EmptyState title="NO CHANGES RECORDED" />
        )}
        <Pagination meta={query.data?.meta} onPage={setPage} />
      </QueryState>
    </Panel>
  )
}
