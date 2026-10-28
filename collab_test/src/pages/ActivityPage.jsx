import { Link } from 'react-router-dom'
import { EmptyState, ErrorState, Panel, StatusTag } from '../components/UI'
import { useAuditLogs } from '../hooks/api'

export function ActivityPage() {
  const query = useAuditLogs({ per_page: 50 })
  const logs = query.data?.data || []
  return <div className="content-page">
    <div className="page-heading"><div><small className="eyebrow">SECURITY OVERSIGHT // LOG 04</small><h1>AUDIT LOG</h1><p>Authorized record access and changes are logged for review.</p></div><span className="online-indicator">ENCRYPTED</span></div>
    <Panel eyebrow="ACCESS HISTORY" title="RECENT ACTIVITY">
      {query.isError ? <ErrorState onRetry={() => query.refetch()} /> : <div className="table-scroll"><table><thead><tr><th>TIMESTAMP</th><th>OFFICER</th><th>ACTION</th><th>SUBJECT / CASE</th><th>DETAILS</th></tr></thead><tbody>{logs.map((log) => <tr key={log.id}><td>{new Date(log.created_at).toLocaleString()}</td><td>{log.user?.name || 'SYSTEM'}<small className="subline">{log.user?.badge_id || ''}</small></td><td><StatusTag status={log.action} /></td><td>{log.subject ? <Link to={`/database/${log.subject.id}`}>{log.subject.name}<small className="subline">{log.subject.case_id}</small></Link> : '—'}</td><td>{log.meta ? Object.entries(log.meta).map(([key, value]) => `${key}: ${value}`).join(' · ') : '—'}</td></tr>)}</tbody></table>{query.isLoading && <div className="table-loading">LOADING ACTIVITY…</div>}{!query.isLoading && logs.length === 0 && <EmptyState title="NO ACTIVITY LOGGED" />}</div>}
    </Panel>
  </div>
}
