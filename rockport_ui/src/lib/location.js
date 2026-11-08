/** "Brgy. Poblacion, Tubigon" from its two parts. Just "Tubigon" when no barangay was chosen. */
export function composeLocation(municipality, barangay) {
  if (!municipality) return ''

  return barangay ? `Brgy. ${barangay}, ${municipality}` : municipality
}

/** The reverse. Works on text the app wrote, and on a plain town name. */
export function splitLocation(text) {
  const parts = String(text ?? '')
    .split(',')
    .map((part) => part.trim())
    .filter(Boolean)

  if (parts.length === 0) return { municipality: '', barangay: '' }

  const municipality = parts[parts.length - 1]
  const barangay = parts.length > 1 && parts[0].startsWith('Brgy. ') ? parts[0].slice(6) : ''

  return { municipality, barangay }
}

/** Street and barangay as one address line: "Purok 3, Brgy. Poblacion". */
export function composeAddress(street, barangay) {
  return [street?.trim(), barangay ? `Brgy. ${barangay}` : null].filter(Boolean).join(', ')
}

/** "Purok 3, Brgy. Poblacion, Tubigon" back into its three parts. */
export function splitAddress(text) {
  const parts = String(text ?? '')
    .split(',')
    .map((part) => part.trim())
    .filter(Boolean)

  if (parts.length === 0) return { street: '', barangay: '', municipality: '' }

  const municipality = parts.pop()
  const barangayIndex = parts.findIndex((part) => part.startsWith('Brgy. '))
  const barangay = barangayIndex >= 0 ? parts.splice(barangayIndex, 1)[0].slice(6) : ''

  return { street: parts.join(', '), barangay, municipality }
}
