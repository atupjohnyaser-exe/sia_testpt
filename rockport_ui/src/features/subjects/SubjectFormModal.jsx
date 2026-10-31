import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { NumberInput } from '../../components/NumberInput'
import { PhotoField } from '../../components/PhotoField'
import { Field, Modal, ModalActions } from '../../components/ui'
import { useToast } from '../../context/toast'
import { useCreateSubject, useDeletePhoto, useUpdateSubject, useUploadPhoto } from '../../hooks/mutations'
import { errorMessage, fieldErrors } from '../../lib/errors'
import { LIMITS, validateSubjectForm } from '../../lib/validation'

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
    // Only a pasted link goes in the box. An uploaded portrait is handled by the photo picker.
    photo_url: subject?.photo_source === 'url' ? subject.photo_url : '',
    officer_advisory: subject?.officer_advisory ?? '',
  }
}

/** Create a record, or edit the profile of an existing one. Status has its own dialog. */
export function SubjectFormModal({ subject, onClose }) {
  const navigate = useNavigate()
  const toast = useToast()
  const create = useCreateSubject()
  const update = useUpdateSubject(subject?.id)
  const upload = useUploadPhoto()
  const deletePhoto = useDeletePhoto()
  const [form, setForm] = useState(() => startingValues(subject))
  const [photo, setPhoto] = useState(null)
  const [removed, setRemoved] = useState(false)
  const [photoError, setPhotoError] = useState('')
  const [errors, setErrors] = useState({})
  const busy = create.isPending || update.isPending || upload.isPending || deletePhoto.isPending

  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }))

  const submit = async (event) => {
    event.preventDefault()

    const problems = validateSubjectForm(form)

    if (Object.keys(problems).length > 0) {
      setErrors(problems)

      return
    }

    setErrors({})

    // A chosen file or a removal replaces any outside link. Otherwise the link box decides.
    const body = {
      ...form,
      age: form.age === '' ? null : Number(form.age),
      photo_url: photo || removed ? null : form.photo_url.trim() || null,
    }

    if (subject) delete body.status

    try {
      const saved = subject ? await update.mutateAsync(body) : await create.mutateAsync(body)
      const id = saved.data.id

      // The record is saved by now. If only the photo fails, say so instead of losing the edit.
      try {
        if (photo) await upload.mutateAsync({ id, file: photo.file })
        else if (removed && subject?.photo_source === 'upload') await deletePhoto.mutateAsync(id)
      } catch (error) {
        toast(`RECORD SAVED, BUT THE PHOTO FAILED: ${errorMessage(error, 'TRY AGAIN FROM EDIT').toUpperCase()}`)
      }

      if (!subject) navigate(`/database/${id}`)
      onClose()
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  const text = (key, title, max, extra = {}) => (
    <Field label={title} error={errors[key]}>
      <input value={form[key]} onChange={set(key)} maxLength={max} {...extra} />
    </Field>
  )

  return (
    <Modal title={subject ? 'EDIT RECORD' : 'CREATE RECORD'} eyebrow="DATABASE ENTRY" onClose={onClose} wide>
      <form className="record-form" onSubmit={submit} noValidate>
        <div className="form-grid">
          {text('name', 'FULL NAME', LIMITS.name, { required: true, autoComplete: 'off' })}
          {text('alias', 'KNOWN ALIAS', LIMITS.alias, { autoComplete: 'off' })}
          <Field label="AGE" error={errors.age}>
            <NumberInput value={form.age} onChange={(value) => setForm((current) => ({ ...current, age: value }))} min={1} max={120} ariaLabel="Age" />
          </Field>
          {!subject && (
            <Field label="STARTING STATUS" error={errors.status}>
              <select value={form.status} onChange={set('status')}>
                {STATUSES.map((status) => <option key={status} value={status}>{status.replace('_', ' ')}</option>)}
              </select>
            </Field>
          )}
          {text('last_known_location', 'LAST KNOWN LOCATION', LIMITS.location)}
          {text('height', 'HEIGHT (E.G. 5\'8" OR 173 CM)', LIMITS.height)}
          {text('eye_color', 'EYE COLOR', LIMITS.color)}
          {text('hair_color', 'HAIR COLOR', LIMITS.color)}
        </div>
        {text('distinguishing_features', 'DISTINGUISHING FEATURES', LIMITS.features)}

        <PhotoField
          currentUrl={subject?.photo_url ?? null}
          photo={photo}
          removed={removed}
          onPick={setPhoto}
          onRemove={setRemoved}
          error={photoError}
          onError={setPhotoError}
        />
        {text('photo_url', 'OR PASTE AN IMAGE LINK (HTTP OR HTTPS)', LIMITS.url, { type: 'url', placeholder: 'https://', disabled: Boolean(photo) || removed })}

        <Field label="OFFICER ADVISORY" error={errors.officer_advisory}>
          <textarea rows="3" value={form.officer_advisory} onChange={set('officer_advisory')} maxLength={LIMITS.advisory} />
        </Field>
        <p className="form-hint">Bounty, threat level and cost to state are worked out from infractions and manhunts. They cannot be typed in.</p>
        {errors.form && <p className="form-error">{errors.form}</p>}
        <ModalActions onClose={onClose} busy={busy} submitLabel={subject ? 'SAVE CHANGES' : 'CREATE RECORD'} />
      </form>
    </Modal>
  )
}
