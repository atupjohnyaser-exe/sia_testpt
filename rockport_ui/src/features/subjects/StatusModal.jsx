import { useState } from 'react'
import { Field, Modal, ModalActions } from '../../components/ui'
import { useChangeStatus } from '../../hooks/mutations'
import { fieldErrors } from '../../lib/errors'
import { label } from '../../lib/format'

const STATUSES = ['WANTED', 'IN_CUSTODY', 'PAROLED', 'CLEARED']

export function StatusModal({ subject, onClose }) {
  const change = useChangeStatus(subject.id)
  const options = STATUSES.filter((status) => status !== subject.status)
  const [status, setStatus] = useState(options[0])
  const [reason, setReason] = useState('')
  const [errors, setErrors] = useState({})

  const submit = async (event) => {
    event.preventDefault()
    setErrors({})

    try {
      await change.mutateAsync({ status, reason })
      onClose()
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  return (
    <Modal title="CHANGE STATUS" eyebrow={`${subject.case_id} // CURRENTLY ${label(subject.status)}`} onClose={onClose}>
      <form className="record-form" onSubmit={submit}>
        <Field label="NEW STATUS" error={errors.status}>
          <select value={status} onChange={(event) => setStatus(event.target.value)}>
            {options.map((option) => <option key={option} value={option}>{label(option)}</option>)}
          </select>
        </Field>
        <Field label="REASON (KEPT IN THE STATUS HISTORY)" error={errors.reason}>
          <textarea rows="3" value={reason} onChange={(event) => setReason(event.target.value)} required minLength={3} maxLength={500} />
        </Field>
        {errors.form && <p className="form-error">{errors.form}</p>}
        <ModalActions onClose={onClose} busy={change.isPending} submitLabel="CHANGE STATUS" />
      </form>
    </Modal>
  )
}
