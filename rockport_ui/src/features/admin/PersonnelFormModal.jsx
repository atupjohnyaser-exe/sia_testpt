import { useState } from 'react'
import { NumberInput } from '../../components/NumberInput'
import { PlacePicker } from '../../components/PlacePicker'
import { Field, Modal, ModalActions } from '../../components/ui'
import { useCreatePersonnel, useUpdatePersonnel } from '../../hooks/mutations'
import { useAccessOptions, useMunicipalities } from '../../hooks/queries'
import { levelName } from '../../lib/clearance'
import { fieldErrors } from '../../lib/errors'
import { splitAddress } from '../../lib/location'
import { LIMITS, normalizePhone, validatePersonnelForm } from '../../lib/validation'

function startingValues(person) {
  return {
    full_name: person?.name ?? '',
    rank: person?.rank ?? '',
    clearance_level: person ? String(person.clearance_level) : '',
    date_of_birth: person?.date_of_birth ?? '',
    phone: person?.phone ?? '',
    email: person?.email ?? '',
    station: person?.station ?? '',
    address_street: splitAddress(person?.address).street,
    years_of_service: person?.years_of_service ?? 0,
    password: '',
  }
}

/** Add a person with their personal information, or edit one. The rank sets the highest level. */
export function PersonnelFormModal({ person, onClose, onCreated }) {
  const options = useAccessOptions()
  const municipalities = useMunicipalities()
  const create = useCreatePersonnel()
  const update = useUpdatePersonnel()
  const [form, setForm] = useState(() => startingValues(person))
  const [place, setPlace] = useState(() => {
    const { municipality, barangay } = splitAddress(person?.address)

    return { municipality, barangay }
  })
  const [errors, setErrors] = useState({})

  const ranks = options.data?.data?.ranks ?? []
  const towns = municipalities.data?.data ?? []
  const rank = ranks.find((item) => item.name === form.rank)
  // The app never hands out level 5, whatever the rank.
  const maxLevel = rank ? Math.min(rank.level, 4) : 0
  const busy = create.isPending || update.isPending

  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }))

  const pickRank = (event) => {
    const next = ranks.find((item) => item.name === event.target.value)
    const cap = next ? Math.min(next.level, 4) : 0

    setForm((current) => ({
      ...current,
      rank: event.target.value,
      // Keep the level inside what the new rank allows. A Detective starts at 3.
      clearance_level: next ? String(Math.min(Number(current.clearance_level) || cap, cap)) : '',
    }))
  }

  const submit = async (event) => {
    event.preventDefault()

    const values = { ...form, address_municipality: place.municipality, address_barangay: place.barangay }
    const problems = validatePersonnelForm(values, { maxLevel })

    if (Object.keys(problems).length > 0) {
      setErrors({ ...problems, municipality: problems.address_municipality, barangay: problems.address_barangay })

      return
    }

    setErrors({})

    const body = {
      full_name: values.full_name.trim(),
      rank: values.rank,
      clearance_level: Number(values.clearance_level),
      date_of_birth: values.date_of_birth,
      phone: normalizePhone(values.phone),
      email: values.email.trim() || null,
      station: values.station,
      address_municipality: values.address_municipality,
      address_barangay: values.address_barangay || null,
      address_street: values.address_street.trim() || null,
      years_of_service: Number(values.years_of_service),
    }

    try {
      if (person) {
        await update.mutateAsync({ id: person.id, body })
      } else {
        const result = await create.mutateAsync({ ...body, password: values.password || undefined })

        onCreated(result.credentials)
      }

      onClose()
    } catch (error) {
      const server = fieldErrors(error)

      setErrors({ ...server, municipality: server.address_municipality, barangay: server.address_barangay })
    }
  }

  return (
    <Modal title={person ? 'EDIT PERSONNEL' : 'ADD PERSONNEL'} eyebrow={person ? person.badge_id : 'A RANDOM BADGE ID IS CREATED'} onClose={onClose} wide>
      <form className="record-form" onSubmit={submit} noValidate>
        <small className="eyebrow">PERSONAL INFORMATION</small>
        <div className="form-grid">
          <Field label="FULL NAME" error={errors.full_name}>
            <input value={form.full_name} onChange={set('full_name')} maxLength={LIMITS.name} autoComplete="off" />
          </Field>
          <Field label="DATE OF BIRTH" error={errors.date_of_birth}>
            <input type="date" value={form.date_of_birth} onChange={set('date_of_birth')} min="1950-01-01" />
          </Field>
          <Field label="MOBILE NUMBER" error={errors.phone}>
            <input value={form.phone} onChange={set('phone')} maxLength={16} placeholder="0917 123 4567" />
          </Field>
          <Field label="EMAIL (OPTIONAL)" error={errors.email}>
            <input type="email" value={form.email} onChange={set('email')} maxLength={LIMITS.email} />
          </Field>
          <PlacePicker value={place} onChange={setPlace} errors={errors} municipalityLabel="HOME MUNICIPALITY" barangayLabel="HOME BARANGAY (OPTIONAL)" />
          <Field label="STREET OR PUROK (OPTIONAL)" error={errors.address_street}>
            <input value={form.address_street} onChange={set('address_street')} maxLength={LIMITS.street} />
          </Field>
        </div>

        <small className="eyebrow">SERVICE AND ACCESS</small>
        <div className="form-grid">
          <Field label="RANK" error={errors.rank}>
            <select value={form.rank} onChange={pickRank}>
              <option value="">SELECT A RANK</option>
              {ranks.map((item) => <option key={item.name} value={item.name}>{item.name.toUpperCase()}</option>)}
            </select>
          </Field>
          <Field label="STATION (MUNICIPALITY)" error={errors.station}>
            <select value={form.station} onChange={set('station')}>
              <option value="">SELECT A STATION</option>
              {towns.map((town) => <option key={town.id} value={town.name}>{town.name}</option>)}
            </select>
          </Field>
          <Field label="YEARS OF SERVICE" error={errors.years_of_service}>
            <NumberInput value={form.years_of_service} onChange={(value) => setForm((current) => ({ ...current, years_of_service: value }))} min={0} max={45} ariaLabel="Years of service" />
          </Field>
          <Field label="CLEARANCE LEVEL" error={errors.clearance_level}>
            <select value={form.clearance_level} onChange={set('clearance_level')} disabled={!rank}>
              <option value="">{rank ? 'CHOOSE A LEVEL' : 'CHOOSE A RANK FIRST'}</option>
              {Array.from({ length: maxLevel }, (_, index) => index + 1).map((value) => (
                <option key={value} value={value}>{value} // {levelName(value).toUpperCase()}</option>
              ))}
            </select>
          </Field>
        </div>
        {rank && <p className="form-hint">{rank.name} can hold level {maxLevel} at most.</p>}

        {!person && (
          <Field label="PASSWORD (OPTIONAL, LEAVE EMPTY TO GENERATE A STRONG ONE)" error={errors.password}>
            <input value={form.password} onChange={set('password')} maxLength={128} autoComplete="off" />
          </Field>
        )}
        {errors.form && <p className="form-error">{errors.form}</p>}
        {errors.user && <p className="form-error">{errors.user}</p>}
        <ModalActions onClose={onClose} busy={busy} submitLabel={person ? 'SAVE CHANGES' : 'CREATE ACCOUNT'} />
      </form>
    </Modal>
  )
}
