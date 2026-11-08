import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { endpoints } from '../../api/endpoints'
import { PublicShell } from '../../components/PublicShell'
import { StatusTag } from '../../components/ui'
import { dateTimeText } from '../../lib/format'
import { levelName } from '../../lib/clearance'

const MEANING = {
  PENDING: 'The Chief Admin has not reviewed this yet. Check again later.',
  APPROVED: 'Approved. Your account exists. Collect your password from the Chief Admin in person.',
  REJECTED: 'This application was not approved.',
}

export function AccessStatusPage() {
  const [params, setParams] = useSearchParams()
  const [typed, setTyped] = useState(params.get('ref') ?? '')
  const reference = params.get('ref') ?? ''

  const query = useQuery({
    queryKey: ['access-status', reference],
    queryFn: () => endpoints.accessStatus(reference),
    enabled: /^ACC-[A-Z0-9]{10}$/.test(reference),
    retry: false,
  })

  const check = (event) => {
    event.preventDefault()
    setParams({ ref: typed.trim().toUpperCase() })
  }

  const data = query.data?.data
  const valid = /^ACC-[A-Z0-9]{10}$/.test(reference)

  return (
    <PublicShell eyebrow="CHECK AN APPLICATION" title="APPLICATION STATUS" intro="Enter the reference number you were given when you applied.">
      <form className="status-form" onSubmit={check}>
        <input value={typed} onChange={(event) => setTyped(event.target.value.toUpperCase())} maxLength={14} placeholder="ACC-XXXXXXXXXX" aria-label="Reference number" />
        <button className="button">CHECK</button>
      </form>

      {reference && !valid && <p className="form-error">A reference looks like ACC-ABCDE12345.</p>}
      {query.isLoading && <p className="form-hint">CHECKING…</p>}
      {query.isError && valid && (
        <p className="form-error">
          {query.error?.response?.status === 404 ? 'No application has that reference number.' : 'Could not check right now. Try again in a moment.'}
        </p>
      )}

      {data && (
        <div className="status-result">
          <div><small>REFERENCE</small><b>{data.reference}</b></div>
          <div><small>STATUS</small><StatusTag status={data.status} /></div>
          <div><small>RANK</small><b>{data.rank}</b></div>
          <div><small>SUBMITTED</small><b>{dateTimeText(data.submitted_at)}</b></div>
          {data.reviewed_at && <div><small>REVIEWED</small><b>{dateTimeText(data.reviewed_at)}</b></div>}
          <p>{MEANING[data.status]}</p>
          {data.status === 'APPROVED' && (
            <div className="badge-box">
              <small>YOUR BADGE ID</small>
              <strong>{data.badge_id}</strong>
              <span>Level {data.approved_level} // {levelName(data.approved_level)}. The password is handed over by the Chief Admin.</span>
            </div>
          )}
          {data.status === 'REJECTED' && data.note && <div className="advisory"><small>REASON</small><p>{data.note}</p></div>}
        </div>
      )}

      <p className="public-links"><Link to="/request-access">Apply for access</Link> · <Link to="/login">Back to sign in</Link></p>
    </PublicShell>
  )
}
