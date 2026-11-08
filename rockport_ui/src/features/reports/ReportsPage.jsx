import { useState } from 'react'
import { EmptyState, PageHeading, Pagination, Panel, QueryState, StatusTag } from '../../components/ui'
import { useAuth } from '../../context/auth'
import { useReports } from '../../hooks/queries'
import { dateTimeText } from '../../lib/format'
import { ReportFormModal } from './ReportFormModal'
import { ReportViewModal } from './ReportViewModal'

export function ReportsPage() {
  const { can } = useAuth()
  const seesAll = can(2)
  const [status, setStatus] = useState('')
  const [mine, setMine] = useState(false)
  const [page, setPage] = useState(1)
  const [filing, setFiling] = useState(false)
  const [open, setOpen] = useState(null)

  const params = { page, per_page: 12 }
  if (status) params.status = status
  if (seesAll && mine) params.mine = 'true'

  const query = useReports(params)

  return (
    <div className="content-page">
      <PageHeading
        eyebrow={seesAll ? 'ALL OFFICERS' : 'YOUR REPORTS'}
        title={seesAll ? 'FIELD REPORTS' : 'MY REPORTS'}
        action={<button className="button" onClick={() => setFiling(true)}>+ FILE A REPORT</button>}
      >
        {seesAll
          ? 'Every report filed by officers. Review them, and correct them if needed.'
          : 'File a report about an incident. You can edit your own reports until a Senior Officer reviews them.'}
      </PageHeading>

      <Panel
        eyebrow="REPORTS"
        title={seesAll ? 'ALL REPORTS' : 'FILED BY YOU'}
        action={
          <div className="panel-actions">
            {seesAll && (
              <label className="check-filter">
                <input type="checkbox" checked={mine} onChange={(event) => { setMine(event.target.checked); setPage(1) }} />
                MINE ONLY
              </label>
            )}
            <label className="filter-select"><small>STATUS</small>
              <select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1) }}>
                <option value="">ALL</option>
                <option value="FILED">FILED</option>
                <option value="REVIEWED">REVIEWED</option>
              </select>
            </label>
          </div>
        }
      >
        <QueryState query={query} rows={5}>
          {query.data?.data.length ? (
            <div className="table-scroll">
              <table className="data-table">
                <thead>
                  <tr><th>REFERENCE</th><th>INCIDENT</th><th>WHERE</th><th>WHEN</th>{seesAll && <th>FILED BY</th>}<th>STATUS</th><th></th></tr>
                </thead>
                <tbody>
                  {query.data.data.map((report) => (
                    <tr key={report.id}>
                      <td className="col-muted">{report.reference}</td>
                      <td className="cell-main">
                        <b>{report.type?.name ?? 'Not specified'}</b>
                        {report.person_name && <span className="cell-sub">{report.person_name}</span>}
                      </td>
                      <td className="cell-main"><b>{report.municipality}</b>{report.barangay && <span className="cell-sub">Brgy. {report.barangay}</span>}</td>
                      <td className="col-muted">{dateTimeText(report.occurred_at)}</td>
                      {seesAll && <td className="cell-main"><b>{report.filed_by?.name}</b><span className="cell-sub">{report.filed_by?.badge_id}</span></td>}
                      <td><StatusTag status={report.status} /></td>
                      <td className="col-action"><button className="mini-button" onClick={() => setOpen(report)}>OPEN</button></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <EmptyState title="NO REPORTS YET" message="Use FILE A REPORT to add the first one." />
          )}
          <Pagination meta={query.data?.meta} onPage={setPage} />
        </QueryState>
      </Panel>

      {filing && <ReportFormModal onClose={() => setFiling(false)} />}
      {open && <ReportViewModal report={open} onClose={() => setOpen(null)} />}
    </div>
  )
}
