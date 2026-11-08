import { useState } from 'react'
import { Field, Modal, StatusTag } from '../../components/ui'
import { useApproveRequest, useRejectRequest } from '../../hooks/mutations'
import { LEVELS, levelName } from '../../lib/clearance'
import { fieldErrors } from '../../lib/errors'
import { ageFrom, passwordProblem, validateText } from '../../lib/validation'
import { dateText, dateTimeText } from '../../lib/format'

function Detail({ title, children }) {
  return <div className="detail-cell"><small>{title}</small><b>{children || '—'}</b></div>
}

/** One application, in full, with the two decisions. */
export function ReviewModal({ request, onClose, onApproved }) {
  const approve = useApproveRequest()
  const reject = useRejectRequest()
  const pending = request.status === 'PENDING'
  const [level, setLevel] = useState(request.auto_level ? String(request.requested_level) : '')
  const [custom, setCustom] = useState(false)
  const [password, setPassword] = useState('')
  const [note, setNote] = useState('')
  const [rejecting, setRejecting] = useState(false)
  const [errors, setErrors] = useState({})

  const levels = Array.from({ length: request.max_level }, (_, index) => index + 1)

  const doApprove = async () => {
    const problems = {}

    if (level === '') problems.level = 'Choose a level for this officer.'
    if (custom && !password) problems.password = 'Type a password, or switch to a generated one.'
    else if (custom && passwordProblem(password)) problems.password = passwordProblem(password)

    const noteProblem = validateText(note, { label: 'Note', max: 300 })
    if (noteProblem) problems.note = noteProblem

    if (Object.keys(problems).length > 0) {
      setErrors(problems)

      return
    }

    setErrors({})

    try {
      const result = await approve.mutateAsync({ id: request.id, body: { level: Number(level), password: custom ? password : undefined, note: note.trim() || undefined } })

      onApproved(result.credentials)
      onClose()
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  const doReject = async () => {
    const problem = validateText(note, { label: 'Reason', required: true, min: 5, max: 300 })

    if (problem) {
      setErrors({ note: problem })

      return
    }

    setErrors({})

    try {
      await reject.mutateAsync({ id: request.id, body: { note } })
      onClose()
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  const age = ageFrom(request.date_of_birth)

  return (
    <Modal title={request.full_name.toUpperCase()} eyebrow={`APPLICATION ${request.reference}`} onClose={onClose} wide>
      <div className="record-form">
        <div className="detail-grid">
          <Detail title="STATUS"><StatusTag status={request.status} /></Detail>
          <Detail title="RANK">{request.rank}</Detail>
          <Detail title="HIGHEST LEVEL FOR RANK">LEVEL {request.max_level} // {levelName(request.max_level).toUpperCase()}</Detail>
          <Detail title="DATE OF BIRTH">{dateText(request.date_of_birth)}{age !== null ? ` (${age})` : ''}</Detail>
          <Detail title="MOBILE">{request.phone}</Detail>
          <Detail title="EMAIL">{request.email}</Detail>
          <Detail title="STATION">{request.station}</Detail>
          <Detail title="YEARS OF SERVICE">{request.years_of_service}</Detail>
          <Detail title="HOME ADDRESS">{request.address}</Detail>
          <Detail title="SUBMITTED">{dateTimeText(request.created_at)}</Detail>
        </div>

        <div className="advisory"><small>WHY THEY NEED ACCESS</small><p>{request.justification}</p></div>

        {request.auto_level && pending && (
          <div className="level-preview auto">
            <small>DETECTIVE</small>
            <b>LEVEL 3 WAS SET AUTOMATICALLY</b>
            <span>It still needs your review. You can approve at level 3, or at a lower level.</span>
          </div>
        )}

        {!pending && (
          <div className="detail-grid">
            <Detail title="REVIEWED BY">{request.reviewed_by}</Detail>
            <Detail title="REVIEWED">{dateTimeText(request.reviewed_at)}</Detail>
            {request.status === 'APPROVED' && <Detail title="BADGE ID">{request.badge_id}</Detail>}
            {request.status === 'APPROVED' && <Detail title="APPROVED LEVEL">{request.approved_level}</Detail>}
            {request.review_note && <Detail title="NOTE">{request.review_note}</Detail>}
          </div>
        )}

        {pending && !rejecting && (
          <>
            <small className="eyebrow">APPROVE</small>
            <div className="form-grid">
              <Field label="CLEARANCE LEVEL" error={errors.level}>
                <select value={level} onChange={(event) => setLevel(event.target.value)}>
                  {!request.auto_level && <option value="">CHOOSE A LEVEL</option>}
                  {levels.map((value) => <option key={value} value={value}>{value} // {levelName(value).toUpperCase()}</option>)}
                </select>
              </Field>
              <Field label="PASSWORD">
                <select value={custom ? 'custom' : 'generate'} onChange={(event) => setCustom(event.target.value === 'custom')}>
                  <option value="generate">GENERATE A STRONG ONE</option>
                  <option value="custom">I WILL TYPE ONE</option>
                </select>
              </Field>
            </div>
            {level !== '' && <p className="form-hint">{levelName(Number(level))}: {LEVELS[level].summary}</p>}
            {custom && (
              <Field label="PASSWORD (12 OR MORE, LETTERS AND NUMBERS)" error={errors.password}>
                <input value={password} onChange={(event) => setPassword(event.target.value)} maxLength={128} autoComplete="off" />
              </Field>
            )}
            <Field label="NOTE (OPTIONAL)" error={errors.note}>
              <input value={note} onChange={(event) => setNote(event.target.value)} maxLength={300} />
            </Field>
            <p className="form-hint">A random badge ID is created. The password is shown to you once, to hand over in person.</p>
            {errors.form && <p className="form-error">{errors.form}</p>}
            {errors.request && <p className="form-error">{errors.request}</p>}
            <div className="modal-actions">
              <button type="button" className="button-danger" onClick={() => setRejecting(true)}>REJECT…</button>
              <button type="button" className={`button ${approve.isPending ? 'is-busy' : ''}`} disabled={approve.isPending} onClick={doApprove}>
                {approve.isPending ? 'CREATING…' : 'APPROVE AND CREATE ACCOUNT'}
              </button>
            </div>
          </>
        )}

        {pending && rejecting && (
          <>
            <small className="eyebrow">REJECT</small>
            <Field label={`REASON (THE APPLICANT CAN READ IT, ${note.length}/300)`} error={errors.note}>
              <textarea rows="3" value={note} onChange={(event) => setNote(event.target.value)} maxLength={300} />
            </Field>
            {errors.form && <p className="form-error">{errors.form}</p>}
            <div className="modal-actions">
              <button type="button" className="button button-outline" onClick={() => setRejecting(false)}>BACK</button>
              <button type="button" className="button-danger" disabled={reject.isPending} onClick={doReject}>{reject.isPending ? 'SAVING…' : 'REJECT APPLICATION'}</button>
            </div>
          </>
        )}

        {!pending && <div className="modal-actions"><button type="button" className="button button-outline" onClick={onClose}>CLOSE</button></div>}
      </div>
    </Modal>
  )
}
