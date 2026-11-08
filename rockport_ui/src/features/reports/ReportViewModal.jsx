import { useState } from 'react'
import { GuardedButton } from '../../components/GuardedButton'
import { Modal, StatusTag } from '../../components/ui'
import { useToast } from '../../context/toast'
import { useDeleteReport, useReviewReport } from '../../hooks/mutations'
import { errorMessage } from '../../lib/errors'
import { dateTimeText } from '../../lib/format'
import { composeAddress } from '../../lib/location'
import { ReportFormModal } from './ReportFormModal'

function Detail({ title, children }) {
  return <div className="detail-cell"><small>{title}</small><b>{children || '—'}</b></div>
}

export function ReportViewModal({ report, onClose }) {
  const toast = useToast()
  const review = useReviewReport()
  const remove = useDeleteReport()
  const [editing, setEditing] = useState(false)

  if (editing) return <ReportFormModal report={report} onClose={onClose} />

  const doReview = async () => {
    try {
      await review.mutateAsync(report.id)
      onClose()
    } catch (error) {
      toast(errorMessage(error, 'COULD NOT MARK THIS REPORT REVIEWED'))
    }
  }

  const doDelete = async () => {
    if (!window.confirm(`Delete report ${report.reference}? This cannot be undone.`)) return

    try {
      await remove.mutateAsync(report.id)
      onClose()
    } catch (error) {
      toast(errorMessage(error, 'COULD NOT DELETE THIS REPORT'))
    }
  }

  return (
    <Modal title={report.reference} eyebrow="FIELD REPORT" onClose={onClose} wide>
      <div className="record-form">
        <div className="detail-grid">
          <Detail title="STATUS"><StatusTag status={report.status} /></Detail>
          <Detail title="INCIDENT">{report.type?.name}</Detail>
          <Detail title="WHEN">{dateTimeText(report.occurred_at)}</Detail>
          <Detail title="WHERE">{[composeAddress(report.street, report.barangay), report.municipality].filter(Boolean).join(', ')}</Detail>
          <Detail title="FILED BY">{report.filed_by ? `${report.filed_by.name} (${report.filed_by.badge_id})` : ''}</Detail>
          <Detail title="FILED">{dateTimeText(report.created_at)}</Detail>
          <Detail title="PERSON INVOLVED">{report.person_name}</Detail>
          <Detail title="DESCRIPTION">{report.person_description}</Detail>
          {report.reviewed_at && <Detail title="REVIEWED BY">{report.reviewed_by}</Detail>}
          {report.reviewed_at && <Detail title="REVIEWED">{dateTimeText(report.reviewed_at)}</Detail>}
        </div>
        <div className="advisory"><small>WHAT HAPPENED</small><p className="pre">{report.narrative}</p></div>

        <div className="modal-actions spread">
          <GuardedButton level={5} action="Deleting a report" className="button-danger" onClick={doDelete} disabled={remove.isPending}>DELETE</GuardedButton>
          <span className="action-group">
            {report.can_edit && <button type="button" className="button button-outline" onClick={() => setEditing(true)}>EDIT</button>}
            {report.status === 'FILED' && (
              <GuardedButton level={2} action="Reviewing a report" className="button" onClick={doReview} disabled={review.isPending}>MARK REVIEWED</GuardedButton>
            )}
            <button type="button" className="button button-outline" onClick={onClose}>CLOSE</button>
          </span>
        </div>
      </div>
    </Modal>
  )
}
