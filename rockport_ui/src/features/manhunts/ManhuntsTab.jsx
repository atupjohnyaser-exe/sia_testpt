import { useState } from 'react'
import { Link, useNavigate, useOutletContext } from 'react-router-dom'
import { EmptyState, Pagination, Panel, QueryState, StatusTag } from '../../components/ui'
import { useAuth } from '../../context/auth'
import { useSubjectManhunts } from '../../hooks/queries'
import { dateTimeText, num } from '../../lib/format'
import { LogManhuntModal } from './LogManhuntModal'

export function ManhuntsTab() {
  const { subject } = useOutletContext()
  const { can } = useAuth()
  const navigate = useNavigate()
  const [sort, setSort] = useState('bounty')
  const [page, setPage] = useState(1)
  const [logging, setLogging] = useState(false)
  const query = useSubjectManhunts(subject.id, { sort, page, per_page: 10 })

  return (
    <>
      <Panel
        eyebrow="TOP MANHUNTS FIRST"
        title="MANHUNTS"
        action={
          <div className="panel-actions">
            <label className="filter-select">
              <small>SORT</small>
              <select value={sort} onChange={(event) => { setSort(event.target.value); setPage(1) }}>
                <option value="bounty">BOUNTY</option>
                <option value="length">LENGTH</option>
                <option value="recent">NEWEST</option>
              </select>
            </label>
            {can(2) && <button className="button button-outline" onClick={() => setLogging(true)}>+ LOG MANHUNT</button>}
          </div>
        }
      >
        <QueryState query={query}>
          {query.data?.data.length ? (
            <div className="manhunt-list">
              {query.data.data.map((manhunt, index) => (
                <Link key={manhunt.id} to={`/database/${subject.id}/manhunts/${manhunt.id}`} className="manhunt-row">
                  <span className="manhunt-rank">{(page - 1) * 10 + index + 1}</span>
                  <span className="manhunt-main">
                    <b>{manhunt.code}</b>
                    <small>{manhunt.district.toUpperCase()} // {dateTimeText(manhunt.started_at)}</small>
                  </span>
                  <span className="manhunt-stat"><small>LENGTH</small><b>{manhunt.duration_label}</b></span>
                  <span className="manhunt-stat"><small>UNITS</small><b>{num(manhunt.officers_involved)}</b></span>
                  <span className="manhunt-stat"><small>BOUNTY</small><b>{num(manhunt.bounty_value)}</b></span>
                  <StatusTag status={manhunt.outcome} />
                </Link>
              ))}
            </div>
          ) : (
            <EmptyState title="NO MANHUNTS LOGGED" message="Log one when officers pursue this person." />
          )}
          <Pagination meta={query.data?.meta} onPage={setPage} />
        </QueryState>
      </Panel>

      {logging && (
        <LogManhuntModal
          subject={subject}
          onClose={() => setLogging(false)}
          onLogged={(manhunt) => navigate(`/database/${subject.id}/manhunts/${manhunt.id}`)}
        />
      )}
    </>
  )
}
