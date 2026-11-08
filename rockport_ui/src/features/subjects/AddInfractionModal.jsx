import { useState } from 'react'
import { PlacePicker } from '../../components/PlacePicker'
import { Field, Modal, ModalActions } from '../../components/ui'
import { useInfractionTypes } from '../../hooks/queries'
import { useAddInfraction } from '../../hooks/mutations'
import { fieldErrors } from '../../lib/errors'
import { localDateValue, num } from '../../lib/format'
import { composeLocation, splitLocation } from '../../lib/location'
import { LIMITS, validateInfractionForm } from '../../lib/validation'

export function AddInfractionModal({ subject, onClose }) {
  const types = useInfractionTypes()
  const add = useAddInfraction(subject.id)
  const [today] = useState(() => localDateValue())
  const [form, setForm] = useState(() => ({ infraction_type_id: '', occurred_on: localDateValue(), disposition: '' }))
  const [place, setPlace] = useState(() => splitLocation(subject.last_known_location))
  const [errors, setErrors] = useState({})

  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }))
  const chosen = types.data?.data?.find((type) => String(type.id) === String(form.infraction_type_id))

  const submit = async (event) => {
    event.preventDefault()

    const values = { ...form, location: composeLocation(place.municipality, place.barangay) }
    const problems = validateInfractionForm(values)

    if (Object.keys(problems).length > 0) {
      setErrors({ ...problems, municipality: problems.location })

      return
    }

    setErrors({})

    try {
      await add.mutateAsync({ ...values, infraction_type_id: Number(form.infraction_type_id) })
      onClose()
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  return (
    <Modal title="RECORD INFRACTION" eyebrow={`${subject.case_id} // ${subject.name.toUpperCase()}`} onClose={onClose}>
      <form className="record-form" onSubmit={submit} noValidate>
        <div className="form-grid">
          <Field label="INFRACTION TYPE" error={errors.infraction_type_id}>
            <select value={form.infraction_type_id} onChange={set('infraction_type_id')}>
              <option value="">SELECT A TYPE</option>
              {types.data?.data?.map((type) => <option key={type.id} value={type.id}>{type.name} ({type.severity})</option>)}
            </select>
          </Field>
          <Field label="DATE" error={errors.occurred_on}>
            <input type="date" value={form.occurred_on} min="1970-01-01" max={today} onChange={set('occurred_on')} />
          </Field>
        </div>
        {chosen && <p className="form-hint">Adds {num(chosen.bounty_value)} to the bounty and {num(chosen.fine_amount)} in fines until it is served.</p>}
        <div className="form-grid">
          <PlacePicker value={place} onChange={setPlace} errors={errors} municipalityLabel="WHERE (MUNICIPALITY)" />
        </div>
        <Field label="DISPOSITION (OPTIONAL)" error={errors.disposition}>
          <input value={form.disposition} onChange={set('disposition')} maxLength={LIMITS.disposition} />
        </Field>
        {errors.location && !errors.municipality && <p className="form-error">{errors.location}</p>}
        {errors.form && <p className="form-error">{errors.form}</p>}
        <ModalActions onClose={onClose} busy={add.isPending} submitLabel="RECORD INFRACTION" />
      </form>
    </Modal>
  )
}
