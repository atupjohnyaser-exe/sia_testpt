import { useState } from 'react'
import { Field, Modal, ModalActions } from '../../components/ui'
import { useThreatOverride } from '../../hooks/mutations'
import { fieldErrors } from '../../lib/errors'
import { LIMITS, validateReason } from '../../lib/validation'

const LEVELS = [
  [1, 'ROUTINE'],
  [2, 'ELEVATED'],
  [3, 'HIGH'],
  [4, 'SEVERE'],
  [5, 'CRITICAL'],
  [6, 'MAXIMUM'],
]

/** Supervisors only. Pins the threat level instead of using the one worked out from the bounty. */
export function ThreatOverrideModal({ subject, onClose }) {
  const override = useThreatOverride(subject.id)
  const [level, setLevel] = useState(subject.threat.overridden ? String(subject.threat.level) : '')
  const [reason, setReason] = useState(subject.threat.override_reason ?? '')
  const [errors, setErrors] = useState({})

  const submit = async (event) => {
    event.preventDefault()

    if (level !== '') {
      const problems = validateReason(reason, { min: 5 })

      if (Object.keys(problems).length > 0) {
        setErrors(problems)

        return
      }
    }

    setErrors({})

    try {
      await override.mutateAsync({ level: level === '' ? null : Number(level), reason: level === '' ? null : reason })
      onClose()
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  return (
    <Modal title="THREAT LEVEL OVERRIDE" eyebrow={`${subject.case_id} // SUPERVISOR ACTION`} onClose={onClose}>
      <form className="record-form" onSubmit={submit} noValidate>
        <Field label="THREAT LEVEL" error={errors.level}>
          <select value={level} onChange={(event) => setLevel(event.target.value)}>
            <option value="">AUTOMATIC (FROM BOUNTY)</option>
            {LEVELS.map(([value, name]) => <option key={value} value={value}>{value} // {name}</option>)}
          </select>
        </Field>
        {level !== '' && (
          <Field label={`REASON (${reason.length}/${LIMITS.reason})`} error={errors.reason}>
            <textarea rows="3" value={reason} onChange={(event) => setReason(event.target.value)} maxLength={LIMITS.reason} />
          </Field>
        )}
        {errors.form && <p className="form-error">{errors.form}</p>}
        <ModalActions onClose={onClose} busy={override.isPending} submitLabel="APPLY" />
      </form>
    </Modal>
  )
}
