import { useState } from 'react'
import { PlacePicker } from '../../components/PlacePicker'
import { Field, Modal, ModalActions } from '../../components/ui'
import { useFileReport, useUpdateReport } from '../../hooks/mutations'
import { useInfractionTypes } from '../../hooks/queries'
import { fieldErrors } from '../../lib/errors'
import { localInputValue } from '../../lib/format'
import { LIMITS, validateReportForm } from '../../lib/validation'

function startingValues(report) {
  return {
    infraction_type_id: report?.type ? String(report.type.id) : '',
    occurred_at: report ? localInputValue(new Date(report.occurred_at)) : localInputValue(new Date(Date.now() - 3600000)),
    street: report?.street ?? '',
    person_name: report?.person_name ?? '',
    person_description: report?.person_description ?? '',
    narrative: report?.narrative ?? '',
  }
}

/** File a field report, or edit one. Patrol officers can do this without seeing any record of a person. */
export function ReportFormModal({ report, onClose }) {
  const types = useInfractionTypes()
  const file = useFileReport()
  const update = useUpdateReport()
  const [now] = useState(() => localInputValue())
  const [form, setForm] = useState(() => startingValues(report))
  const [place, setPlace] = useState({ municipality: report?.municipality ?? '', barangay: report?.barangay ?? '' })
  const [errors, setErrors] = useState({})

  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }))

  const submit = async (event) => {
    event.preventDefault()

    const values = { ...form, municipality: place.municipality, barangay: place.barangay }
    const problems = validateReportForm(values)

    if (Object.keys(problems).length > 0) {
      setErrors(problems)

      return
    }

    setErrors({})

    const body = {
      infraction_type_id: form.infraction_type_id ? Number(form.infraction_type_id) : null,
      // Sent as a UTC timestamp so the server never reads your local time as its own.
      occurred_at: new Date(form.occurred_at).toISOString(),
      municipality: place.municipality,
      barangay: place.barangay || null,
      street: form.street.trim() || null,
      person_name: form.person_name.trim() || null,
      person_description: form.person_description.trim() || null,
      narrative: form.narrative.trim(),
    }

    try {
      if (report) await update.mutateAsync({ id: report.id, body })
      else await file.mutateAsync(body)

      onClose()
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  return (
    <Modal title={report ? 'EDIT REPORT' : 'FILE A REPORT'} eyebrow={report ? report.reference : 'FIELD REPORT'} onClose={onClose} wide>
      <form className="record-form" onSubmit={submit} noValidate>
        <small className="eyebrow">WHAT AND WHEN</small>
        <div className="form-grid">
          <Field label="TYPE OF INCIDENT (OPTIONAL)" error={errors.infraction_type_id}>
            <select value={form.infraction_type_id} onChange={set('infraction_type_id')}>
              <option value="">NOT SURE</option>
              {types.data?.data?.map((type) => <option key={type.id} value={type.id}>{type.name} ({type.severity})</option>)}
            </select>
          </Field>
          <Field label="DATE AND TIME" error={errors.occurred_at}>
            <input type="datetime-local" value={form.occurred_at} min="1970-01-01T00:00" max={now} onChange={set('occurred_at')} />
          </Field>
        </div>

        <small className="eyebrow">WHERE</small>
        <div className="form-grid">
          <PlacePicker value={place} onChange={setPlace} errors={errors} />
          <Field label="STREET OR LANDMARK (OPTIONAL)" error={errors.street}>
            <input value={form.street} onChange={set('street')} maxLength={LIMITS.street} />
          </Field>
        </div>

        <small className="eyebrow">PERSON INVOLVED (OPTIONAL)</small>
        <div className="form-grid">
          <Field label="NAME OR ALIAS" error={errors.person_name}>
            <input value={form.person_name} onChange={set('person_name')} maxLength={LIMITS.name} placeholder="LEAVE EMPTY IF UNKNOWN" />
          </Field>
          <Field label="DESCRIPTION" error={errors.person_description}>
            <input value={form.person_description} onChange={set('person_description')} maxLength={LIMITS.personDescription} placeholder="CLOTHING, BUILD, VEHICLE" />
          </Field>
        </div>

        <Field label={`WHAT HAPPENED (${form.narrative.length}/${LIMITS.narrative})`} error={errors.narrative}>
          <textarea rows="5" value={form.narrative} onChange={set('narrative')} maxLength={LIMITS.narrative} />
        </Field>
        {errors.form && <p className="form-error">{errors.form}</p>}
        <ModalActions onClose={onClose} busy={file.isPending || update.isPending} submitLabel={report ? 'SAVE REPORT' : 'FILE REPORT'} />
      </form>
    </Modal>
  )
}
