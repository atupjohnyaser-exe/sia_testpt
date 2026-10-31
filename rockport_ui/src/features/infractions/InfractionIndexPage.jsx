import { useState } from 'react'
import { Link } from 'react-router-dom'
import { EmptyState, Pagination, PageHeading, Panel, QueryState, StatusTag } from '../../components/ui'
import { useInfractionTypes, useInfractions } from '../../hooks/queries'
import { dateText, num } from '../../lib/format'

export function InfractionIndexPage() {
  const [type, setType] = useState('')
  const [served, setServed] = useState('')
  const [page, setPage] = useState(1)
  const types = useInfractionTypes()

  const params = { page, per_page: 25 }
  if (type) params.type = type
  if (served) params.served = served

  const query = useInfractions(params)

  return (
    <div className="content-page">
      <PageHeading eyebrow="ALL FILES" title="INFRACTION INDEX">
        Browse every recorded infraction across all subject files.
      </PageHeading>

      <Panel
        eyebrow="CHARGES"
        title="ALL INFRACTIONS"
        action={
          <div className="panel-actions">
            <label className="filter-select"><small>TYPE</small>
              <select value={type} onChange={(event) => { setType(event.target.value); setPage(1) }}>
                <option value="">ALL TYPES</option>
                {types.data?.data?.map((item) => <option key={item.code} value={item.code}>{item.name.toUpperCase()}</option>)}
              </select>
            </label>
            <label className="filter-select"><small>SHOW</small>
              <select value={served} onChange={(event) => { setServed(event.target.value); setPage(1) }}>
                <option value="">ALL</option>
                <option value="false">UNSERVED</option>
                <option value="true">SERVED</option>
              </select>
            </label>
          </div>
        }
      >
        <QueryState query={query} rows={6}>
          {query.data?.data.length ? (
            <div className="table-scroll">
              <table className="data-table">
                <thead><tr><th>DATE</th><th>SUBJECT</th><th>INFRACTION</th><th>LOCATION</th><th className="num">BOUNTY</th><th>STATE</th></tr></thead>
                <tbody>
                  {query.data.data.map((infraction) => (
                    <tr key={infraction.id}>
                      <td>{dateText(infraction.occurred_on)}</td>
                      <td>{infraction.subject ? <Link to={`/database/${infraction.subject.id}/infractions`}><b>{infraction.subject.name}</b><span className="subline">{infraction.subject.case_id}</span></Link> : '—'}</td>
                      <td><b>{infraction.type?.name}</b> <StatusTag status={infraction.type?.severity} /></td>
                      <td>{infraction.location}</td>
                      <td className="num">{num(infraction.bounty_value)}</td>
                      <td><span className={`status-tag status-${infraction.served ? 'cleared' : 'wanted'}`}>{infraction.served ? 'SERVED' : 'UNSERVED'}</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <EmptyState title="NO INFRACTIONS MATCH" />
          )}
          <Pagination meta={query.data?.meta} onPage={setPage} />
        </QueryState>
      </Panel>
    </div>
  )
}
