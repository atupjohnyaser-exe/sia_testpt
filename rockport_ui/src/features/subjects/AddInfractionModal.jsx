import { useState } from 'react'
import { Field, Modal, ModalActions } from '../../components/ui'
import { useInfractionTypes } from '../../hooks/queries'
import { useAddInfraction } from '../../hooks/mutations'
import { fieldErrors } from '../../lib/errors'
import { localDateValue, num } from '../../lib/format'

export function AddInfractionModal({ subject, onClose }) {
  const types = useInfractionTypes()
  const add = useAddInfraction(subject.id)
  const [today] = useState(() => localDateValue())
  const [form, setForm] = useState(() => ({ infraction_type_id: '', occurred_on: localDateValue(), location: subject.last_known_location ?? '', disposition: '' }))
  const [errors, setErrors] = useState({})

  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }))
  const chosen = types.data?.data?.find((type) => String(type.id) === String(form.infraction_type_id))

  const submit = async (event) => {
    event.preventDefault()
    setErrors({})

    try {
      await add.mutateAsync({ ...form, infraction_type_id: Number(form.infraction_type_id) })
      onClose()
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  return (
    <Modal title="RECORD INFRACTION" eyebrow={`${subject.case_id} // ${subject.name.toUpperCase()}`} onClose={onClose}>
      <form className="record-form" onSubmit={submit}>
        <Field label="INFRACTION TYPE" error={errors.infraction_type_id}>
          <select value={form.infraction_type_id} onChange={set('infraction_type_id')} required>
            <option value="">SELECT A TYPE</option>
            {types.data?.data?.map((type) => <option key={type.id} value={type.id}>{type.name} ({type.severity})</option>)}
          </select>
        </Field>
        {chosen && <p className="form-hint">Adds {num(chosen.bounty_value)} to the bounty and {num(chosen.fine_amount)} in fines until it is served.</p>}
        <div className="form-grid">
          <Field label="DATE" error={errors.occurred_on}>
            <input type="date" value={form.occurred_on} max={today} onChange={set('occurred_on')} required />
          </Field>
          <Field label="LOCATION" error={errors.location}>
            <input value={form.location} onChange={set('location')} required />
          </Field>
        </div>
        <Field label="DISPOSITION (OPTIONAL)" error={errors.disposition}>
          <input value={form.disposition} onChange={set('disposition')} />
        </Field>
        {errors.form && <p className="form-error">{errors.form}</p>}
        <ModalActions onClose={onClose} busy={add.isPending} submitLabel="RECORD INFRACTION" />
      </form>
    </Modal>
  )
}
