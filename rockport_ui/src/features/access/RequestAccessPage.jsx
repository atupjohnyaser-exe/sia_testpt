import { useState } from 'react'
import { Link } from 'react-router-dom'
import { endpoints } from '../../api/endpoints'
import { NumberInput } from '../../components/NumberInput'
import { PlacePicker } from '../../components/PlacePicker'
import { PublicShell } from '../../components/PublicShell'
import { Field, LoadingRows } from '../../components/ui'
import { useMunicipalities, useAccessOptions } from '../../hooks/queries'
import { LEVELS } from '../../lib/clearance'
import { fieldErrors } from '../../lib/errors'
import { LIMITS, normalizePhone, validateApplicationForm } from '../../lib/validation'

const empty = {
  full_name: '', rank: '', date_of_birth: '', phone: '', email: '', station: '', address_street: '',
  years_of_service: 0, justification: '', declaration: false, website: '',
}

export function RequestAccessPage() {
  const options = useAccessOptions()
  const municipalities = useMunicipalities()
  const [form, setForm] = useState(empty)
  const [place, setPlace] = useState({ municipality: '', barangay: '' })
  const [errors, setErrors] = useState({})
  const [busy, setBusy] = useState(false)
  const [done, setDone] = useState(null)

  const ranks = options.data?.data?.ranks ?? []
  const rank = ranks.find((item) => item.name === form.rank)
  const towns = municipalities.data?.data ?? []

  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }))

  const submit = async (event) => {
    event.preventDefault()

    const values = { ...form, address_municipality: place.municipality, address_barangay: place.barangay }
    const problems = validateApplicationForm(values, { minAge: options.data?.data?.min_age, maxAge: options.data?.data?.max_age })

    if (Object.keys(problems).length > 0) {
      setErrors({ ...problems, municipality: problems.address_municipality, barangay: problems.address_barangay })

      return
    }

    setErrors({})
    setBusy(true)

    try {
      const result = await endpoints.submitAccess({
        ...values,
        phone: normalizePhone(values.phone),
        email: values.email.trim() || null,
        years_of_service: Number(values.years_of_service),
      })

      setDone(result.data)
    } catch (error) {
      const server = fieldErrors(error)

      setErrors({ ...server, municipality: server.address_municipality, barangay: server.address_barangay })
    } finally {
      setBusy(false)
    }
  }

  if (done) {
    return (
      <PublicShell eyebrow="APPLICATION RECEIVED" title="NOW THE CHIEF ADMIN REVIEWS IT">
        <div className="submitted-card">
          <small>YOUR REFERENCE NUMBER</small>
          <strong>{done.reference}</strong>
          <p>Keep this number. You need it to check your application.</p>
          <ol className="steps">
            <li>The Chief Admin reviews your details and your rank.</li>
            <li>If approved, an account is created with a random badge ID.</li>
            <li>The Chief Admin gives you your password in person. Nobody else can issue it.</li>
          </ol>
          <div className="submitted-actions">
            <Link className="button" to={`/access-status?ref=${done.reference}`}>CHECK STATUS</Link>
            <Link className="button button-outline" to="/login">BACK TO SIGN IN</Link>
          </div>
        </div>
      </PublicShell>
    )
  }

  return (
    <PublicShell
      eyebrow="REQUEST ACCESS"
      title="APPLY FOR RECORDS ACCESS"
      intro="Fill in your details. The Chief Admin reviews every application. You do not choose your badge ID or your password."
    >
      <div className="public-grid">
        <form className="record-form public-form" onSubmit={submit} noValidate>
          {options.isLoading && <LoadingRows count={3} />}

          <small className="eyebrow">PERSONAL INFORMATION</small>
          <div className="form-grid">
            <Field label="FULL NAME" error={errors.full_name}>
              <input value={form.full_name} onChange={set('full_name')} maxLength={LIMITS.name} autoComplete="name" placeholder="FIRST AND LAST NAME" />
            </Field>
            <Field label="DATE OF BIRTH" error={errors.date_of_birth}>
              <input type="date" value={form.date_of_birth} onChange={set('date_of_birth')} min="1950-01-01" />
            </Field>
            <Field label="MOBILE NUMBER" error={errors.phone}>
              <input value={form.phone} onChange={set('phone')} maxLength={16} inputMode="tel" autoComplete="tel" placeholder="0917 123 4567" />
            </Field>
            <Field label="EMAIL (OPTIONAL)" error={errors.email}>
              <input type="email" value={form.email} onChange={set('email')} maxLength={LIMITS.email} autoComplete="email" />
            </Field>
            <PlacePicker value={place} onChange={setPlace} errors={errors} municipalityLabel="HOME MUNICIPALITY" barangayLabel="HOME BARANGAY (OPTIONAL)" />
            <Field label="STREET OR PUROK (OPTIONAL)" error={errors.address_street}>
              <input value={form.address_street} onChange={set('address_street')} maxLength={LIMITS.street} />
            </Field>
          </div>

          <small className="eyebrow">SERVICE</small>
          <div className="form-grid">
            <Field label="RANK" error={errors.rank}>
              <select value={form.rank} onChange={set('rank')}>
                <option value="">{options.isLoading ? 'LOADING…' : 'SELECT YOUR RANK'}</option>
                {ranks.map((item) => <option key={item.name} value={item.name}>{item.name.toUpperCase()}</option>)}
              </select>
            </Field>
            <Field label="STATION (MUNICIPALITY)" error={errors.station}>
              <select value={form.station} onChange={set('station')}>
                <option value="">SELECT YOUR STATION</option>
                {towns.map((town) => <option key={town.id} value={town.name}>{town.name}</option>)}
              </select>
            </Field>
            <Field label="YEARS OF SERVICE" error={errors.years_of_service}>
              <NumberInput value={form.years_of_service} onChange={(value) => setForm((current) => ({ ...current, years_of_service: value }))} min={0} max={45} ariaLabel="Years of service" />
            </Field>
          </div>

          {rank && (
            <div className={`level-preview ${rank.auto ? 'auto' : ''}`}>
              <small>{rank.auto ? 'AUTOMATIC LEVEL' : 'HIGHEST LEVEL FOR THIS RANK'}</small>
              <b>LEVEL {rank.level} // {rank.label.toUpperCase()}</b>
              <p>{LEVELS[rank.level].summary}</p>
              <span>
                {rank.auto
                  ? 'Detectives get this level on the application without a decision. The Chief Admin still reviews it before anything is created.'
                  : 'The Chief Admin decides your level, up to this one. A higher rank allows a higher level.'}
              </span>
            </div>
          )}

          <Field label={`WHY DO YOU NEED ACCESS? (${form.justification.length}/${LIMITS.justification})`} error={errors.justification}>
            <textarea rows="3" value={form.justification} onChange={set('justification')} maxLength={LIMITS.justification} />
          </Field>

          {/* Real people never see this. Bots fill it in, and the server then ignores the whole application. */}
          <div className="trap" aria-hidden="true">
            <label>WEBSITE<input tabIndex={-1} autoComplete="off" value={form.website} onChange={set('website')} /></label>
          </div>

          <label className={`declaration ${errors.declaration ? 'has-error' : ''}`}>
            <input type="checkbox" checked={form.declaration} onChange={(event) => setForm((current) => ({ ...current, declaration: event.target.checked }))} />
            I confirm that the information above is true. I understand that false information can be refused or reported.
          </label>
          {errors.declaration && <p className="form-error">{errors.declaration}</p>}
          {errors.form && <p className="form-error">{errors.form}</p>}

          <div className="modal-actions">
            <Link className="button button-outline" to="/login">CANCEL</Link>
            <button className={`button ${busy ? 'is-busy' : ''}`} disabled={busy}>{busy ? 'SENDING…' : 'SUBMIT APPLICATION'}</button>
          </div>
        </form>

        <aside className="public-aside">
          <small className="eyebrow">HOW ACCESS WORKS</small>
          <ol className="steps">
            <li><b>You apply.</b> Your rank sets the highest level you can hold.</li>
            <li><b>The Chief Admin reviews.</b> Every application, including Detectives.</li>
            <li><b>A random badge ID is created.</b> You do not pick it.</li>
            <li><b>The Chief Admin issues your password.</b> Only they can.</li>
          </ol>
          <small className="eyebrow">RANK AND LEVEL</small>
          <table className="ladder">
            <tbody>
              {ranks.map((item) => (
                <tr key={item.name} className={item.name === form.rank ? 'current' : ''}>
                  <td>{item.name}</td>
                  <td>L{item.level}{item.auto ? ' AUTO' : ''}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </aside>
      </div>
    </PublicShell>
  )
}
