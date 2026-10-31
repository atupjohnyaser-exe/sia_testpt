import { useState } from 'react'
import { Field, Modal, ModalActions } from '../../components/ui'
import { useCostRates, useInfractionTypes } from '../../hooks/queries'
import { useLogManhunt } from '../../hooks/mutations'
import { fieldErrors } from '../../lib/errors'
import { localInputValue, num } from '../../lib/format'
import { simulateManhunt } from '../../lib/simulate'
import { DISTRICTS } from '../../lib/districts'

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
  const [form, setForm] = useState(() => ({ started_at: localInputValue(new Date(Date.now() - 3600000)), minutes: 20, seconds: 0, outcome: 'EVADED', district: '', notes: '' }))
  const [counts, setCounts] = useState(emptyCounts)
  const [infractionIds, setInfractionIds] = useState([])
  const [errors, setErrors] = useState({})

  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }))
  const setCount = (key) => (event) => setCounts((current) => ({ ...current, [key]: Math.max(0, Number(event.target.value) || 0) }))
  const toggleType = (id) => setInfractionIds((current) => (current.includes(id) ? current.filter((item) => item !== id) : [...current, id]))

  // A preview only. The server works out the real numbers when you save.
  const multiplier = rates.data?.meta?.bounty_multiplier ?? 0.4
  const estimate = (rates.data?.data ?? []).reduce((sum, rate) => sum + (counts[rate.code] ?? 0) * rate.unit_cost, 0)

  const fillDemoNumbers = () => {
    const made = simulateManhunt(DISTRICTS, types.data?.data ?? [])

    setForm((current) => ({ ...current, minutes: made.minutes, seconds: made.seconds, outcome: made.outcome, district: made.district }))
    setCounts(made.counts)
    setInfractionIds(made.infractionIds)
  }

  const submit = async (event) => {
    event.preventDefault()
    setErrors({})

    const body = {
      ...counts,
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
      <form className="record-form" onSubmit={submit}>
        <div className="form-actions-top">
          <button type="button" className="button button-outline" onClick={fillDemoNumbers}>GENERATE DEMO NUMBERS ⟳</button>
          <small>Fills the form with made-up numbers for training. You can still edit them.</small>
        </div>
        <div className="form-grid">
          <Field label="STARTED" error={errors.started_at}>
            <input type="datetime-local" value={form.started_at} onChange={set('started_at')} required />
          </Field>
          <Field label="DISTRICT" error={errors.district}>
            <input list="manhunt-districts" value={form.district} onChange={set('district')} required />
            <datalist id="manhunt-districts">{DISTRICTS.map((district) => <option key={district} value={district} />)}</datalist>
          </Field>
          <Field label="LENGTH (MINUTES)" error={errors.duration_seconds}>
            <input type="number" min="0" max="2800" value={form.minutes} onChange={set('minutes')} required />
          </Field>
          <Field label="LENGTH (SECONDS)">
            <input type="number" min="0" max="59" value={form.seconds} onChange={set('seconds')} required />
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
              <input type="number" min="0" value={counts[key]} onChange={setCount(key)} />
            </Field>
          ))}
        </div>

        <div className="estimate">
          <span>ESTIMATED COST TO STATE <b>{num(estimate)}</b></span>
          <span>ESTIMATED BOUNTY <b>{num(Math.round(estimate * multiplier))}</b></span>
        </div>

        <small className="eyebrow">INFRACTIONS RECORDED DURING THE MANHUNT (OPTIONAL)</small>
        <div className="checkbox-grid">
          {types.data?.data?.map((type) => (
            <label key={type.id} className={infractionIds.includes(type.id) ? 'checked' : ''}>
              <input type="checkbox" checked={infractionIds.includes(type.id)} onChange={() => toggleType(type.id)} />
              {type.name}
            </label>
          ))}
        </div>

        <Field label="NOTES (OPTIONAL)" error={errors.notes}>
          <textarea rows="2" value={form.notes} onChange={set('notes')} />
        </Field>
        {errors.form && <p className="form-error">{errors.form}</p>}
        <ModalActions onClose={onClose} busy={log.isPending} submitLabel="LOG MANHUNT" />
      </form>
    </Modal>
  )
}
