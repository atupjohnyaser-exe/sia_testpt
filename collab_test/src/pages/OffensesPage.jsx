import { useState } from 'react'
import { Link } from 'react-router-dom'
import { EmptyState, ErrorState, Panel, StatusTag } from '../components/UI'
import { useCrimeCategories, useOffenses } from '../hooks/api'

export function OffensesPage() {
  const [category, setCategory] = useState('')
  const query = useOffenses(null, { category: category || undefined, per_page: 50 })
  const categoryQuery = useCrimeCategories()
  const categories = categoryQuery.data?.data || []
  const rows = query.data?.data || []
  return <div className="content-page">
    <div className="page-heading"><div><small className="eyebrow">RECORDS NETWORK // INDEX 03</small><h1>OFFENSE INDEX</h1><p>Search and review recorded charges across active case files.</p></div><span className="online-indicator">SYSTEM ONLINE</span></div>
    <Panel eyebrow="CASE INTELLIGENCE" title="ALL RECORDED OFFENSES" action={<label className="filter-select"><small>FILTER CATEGORY</small><select value={category} onChange={(event) => setCategory(event.target.value)}><option value="">ALL CATEGORIES</option>{categories.map((item) => <option key={item.url || item.name} value={item.url || item.name}>{item.name}</option>)}</select></label>}>
      {query.isError ? <ErrorState onRetry={() => query.refetch()} /> : <div className="table-scroll"><table><thead><tr><th>DATE</th><th>CHARGE</th><th>SUBJECT</th><th>LOCATION</th><th>DISPOSITION</th><th>SEVERITY</th></tr></thead><tbody>{rows.map((row) => <tr key={row.id}><td>{row.occurred_on || '—'}</td><td><b>{row.charge}</b><small className="subline">{row.category}</small></td><td>{row.subject ? <Link to={`/database/${row.subject.id}`}>{row.subject.name}<small className="subline">{row.subject.case_id}</small></Link> : row.subject_id}</td><td>{row.location || '—'}</td><td>{row.disposition || '—'}</td><td><StatusTag status={row.severity} /></td></tr>)}</tbody></table>{query.isLoading && <div className="table-loading">LOADING INDEX…</div>}{!query.isLoading && rows.length === 0 && <EmptyState title="NO OFFENSES FOUND" />}</div>}
    </Panel>
    <div className="training-strip"><span>FICTIONAL TRAINING DATA</span><span>INCIDENTS IN THIS TRAINING SYSTEM ARE ANONYMOUS AND FOR DEMONSTRATION ONLY</span></div>
  </div>
}
