import { useState } from 'react'
import { EmptyState, Pagination, Panel, QueryState, StatusTag } from '../../components/ui'
import { useToast } from '../../context/toast'
import { useIssuePassword, useSetPersonnelActive } from '../../hooks/mutations'
import { usePersonnel } from '../../hooks/queries'
import { levelName } from '../../lib/clearance'
import { errorMessage } from '../../lib/errors'
import { dateTimeText } from '../../lib/format'
import { CredentialsModal } from './CredentialsModal'
import { PersonnelFormModal } from './PersonnelFormModal'

export function PersonnelTab() {
  const toast = useToast()
  const [search, setSearch] = useState('')
  const [level, setLevel] = useState('')
  const [active, setActive] = useState('')
  const [page, setPage] = useState(1)
  const [editing, setEditing] = useState(null)
  const [credentials, setCredentials] = useState(null)
  const issue = useIssuePassword()
  const toggle = useSetPersonnelActive()

  const params = { page, per_page: 12 }
  if (search.trim()) params.search = search.trim()
  if (level) params.level = level
  if (active) params.active = active

  const query = usePersonnel(params)

  const issuePassword = async (person) => {
    if (!window.confirm(`Issue a new password for ${person.name}? They are signed out everywhere and the old password stops working.`)) return

    try {
      const result = await issue.mutateAsync({ id: person.id, body: {} })

      setCredentials(result.credentials)
    } catch (error) {
      toast(errorMessage(error, 'COULD NOT ISSUE A PASSWORD'))
    }
  }

  const setStatus = async (person, next) => {
    if (!next && !window.confirm(`Deactivate ${person.name}? They are signed out and cannot sign in until you activate the account again.`)) return

    try {
      await toggle.mutateAsync({ id: person.id, active: next })
    } catch (error) {
      toast(errorMessage(error, 'COULD NOT CHANGE THIS ACCOUNT'))
    }
  }

  return (
    <>
      <Panel
        eyebrow="PEOPLE WITH ACCESS"
        title="PERSONNEL"
        action={
          <div className="panel-actions">
            <input className="filter-input" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1) }} placeholder="NAME, BADGE OR STATION" aria-label="Search personnel" maxLength={100} />
            <label className="filter-select"><small>LEVEL</small>
              <select value={level} onChange={(event) => { setLevel(event.target.value); setPage(1) }}>
                <option value="">ALL</option>
                {[1, 2, 3, 4, 5].map((value) => <option key={value} value={value}>{value}</option>)}
              </select>
            </label>
            <label className="filter-select"><small>ACCOUNT</small>
              <select value={active} onChange={(event) => { setActive(event.target.value); setPage(1) }}>
                <option value="">ALL</option>
                <option value="true">ACTIVE</option>
                <option value="false">INACTIVE</option>
              </select>
            </label>
            <button className="button" onClick={() => setEditing({})}>+ ADD PERSONNEL</button>
          </div>
        }
      >
        <QueryState query={query} rows={5}>
          {query.data?.data.length ? (
            <div className="table-scroll">
              <table className="data-table">
                <thead>
                  <tr><th>BADGE</th><th>NAME</th><th>RANK</th><th>LEVEL</th><th>STATION</th><th>LAST SIGN IN</th><th>ACCOUNT</th><th></th></tr>
                </thead>
                <tbody>
                  {query.data.data.map((person) => (
                    <tr key={person.id}>
                      <td className="col-muted">{person.badge_id}</td>
                      <td className="cell-main"><b>{person.name}</b><span className="cell-sub">{person.phone || person.email || '—'}</span></td>
                      <td className="col-muted">{person.rank}</td>
                      <td><span className={`level-chip l${person.clearance_level}`}>L{person.clearance_level} {levelName(person.clearance_level).toUpperCase()}</span></td>
                      <td className="col-muted">{person.station || '—'}</td>
                      <td className="col-muted">{person.last_login_at ? dateTimeText(person.last_login_at) : 'NEVER'}</td>
                      <td><StatusTag status={person.is_active ? 'ACTIVE' : 'INACTIVE'} /></td>
                      <td className="col-action">
                        {person.protected ? (
                          <span className="cell-sub">COMMAND LINE ONLY</span>
                        ) : (
                          <div className="row-actions">
                            <button className="mini-button" onClick={() => setEditing(person)}>EDIT</button>
                            <button className="mini-button" onClick={() => issuePassword(person)}>PASSWORD</button>
                            <button className={`mini-button ${person.is_active ? 'danger' : ''}`} onClick={() => setStatus(person, !person.is_active)}>
                              {person.is_active ? 'DEACTIVATE' : 'ACTIVATE'}
                            </button>
                          </div>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <EmptyState title="NO ONE MATCHES" />
          )}
          <Pagination meta={query.data?.meta} onPage={setPage} />
        </QueryState>
      </Panel>

      {editing && <PersonnelFormModal person={editing.id ? editing : null} onClose={() => setEditing(null)} onCreated={setCredentials} />}
      {credentials && <CredentialsModal credentials={credentials} onClose={() => setCredentials(null)} />}
    </>
  )
}
