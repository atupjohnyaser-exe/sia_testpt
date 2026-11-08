import { useState } from 'react'
import { PlacePicker } from '../../components/PlacePicker'
import { Field, Modal, ModalActions } from '../../components/ui'
import { useAddSighting } from '../../hooks/mutations'
import { fieldErrors } from '../../lib/errors'
import { localInputValue } from '../../lib/format'
import { composeAddress, splitLocation } from '../../lib/location'
import { LIMITS, validateSightingForm } from '../../lib/validation'

export function SightingModal({ subject, onClose }) {
  const add = useAddSighting(subject.id)
  const [form, setForm] = useState(() => ({ street: '', seen_at: localInputValue(), source: 'patrol', notes: '' }))
  const [place, setPlace] = useState(() => splitLocation(subject.last_known_location))
  const [now] = useState(() => localInputValue())
  const [errors, setErrors] = useState({})

  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }))

  const submit = async (event) => {
    event.preventDefault()

    // The municipality is stored as the district. The barangay and street make up the address.
    const values = { ...form, district: place.municipality, address: composeAddress(form.street, place.barangay) }
    const problems = validateSightingForm(values)

    if (Object.keys(problems).length > 0) {
      setErrors({ ...problems, municipality: problems.district })

      return
    }

    setErrors({})

    try {
      // Sent as a UTC timestamp so the server never reads your local time as its own.
      await add.mutateAsync({
        district: values.district,
        address: values.address,
        seen_at: new Date(form.seen_at).toISOString(),
        source: form.source,
        notes: form.notes,
      })
      onClose()
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  return (
    <Modal title="LOG SIGHTING" eyebrow={`${subject.case_id} // ${subject.name.toUpperCase()}`} onClose={onClose}>
      <form className="record-form" onSubmit={submit} noValidate>
        <div className="form-grid">
          <PlacePicker value={place} onChange={setPlace} errors={errors} municipalityLabel="MUNICIPALITY" />
          <Field label="STREET OR LANDMARK (OPTIONAL)" error={errors.address}>
            <input value={form.street} onChange={set('street')} maxLength={LIMITS.street} />
          </Field>
          <Field label="SOURCE" error={errors.source}>
            <select value={form.source} onChange={set('source')}>
              {['patrol', 'tip', 'camera', 'informant', 'other'].map((source) => <option key={source} value={source}>{source.toUpperCase()}</option>)}
            </select>
          </Field>
          <Field label="SEEN AT" error={errors.seen_at}>
            <input type="datetime-local" value={form.seen_at} min="1970-01-01T00:00" max={now} onChange={set('seen_at')} />
          </Field>
        </div>
        <Field label="NOTES (OPTIONAL)" error={errors.notes}>
          <textarea rows="2" value={form.notes} onChange={set('notes')} maxLength={LIMITS.sightingNotes} />
        </Field>
        {errors.form && <p className="form-error">{errors.form}</p>}
        <ModalActions onClose={onClose} busy={add.isPending} submitLabel="LOG SIGHTING" />
      </form>
    </Modal>
  )
}
