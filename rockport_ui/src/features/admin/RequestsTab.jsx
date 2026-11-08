import { useState } from 'react'
import { EmptyState, Pagination, Panel, QueryState, StatusTag } from '../../components/ui'
import { useAccessRequests } from '../../hooks/queries'
import { dateTimeText } from '../../lib/format'
import { CredentialsModal } from './CredentialsModal'
import { ReviewModal } from './ReviewModal'

export function RequestsTab() {
  const [status, setStatus] = useState('PENDING')
  const [page, setPage] = useState(1)
  const [open, setOpen] = useState(null)
  const [credentials, setCredentials] = useState(null)
  const query = useAccessRequests({ status: status || undefined, page, per_page: 10 })

  return (
    <>
      <Panel
        eyebrow="APPLICATIONS FOR ACCESS"
        title="REVIEW QUEUE"
        action={
          <label className="filter-select">
            <small>SHOW</small>
            <select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1) }}>
              <option value="PENDING">WAITING</option>
              <option value="APPROVED">APPROVED</option>
              <option value="REJECTED">REJECTED</option>
              <option value="">ALL</option>
            </select>
          </label>
        }
      >
        <QueryState query={query} rows={4}>
          {query.data?.data.length ? (
            <div className="table-scroll">
              <table className="data-table">
                <thead>
                  <tr><th>REFERENCE</th><th>APPLICANT</th><th>RANK</th><th>STATION</th><th>SUBMITTED</th><th>STATUS</th><th></th></tr>
                </thead>
                <tbody>
                  {query.data.data.map((request) => (
                    <tr key={request.id}>
                      <td className="col-muted">{request.reference}</td>
                      <td className="cell-main"><b>{request.full_name}</b><span className="cell-sub">{request.phone}</span></td>
                      <td>
                        <div className="cell-inline">
                          <span>{request.rank}</span>
                          <span className={`level-chip l${request.requested_level}`}>L{request.requested_level}{request.auto_level ? ' AUTO' : ''}</span>
                        </div>
                      </td>
                      <td className="col-muted">{request.station}</td>
                      <td className="col-muted">{dateTimeText(request.created_at)}</td>
                      <td><StatusTag status={request.status} /></td>
                      <td className="col-action"><button className="mini-button" onClick={() => setOpen(request)}>{request.status === 'PENDING' ? 'REVIEW' : 'VIEW'}</button></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <EmptyState title="NOTHING WAITING" message="New applications from the request access page appear here." />
          )}
          <Pagination meta={query.data?.meta} onPage={setPage} />
        </QueryState>
      </Panel>

      {open && <ReviewModal request={open} onClose={() => setOpen(null)} onApproved={setCredentials} />}
      {credentials && <CredentialsModal credentials={credentials} onClose={() => setCredentials(null)} />}
    </>
  )
}
