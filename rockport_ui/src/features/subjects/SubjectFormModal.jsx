import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Field, Modal, ModalActions } from '../../components/ui'
import { useCreateSubject, useUpdateSubject } from '../../hooks/mutations'
import { fieldErrors } from '../../lib/errors'

const STATUSES = ['WANTED', 'IN_CUSTODY', 'PAROLED', 'CLEARED']

function startingValues(subject) {
  return {
    name: subject?.name ?? '',
    alias: subject?.alias ?? '',
    age: subject?.age ?? '',
    status: 'WANTED',
    last_known_location: subject?.last_known_location ?? '',
    height: subject?.height ?? '',
    eye_color: subject?.eye_color ?? '',
    hair_color: subject?.hair_color ?? '',
    distinguishing_features: subject?.distinguishing_features ?? '',
    photo_url: subject?.photo_url ?? '',
    officer_advisory: subject?.officer_advisory ?? '',
  }
}

/** Create a record, or edit the profile of an existing one. Status has its own dialog. */
export function SubjectFormModal({ subject, onClose }) {
  const navigate = useNavigate()
  const create = useCreateSubject()
  const update = useUpdateSubject(subject?.id)
  const [form, setForm] = useState(() => startingValues(subject))
  const [errors, setErrors] = useState({})
  const busy = create.isPending || update.isPending

  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }))

  const submit = async (event) => {
    event.preventDefault()
    setErrors({})

    const body = { ...form, age: form.age === '' ? null : Number(form.age) }

    try {
      if (subject) {
        delete body.status
        await update.mutateAsync(body)
      } else {
        const result = await create.mutateAsync(body)
        navigate(`/database/${result.data.id}`)
      }

      onClose()
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  const text = (key, title, extra = {}) => (
    <Field label={title} error={errors[key]}>
      <input value={form[key]} onChange={set(key)} {...extra} />
    </Field>
  )

  return (
    <Modal title={subject ? 'EDIT RECORD' : 'CREATE RECORD'} eyebrow="DATABASE ENTRY" onClose={onClose} wide>
      <form className="record-form" onSubmit={submit}>
        <div className="form-grid">
          {text('name', 'FULL NAME', { required: true })}
          {text('alias', 'KNOWN ALIAS')}
          {text('age', 'AGE', { type: 'number', min: 0, max: 120 })}
          {!subject && (
            <Field label="STARTING STATUS" error={errors.status}>
              <select value={form.status} onChange={set('status')}>
                {STATUSES.map((status) => <option key={status} value={status}>{status.replace('_', ' ')}</option>)}
              </select>
            </Field>
          )}
          {text('last_known_location', 'LAST KNOWN LOCATION')}
          {text('height', 'HEIGHT')}
          {text('eye_color', 'EYE COLOR')}
          {text('hair_color', 'HAIR COLOR')}
        </div>
        {text('distinguishing_features', 'DISTINGUISHING FEATURES')}
        {text('photo_url', 'PHOTO URL (HTTP OR HTTPS)', { type: 'url', placeholder: 'https://' })}
        <Field label="OFFICER ADVISORY" error={errors.officer_advisory}>
          <textarea rows="3" value={form.officer_advisory} onChange={set('officer_advisory')} />
        </Field>
        <p className="form-hint">Bounty, threat level and cost to state are worked out from infractions and manhunts. They cannot be typed in.</p>
        {errors.form && <p className="form-error">{errors.form}</p>}
        <ModalActions onClose={onClose} busy={busy} submitLabel={subject ? 'SAVE CHANGES' : 'CREATE RECORD'} />
      </form>
    </Modal>
  )
}
