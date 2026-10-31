import { useCallback, useState } from 'react'
import { Link } from 'react-router-dom'
import { EmptyState, PageHeading, Panel, QueryState } from '../../components/ui'
import { useHotkey } from '../../hooks/useHotkeys'
import { useRankings } from '../../hooks/queries'
import { duration, num } from '../../lib/format'

const FALLBACK_CATEGORIES = { length: 'Manhunt Length' }

export function RankingsPage() {
  const [scope, setScope] = useState('single')
  const [category, setCategory] = useState('length')
  const query = useRankings({ scope, category, limit: 10 })
  const categories = query.data?.meta?.categories ?? FALLBACK_CATEGORIES

  const toggleScope = useCallback(() => setScope((current) => (current === 'single' ? 'lifetime' : 'single')), [])

  // "1" flips between single manhunt and lifetime, like the key hint in the game.
  useHotkey('1', toggleScope)

  const show = (value) => (category === 'length' ? duration(value) : num(value))

  return (
    <div className="content-page">
      <PageHeading eyebrow="LEADERBOARD // ALL ACTIVE FILES" title="RANKINGS" action={<span className="online-indicator">LIVE</span>}>
        See who tops each category. Switch between the single biggest manhunt and lifetime totals.
      </PageHeading>

      <div className="scope-toggle">
        <button className={scope === 'single' ? 'active' : ''} onClick={() => setScope('single')}>SINGLE MANHUNT</button>
        <button className={scope === 'lifetime' ? 'active' : ''} onClick={() => setScope('lifetime')}>LIFETIME</button>
        <small>PRESS 1 TO SWITCH</small>
      </div>

      <div className="rank-layout">
        <nav className="category-menu" aria-label="Ranking categories">
          {Object.entries(categories).map(([key, title]) => (
            <button key={key} className={key === category ? 'active' : ''} onClick={() => setCategory(key)}>
              <span>›</span> {title.toUpperCase()}
            </button>
          ))}
        </nav>

        <Panel eyebrow={scope === 'single' ? 'SINGLE MANHUNT' : 'LIFETIME TOTAL'} title={(categories[category] ?? '').toUpperCase()}>
          <QueryState query={query} rows={5}>
            {query.data?.data.length ? (
              <div className="table-scroll">
                <table className="data-table rank-table">
                  <thead>
                    <tr><th>#</th><th>SUBJECT</th>{scope === 'single' && <th>MANHUNT</th>}<th className="num">{(categories[category] ?? '').toUpperCase()}</th></tr>
                  </thead>
                  <tbody>
                    {query.data.data.map((row) => (
                      <tr key={`${row.subject.id}-${row.manhunt_code ?? 'total'}`}>
                        <td className="rank-number">{row.rank}</td>
                        <td>
                          <Link to={`/database/${row.subject.id}`}><b>{row.subject.name}</b></Link>
                          <span className="subline">{row.subject.alias ? `AKA ${row.subject.alias} // ` : ''}{row.subject.case_id}</span>
                        </td>
                        {scope === 'single' && (
                          <td>{row.manhunt_code ? <Link to={`/database/${row.subject.id}/manhunts`}>{row.manhunt_code}</Link> : '—'}</td>
                        )}
                        <td className="num total">{show(row.value)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <EmptyState title="NO DATA YET" message="Rankings appear once manhunts have been logged." />
            )}
          </QueryState>
        </Panel>
      </div>
    </div>
  )
}
