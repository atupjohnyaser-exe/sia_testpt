import { useState } from 'react'
import { EmptyState, Pagination, Panel, QueryState, StatusTag } from '../../components/ui'
import { useToast } from '../../context/toast'
import { useRestoreSubject } from '../../hooks/mutations'
import { useArchivedSubjects } from '../../hooks/queries'
import { errorMessage } from '../../lib/errors'
import { dateText, num } from '../../lib/format'

export function ArchivedTab() {
  const toast = useToast()
  const [page, setPage] = useState(1)
  const query = useArchivedSubjects({ page, per_page: 12 })
  const restore = useRestoreSubject()

  const restoreRecord = async (subject) => {
    if (!window.confirm(`Restore ${subject.name}? The record comes back with its full history.`)) return

    try {
      await restore.mutateAsync(subject.id)
    } catch (error) {
      toast(errorMessage(error, 'COULD NOT RESTORE THIS RECORD'))
    }
  }

  return (
    <Panel eyebrow="REMOVED FROM THE DATABASE BY A SUPERVISOR" title="ARCHIVED RECORDS">
      <QueryState query={query} rows={4}>
        {query.data?.data.length ? (
          <div className="table-scroll">
            <table className="data-table">
              <thead><tr><th>CASE</th><th>NAME</th><th>STATUS</th><th className="num">BOUNTY</th><th>ARCHIVED</th><th></th></tr></thead>
              <tbody>
                {query.data.data.map((subject) => (
                  <tr key={subject.id}>
                    <td className="col-muted">{subject.case_id}</td>
                    <td className="cell-main"><b>{subject.name}</b><span className="cell-sub">{subject.alias ? `AKA ${subject.alias}` : '—'}</span></td>
                    <td><StatusTag status={subject.status} /></td>
                    <td className="num">{num(subject.bounty)}</td>
                    <td className="col-muted">{dateText(subject.archived_at)}</td>
                    <td className="col-action"><button className="mini-button" disabled={restore.isPending} onClick={() => restoreRecord(subject)}>RESTORE</button></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <EmptyState title="NO ARCHIVED RECORDS" message="Records a supervisor archives show up here, and can be restored." />
        )}
        <Pagination meta={query.data?.meta} onPage={setPage} />
      </QueryState>
    </Panel>
  )
}
