import { useState } from 'react'
import { Field, Modal, ModalActions } from '../../components/ui'
import { useAddSighting } from '../../hooks/mutations'
import { fieldErrors } from '../../lib/errors'
import { DISTRICTS } from '../../lib/districts'
import { localInputValue } from '../../lib/format'


export function SightingModal({ subject, onClose }) {
  const add = useAddSighting(subject.id)
  const [form, setForm] = useState(() => ({ district: '', address: '', seen_at: localInputValue(), source: 'patrol', notes: '' }))
  const [errors, setErrors] = useState({})

  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }))

  const submit = async (event) => {
    event.preventDefault()
    setErrors({})

    try {
      // Sent as a UTC timestamp so the server never reads your local time as its own.
      await add.mutateAsync({ ...form, seen_at: new Date(form.seen_at).toISOString() })
      onClose()
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  return (
    <Modal title="LOG SIGHTING" eyebrow={`${subject.case_id} // ${subject.name.toUpperCase()}`} onClose={onClose}>
      <form className="record-form" onSubmit={submit}>
        <div className="form-grid">
          <Field label="DISTRICT" error={errors.district}>
            <input list="district-options" value={form.district} onChange={set('district')} required />
            <datalist id="district-options">{DISTRICTS.map((district) => <option key={district} value={district} />)}</datalist>
          </Field>
          <Field label="ADDRESS (OPTIONAL)" error={errors.address}>
            <input value={form.address} onChange={set('address')} />
          </Field>
          <Field label="SEEN AT" error={errors.seen_at}>
            <input type="datetime-local" value={form.seen_at} onChange={set('seen_at')} required />
          </Field>
          <Field label="SOURCE" error={errors.source}>
            <select value={form.source} onChange={set('source')}>
              {['patrol', 'tip', 'camera', 'informant', 'other'].map((source) => <option key={source} value={source}>{source.toUpperCase()}</option>)}
            </select>
          </Field>
        </div>
        <Field label="NOTES (OPTIONAL)" error={errors.notes}>
          <textarea rows="2" value={form.notes} onChange={set('notes')} />
        </Field>
        {errors.form && <p className="form-error">{errors.form}</p>}
        <ModalActions onClose={onClose} busy={add.isPending} submitLabel="LOG SIGHTING" />
      </form>
    </Modal>
  )
}
