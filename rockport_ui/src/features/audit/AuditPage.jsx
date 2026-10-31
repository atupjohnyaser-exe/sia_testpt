import { useState } from 'react'
import { Link } from 'react-router-dom'
import { EmptyState, Pagination, PageHeading, Panel, QueryState, StatusTag } from '../../components/ui'
import { useAuditLogs } from '../../hooks/queries'
import { dateTimeText, label } from '../../lib/format'

const ACTIONS = [
  'login', 'login_failed', 'logout', 'viewed', 'created', 'updated', 'archived', 'restored', 'exported',
  'status_changed', 'threat_overridden', 'note_added', 'note_deleted', 'infraction_added', 'infraction_served',
  'infraction_deleted', 'manhunt_logged', 'manhunt_deleted', 'sighting_added', 'rates_updated', 'case_file_generated',
]

// Short readable detail for each row. Never shows free text from notes.
function detail(log) {
  const meta = log.meta ?? {}

  if (log.action === 'login_failed') return `BADGE ${meta.badge_id ?? '?'}`
  if (log.action === 'status_changed') return `${label(meta.from)} → ${label(meta.to)}: ${meta.reason ?? ''}`
  if (log.action === 'threat_overridden') return `LEVEL ${meta.level ?? 'AUTO'}: ${meta.reason ?? ''}`
  if (meta.manhunt) return meta.manhunt
  if (meta.changed) return `FIELDS: ${meta.changed.join(', ')}`
  if (meta.scope) return meta.scope.toUpperCase()

  return ''
}

export function AuditPage() {
  const [filters, setFilters] = useState({ action: '', from: '', to: '' })
  const [page, setPage] = useState(1)

  const params = { page, per_page: 30 }
  for (const [key, value] of Object.entries(filters)) {
    if (value) params[key] = value
  }

  const query = useAuditLogs(params)
  const set = (key) => (event) => {
    setFilters((current) => ({ ...current, [key]: event.target.value }))
    setPage(1)
  }

  return (
    <div className="content-page">
      <PageHeading eyebrow="SUPERVISOR ONLY // READ ONLY" title="AUDIT LOG" action={<span className="online-indicator">MONITORING</span>}>
        Every sign in, failed attempt, export, change and case file is recorded here. Entries cannot be edited or deleted.
      </PageHeading>

      <Panel
        eyebrow="ACCESS ACTIVITY"
        title="RECENT EVENTS"
        action={
          <div className="panel-actions">
            <label className="filter-select"><small>ACTION</small>
              <select value={filters.action} onChange={set('action')}>
                <option value="">ALL</option>
                {ACTIONS.map((action) => <option key={action} value={action}>{label(action)}</option>)}
              </select>
            </label>
            <label className="filter-select"><small>FROM</small><input type="date" value={filters.from} onChange={set('from')} /></label>
            <label className="filter-select"><small>TO</small><input type="date" value={filters.to} onChange={set('to')} /></label>
          </div>
        }
      >
        <QueryState query={query} rows={6}>
          {query.data?.data.length ? (
            <div className="table-scroll">
              <table className="data-table">
                <thead><tr><th>TIME</th><th>OFFICER</th><th>ACTION</th><th>RECORD</th><th>DETAIL</th><th>IP</th></tr></thead>
                <tbody>
                  {query.data.data.map((log) => (
                    <tr key={log.id}>
                      <td>{dateTimeText(log.created_at)}</td>
                      <td><b>{log.user?.name ?? 'UNKNOWN'}</b>{log.user && <span className="subline">{log.user.badge_id}</span>}</td>
                      <td><StatusTag status={log.action} /></td>
                      <td>{log.subject ? <Link to={`/database/${log.subject.id}`}>{log.subject.name}<span className="subline">{log.subject.case_id}</span></Link> : '—'}</td>
                      <td>{detail(log)}</td>
                      <td>{log.ip_address ?? '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <EmptyState title="NO EVENTS MATCH THESE FILTERS" />
          )}
          <Pagination meta={query.data?.meta} onPage={setPage} />
        </QueryState>
      </Panel>
    </div>
  )
}
