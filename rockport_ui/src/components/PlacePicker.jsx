import { useBarangays, useMunicipalities } from '../hooks/queries'
import { LIMITS } from '../lib/validation'
import { Field } from './ui'

/**
 * Two linked fields for a real place in Bohol: the municipality, then a barangay of that municipality.
 * Both come from PSGC (the official Philippine geographic codes). Render it inside a form grid.
 * If the barangays of a town have not been loaded yet, the barangay becomes a plain text box so nobody is blocked.
 *
 *   value     { municipality, barangay }
 *   onChange  called with the new { municipality, barangay }
 */
export function PlacePicker({ value, onChange, errors = {}, municipalityLabel = 'MUNICIPALITY', barangayLabel = 'BARANGAY (OPTIONAL)', municipalityKey = 'municipality', barangayKey = 'barangay' }) {
  const municipalities = useMunicipalities()
  const list = municipalities.data?.data ?? []
  const selected = list.find((place) => place.name === value.municipality)
  const barangays = useBarangays(selected?.id)
  const known = barangays.data?.data ?? []

  const pickMunicipality = (event) => onChange({ municipality: event.target.value, barangay: '' })
  const pickBarangay = (event) => onChange({ ...value, barangay: event.target.value })

  return (
    <>
      <Field label={municipalityLabel} error={errors[municipalityKey]}>
        <select value={value.municipality} onChange={pickMunicipality} disabled={municipalities.isLoading}>
          <option value="">{municipalities.isLoading ? 'LOADING PLACES…' : 'SELECT A MUNICIPALITY'}</option>
          {/* A place saved earlier that is not in the list is kept, so editing never loses it. */}
          {value.municipality && !selected && <option value={value.municipality}>{value.municipality} (SAVED)</option>}
          {list.map((place) => (
            <option key={place.id} value={place.name}>{place.name}{place.type === 'City' ? ' (CITY)' : ''}</option>
          ))}
        </select>
      </Field>

      <Field label={barangayLabel} error={errors[barangayKey]}>
        {known.length > 0 ? (
          <select value={value.barangay} onChange={pickBarangay}>
            <option value="">NO BARANGAY</option>
            {value.barangay && !known.some((place) => place.name === value.barangay) && <option value={value.barangay}>{value.barangay} (SAVED)</option>}
            {known.map((place) => <option key={place.id} value={place.name}>{place.name}</option>)}
          </select>
        ) : (
          <input
            value={value.barangay}
            onChange={pickBarangay}
            maxLength={LIMITS.barangay}
            disabled={!value.municipality}
            placeholder={value.municipality ? 'TYPE THE BARANGAY' : 'CHOOSE A MUNICIPALITY FIRST'}
          />
        )}
      </Field>
    </>
  )
}
