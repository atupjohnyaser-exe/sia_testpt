import { useState } from 'react'
import { NumberInput } from '../../components/NumberInput'
import { Field, Modal, ModalActions } from '../../components/ui'
import { useCostRates, useInfractionTypes } from '../../hooks/queries'
import { useLogManhunt } from '../../hooks/mutations'
import { DISTRICTS } from '../../lib/districts'
import { fieldErrors } from '../../lib/errors'
import { localInputValue, num } from '../../lib/format'
import { simulateManhunt } from '../../lib/simulate'
import { COUNT_LIMITS, LIMITS, MAX_DURATION_SECONDS, validateManhuntForm } from '../../lib/validation'

const COUNTS = [
  ['officers_involved', 'OFFICERS DEPLOYED'],
  ['officers_injured', 'OFFICERS INJURED'],
  ['units_stood_down', 'UNITS STOOD DOWN'],
  ['checkpoints', 'CHECKPOINTS'],
  ['k9_units', 'K-9 UNITS'],
  ['helicopters', 'HELICOPTERS'],
  ['property_damage_incidents', 'PROPERTY DAMAGE INCIDENTS'],
  ['insurance_claims', 'INSURANCE CLAIMS'],
]

const emptyCounts = () => Object.fromEntries(COUNTS.map(([key]) => [key, 0]))

export function LogManhuntModal({ subject, onClose, onLogged }) {
  const log = useLogManhunt(subject.id)
  const rates = useCostRates()
  const types = useInfractionTypes()
  const [now] = useState(() => localInputValue())
  const [form, setForm] = useState(() => ({ started_at: localInputValue(new Date(Date.now() - 3600000)), minutes: 20, seconds: 0, outcome: 'EVADED', district: '', notes: '' }))
  const [counts, setCounts] = useState(emptyCounts)
  const [infractionIds, setInfractionIds] = useState([])
  const [errors, setErrors] = useState({})

  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }))
  const setNumber = (key) => (value) => setForm((current) => ({ ...current, [key]: value }))
  const setCount = (key) => (value) => setCounts((current) => ({ ...current, [key]: value }))
  const toggleType = (id) => setInfractionIds((current) => (current.includes(id) ? current.filter((item) => item !== id) : [...current, id]))

  // A preview only. The server works out the real numbers when you save.
  const multiplier = rates.data?.meta?.bounty_multiplier ?? 0.4
  const estimate = (rates.data?.data ?? []).reduce((sum, rate) => sum + (Number(counts[rate.code]) || 0) * rate.unit_cost, 0)

  const fillDemoNumbers = () => {
    const made = simulateManhunt(DISTRICTS, types.data?.data ?? [])

    setForm((current) => ({ ...current, minutes: made.minutes, seconds: made.seconds, outcome: made.outcome, district: made.district }))
    setCounts(made.counts)
    setInfractionIds(made.infractionIds)
    setErrors({})
  }

  const submit = async (event) => {
    event.preventDefault()

    const problems = validateManhuntForm(form, counts)

    if (Object.keys(problems).length > 0) {
      setErrors(problems)

      return
    }

    setErrors({})

    const body = {
      ...Object.fromEntries(Object.entries(counts).map(([key, value]) => [key, Number(value) || 0])),
      started_at: new Date(form.started_at).toISOString(),
      duration_seconds: Number(form.minutes) * 60 + Number(form.seconds),
      outcome: form.outcome,
      district: form.district,
      notes: form.notes,
      infractions: infractionIds.map((id) => ({ infraction_type_id: id })),
    }

    try {
      const result = await log.mutateAsync(body)
      onLogged?.(result.data)
      onClose()
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  return (
    <Modal title="LOG MANHUNT" eyebrow={`${subject.case_id} // ${subject.name.toUpperCase()}`} onClose={onClose} wide>
      <form className="record-form" onSubmit={submit} noValidate>
        <div className="form-actions-top">
          <button type="button" className="button button-outline" onClick={fillDemoNumbers}>GENERATE DEMO NUMBERS ⟳</button>
          <small>Fills the form with made-up numbers for training. You can still edit them.</small>
        </div>
        <div className="form-grid">
          <Field label="STARTED" error={errors.started_at}>
            <input type="datetime-local" value={form.started_at} min="1970-01-01T00:00" max={now} onChange={set('started_at')} />
          </Field>
          <Field label="DISTRICT" error={errors.district}>
            <input list="manhunt-districts" value={form.district} onChange={set('district')} maxLength={LIMITS.district} />
            <datalist id="manhunt-districts">{DISTRICTS.map((district) => <option key={district} value={district} />)}</datalist>
          </Field>
          <Field label="LENGTH (MINUTES)" error={errors.duration_seconds}>
            <NumberInput value={form.minutes} onChange={setNumber('minutes')} min={0} max={Math.floor(MAX_DURATION_SECONDS / 60)} ariaLabel="Minutes" />
          </Field>
          <Field label="LENGTH (SECONDS)">
            <NumberInput value={form.seconds} onChange={setNumber('seconds')} min={0} max={59} ariaLabel="Seconds" />
          </Field>
          <Field label="OUTCOME" error={errors.outcome}>
            <select value={form.outcome} onChange={set('outcome')}>
              <option value="EVADED">EVADED (SUBJECT STAYS WANTED)</option>
              <option value="APPREHENDED">APPREHENDED (SUBJECT IN CUSTODY)</option>
            </select>
          </Field>
        </div>

        <small className="eyebrow">RESOURCES USED</small>
        <div className="count-grid">
          {COUNTS.map(([key, title]) => (
            <Field label={title} key={key} error={errors[key]}>
              <NumberInput value={counts[key]} onChange={setCount(key)} min={0} max={COUNT_LIMITS[key]} ariaLabel={title} />
            </Field>
          ))}
        </div>

        <div className="estimate">
          <span>ESTIMATED COST TO STATE <b>{num(estimate)}</b></span>
          <span>ESTIMATED BOUNTY <b>{num(Math.round(estimate * multiplier))}</b></span>
        </div>

        <small className="eyebrow">INFRACTIONS RECORDED DURING THE MANHUNT (OPTIONAL, UP TO 20)</small>
        <div className="checkbox-grid">
          {types.data?.data?.map((type) => (
            <label key={type.id} className={infractionIds.includes(type.id) ? 'checked' : ''}>
              <input type="checkbox" checked={infractionIds.includes(type.id)} onChange={() => toggleType(type.id)} />
              {type.name}
            </label>
          ))}
        </div>

        <Field label="NOTES (OPTIONAL)" error={errors.notes}>
          <textarea rows="2" value={form.notes} onChange={set('notes')} maxLength={LIMITS.manhuntNotes} />
        </Field>
        {errors.form && <p className="form-error">{errors.form}</p>}
        <ModalActions onClose={onClose} busy={log.isPending} submitLabel="LOG MANHUNT" />
      </form>
    </Modal>
  )
}
