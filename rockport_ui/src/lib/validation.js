import { localDateValue } from './format'

/*
 * These checks mirror the server rules so people get a clear message before anything is sent.
 * The server checks everything again. Never rely on this file for security.
 */

export const LIMITS = {
  name: 120,
  alias: 80,
  location: 120,
  height: 20,
  color: 30,
  features: 500,
  advisory: 2000,
  url: 2048,
  badge: 20,
  key: 128,
  reason: 300,
  note: 5000,
  district: 100,
  address: 150,
  sightingNotes: 1000,
  manhuntNotes: 2000,
  disposition: 120,
  barangay: 100,
  street: 150,
  narrative: 3000,
  personDescription: 500,
  justification: 500,
  phone: 11,
  email: 160,
}

/** The most one manhunt may record for each count. Same numbers as the server. */
export const COUNT_LIMITS = {
  officers_involved: 5000,
  officers_injured: 5000,
  units_stood_down: 5000,
  checkpoints: 500,
  k9_units: 200,
  helicopters: 50,
  property_damage_incidents: 5000,
  insurance_claims: 5000,
}

export const MAX_DURATION_SECONDS = 172800

export const PHOTO = {
  maxBytes: 2 * 1024 * 1024,
  types: ['image/jpeg', 'image/png', 'image/webp'],
  minSide: 100,
  maxSide: 3000,
}

export const PATTERNS = {
  name: /^[\p{L}\p{M}][\p{L}\p{M} .'’-]*$/u,
  alias: /^[\p{L}\p{M}\p{N}][\p{L}\p{M}\p{N} .'’"“”-]*$/u,
  color: /^\p{L}[\p{L} /-]*$/u,
  height: /^[0-9'"’”′″. cmftin-]{1,20}$/iu,
  badge: /^[A-Za-z0-9-]{3,20}$/,
  phone: /^09\d{9}$/,
  email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
  fullName: /^\S+(\s+\S+)+$/u,
}

function hasControlCharacters(text) {
  for (const character of text) {
    const code = character.codePointAt(0)

    // Tab (9), new line (10) and carriage return (13) are fine.
    if ((code < 32 && code !== 9 && code !== 10 && code !== 13) || code === 127) return true
  }

  return false
}

/** What is wrong with a piece of free text, or an empty string if nothing is. */
export function plainTextProblem(text) {
  if (hasControlCharacters(text)) return 'contains characters that are not allowed.'
  if (/[\u200B-\u200F\u202A-\u202E\u2066-\u2069\uFEFF]/.test(text)) return 'contains hidden characters that are not allowed.'
  if (/<\s*\/?\s*[a-z!?]/i.test(text)) return 'may not contain HTML.'

  return ''
}

export function validateText(value, { label, required = false, min = 0, max = 255, pattern = null, message = '' }) {
  const text = String(value ?? '').trim()

  if (!text) return required ? `${label} is required.` : ''
  if (text.length < min) return `${label} must be at least ${min} characters.`
  if (text.length > max) return `${label} must be ${max} characters or fewer.`

  const problem = plainTextProblem(text)
  if (problem) return `${label} ${problem}`

  if (pattern && !pattern.test(text)) return message || `${label} contains characters that are not allowed.`

  return ''
}

export function validateNumber(value, { label, min, max, required = false }) {
  if (value === '' || value === null || value === undefined) return required ? `${label} is required.` : ''

  const number = Number(value)

  if (!Number.isInteger(number)) return `${label} must be a whole number.`
  if (number < min || number > max) return `${label} must be between ${min} and ${max}.`

  return ''
}

export function validateUrl(value) {
  const text = String(value ?? '').trim()

  if (!text) return ''
  if (text.length > LIMITS.url) return `The link must be ${LIMITS.url} characters or fewer.`

  try {
    const url = new URL(text)

    return url.protocol === 'http:' || url.protocol === 'https:' ? '' : 'The link must start with http:// or https://.'
  } catch {
    return 'Enter a full link that starts with http:// or https://.'
  }
}

/** For <input type="date"> values like 2026-10-31. Up to tomorrow, same as the server. */
export function validateDate(value, label) {
  if (!value) return `${label} is required.`
  if (value < '1970-01-01') return `${label} cannot be before 1970.`

  const tomorrow = localDateValue(new Date(Date.now() + 86400000))

  return value > tomorrow ? `${label} cannot be in the future.` : ''
}

/** For <input type="datetime-local"> values. Not in the future, not before 1970. */
export function validateDateTime(value, label) {
  if (!value) return `${label} is required.`

  const time = new Date(value).getTime()

  if (Number.isNaN(time)) return `${label} is not a valid date.`
  if (time < 0) return `${label} cannot be before 1970.`

  return time > Date.now() ? `${label} cannot be in the future.` : ''
}

/** Drops empty messages, so what is left is only the problems. */
export function collect(messages) {
  return Object.fromEntries(Object.entries(messages).filter(([, message]) => message))
}

/** Checks type, size and pixel size of a chosen image. Resolves to a message, or an empty string if fine. */
export function validatePhoto(file) {
  return new Promise((resolve) => {
    if (!PHOTO.types.includes(file.type)) return resolve('Use a JPG, PNG or WebP image.')
    if (file.size > PHOTO.maxBytes) return resolve('The image must be 2 MB or smaller.')

    const url = URL.createObjectURL(file)
    const image = new Image()

    image.onload = () => {
      URL.revokeObjectURL(url)

      const { naturalWidth: width, naturalHeight: height } = image

      if (width < PHOTO.minSide || height < PHOTO.minSide) return resolve(`The image must be at least ${PHOTO.minSide} pixels on each side.`)
      if (width > PHOTO.maxSide || height > PHOTO.maxSide) return resolve(`The image must be ${PHOTO.maxSide} pixels or smaller on each side.`)

      return resolve('')
    }

    image.onerror = () => {
      URL.revokeObjectURL(url)
      resolve('That file could not be read as an image.')
    }

    image.src = url
  })
}

// ---- Form checks -----------------------------------------------------------

export function validateSubjectForm(form) {
  return collect({
    name: validateText(form.name, { label: 'Name', required: true, min: 2, max: LIMITS.name, pattern: PATTERNS.name, message: 'Name can only use letters, spaces, apostrophes, periods and hyphens.' }),
    alias: validateText(form.alias, { label: 'Alias', min: 2, max: LIMITS.alias, pattern: PATTERNS.alias, message: 'Alias can only use letters, numbers, spaces, quotes, apostrophes, periods and hyphens.' }),
    age: validateNumber(form.age, { label: 'Age', min: 1, max: 120 }),
    last_known_location: validateText(form.last_known_location, { label: 'Location', max: LIMITS.location }),
    height: validateText(form.height, { label: 'Height', max: LIMITS.height, pattern: PATTERNS.height, message: 'Use a format like 5\'8" or 173 cm.' }),
    eye_color: validateText(form.eye_color, { label: 'Eye color', max: LIMITS.color, pattern: PATTERNS.color, message: 'Eye color can only use letters.' }),
    hair_color: validateText(form.hair_color, { label: 'Hair color', max: LIMITS.color, pattern: PATTERNS.color, message: 'Hair color can only use letters.' }),
    distinguishing_features: validateText(form.distinguishing_features, { label: 'Distinguishing features', max: LIMITS.features }),
    officer_advisory: validateText(form.officer_advisory, { label: 'Advisory', max: LIMITS.advisory }),
    photo_url: validateUrl(form.photo_url),
  })
}

export function validateReason(value, { min, label = 'Reason' }) {
  return collect({ reason: validateText(value, { label, required: true, min, max: LIMITS.reason }) })
}

export function validateInfractionForm(form) {
  return collect({
    infraction_type_id: form.infraction_type_id ? '' : 'Choose an infraction type.',
    occurred_on: validateDate(form.occurred_on, 'Date'),
    location: validateText(form.location, { label: 'Location', required: true, min: 2, max: LIMITS.location }),
    disposition: validateText(form.disposition, { label: 'Disposition', max: LIMITS.disposition }),
  })
}

export function validateSightingForm(form) {
  return collect({
    district: validateText(form.district, { label: 'District', required: true, min: 2, max: LIMITS.district }),
    address: validateText(form.address, { label: 'Address', max: LIMITS.address }),
    seen_at: validateDateTime(form.seen_at, 'Seen at'),
    notes: validateText(form.notes, { label: 'Notes', max: LIMITS.sightingNotes }),
  })
}

export function validateManhuntForm(form, counts) {
  const seconds = Number(form.minutes) * 60 + Number(form.seconds)
  const problems = {
    started_at: validateDateTime(form.started_at, 'Start time'),
    district: validateText(form.district, { label: 'District', required: true, min: 2, max: LIMITS.district }),
    notes: validateText(form.notes, { label: 'Notes', max: LIMITS.manhuntNotes }),
    duration_seconds: seconds < 1 || seconds > MAX_DURATION_SECONDS ? 'A manhunt must last between 1 second and 48 hours.' : '',
  }

  for (const [key, max] of Object.entries(COUNT_LIMITS)) {
    problems[key] = validateNumber(counts[key], { label: 'This number', min: 0, max })
  }

  return collect(problems)
}

export function validateNote(body) {
  return collect({ body: validateText(body, { label: 'Note', required: true, min: 3, max: LIMITS.note }) })
}

export function validateLogin(badge, key) {
  return collect({
    badge_id: PATTERNS.badge.test(badge.trim()) ? '' : 'A badge ID is 3 to 20 letters, numbers or hyphens.',
    access_key: key.length > LIMITS.key ? `The access key must be ${LIMITS.key} characters or fewer.` : '',
  })
}

// ---- Officers and reports --------------------------------------------------

/** 0917 123 4567, +63 917 123 4567 and 0917-123-4567 all become 09171234567. */
export function normalizePhone(value) {
  const phone = String(value ?? '').replace(/[\s\-()]/g, '')

  if (phone.startsWith('+63')) return `0${phone.slice(3)}`
  if (phone.startsWith('63') && phone.length === 12) return `0${phone.slice(2)}`

  return phone
}

export function ageFrom(isoDate, now = new Date()) {
  const born = new Date(isoDate)

  if (Number.isNaN(born.getTime())) return null

  let age = now.getFullYear() - born.getFullYear()
  const months = now.getMonth() - born.getMonth()

  if (months < 0 || (months === 0 && now.getDate() < born.getDate())) age -= 1

  return age
}

/** At least 12 characters, with letters and numbers. Same rule as the server. */
export function passwordProblem(password) {
  if (!password) return ''
  if (password.length < 12) return 'Use at least 12 characters.'

  return /[A-Za-z]/.test(password) && /\d/.test(password) ? '' : 'Use letters and numbers.'
}

const NAME_MESSAGE = 'Use your first and last name. Letters, spaces, apostrophes, periods and hyphens only.'

/** The personal details shared by the access application and the Chief Admin's personnel form. */
function personalProblems(form, { minAge = 21, maxAge = 65 } = {}) {
  const age = ageFrom(form.date_of_birth)

  let birth = form.date_of_birth ? '' : 'Date of birth is required.'
  if (!birth && age === null) birth = 'Date of birth is not valid.'
  if (!birth && (age < minAge || age > maxAge)) birth = `The age must be between ${minAge} and ${maxAge}.`

  const phone = normalizePhone(form.phone)
  let service = validateNumber(form.years_of_service, { label: 'Years of service', min: 0, max: 45, required: true })
  if (!service && age !== null && Number(form.years_of_service) > Math.max(0, age - 18)) {
    service = 'Years of service cannot be more than your age minus 18.'
  }

  return {
    full_name:
      validateText(form.full_name, { label: 'Full name', required: true, min: 5, max: LIMITS.name, pattern: PATTERNS.name, message: NAME_MESSAGE })
      || (PATTERNS.fullName.test(form.full_name.trim()) ? '' : NAME_MESSAGE),
    rank: form.rank ? '' : 'Choose a rank.',
    date_of_birth: birth,
    phone: PATTERNS.phone.test(phone) ? '' : 'Use a Philippine mobile number like 0917 123 4567.',
    email: !form.email.trim() ? '' : PATTERNS.email.test(form.email.trim()) && form.email.length <= LIMITS.email ? '' : 'Enter a valid email address.',
    station: form.station ? '' : 'Choose the municipality of your station.',
    address_municipality: form.address_municipality ? '' : 'Choose a municipality.',
    address_barangay: validateText(form.address_barangay, { label: 'Barangay', max: LIMITS.barangay }),
    address_street: validateText(form.address_street, { label: 'Street', max: LIMITS.street }),
    years_of_service: service,
  }
}

export function validateApplicationForm(form, limits) {
  return collect({
    ...personalProblems(form, limits),
    justification: validateText(form.justification, { label: 'Reason', required: true, min: 10, max: LIMITS.justification }),
    declaration: form.declaration ? '' : 'Confirm that the information is true.',
  })
}

export function validatePersonnelForm(form, { maxLevel }) {
  const level = Number(form.clearance_level)

  return collect({
    ...personalProblems(form),
    clearance_level: level >= 1 && level <= maxLevel ? '' : `This rank can hold level ${maxLevel} at most.`,
    password: passwordProblem(form.password),
  })
}

export function validateReportForm(form) {
  return collect({
    occurred_at: validateDateTime(form.occurred_at, 'Date and time'),
    municipality: form.municipality ? '' : 'Choose the municipality.',
    barangay: validateText(form.barangay, { label: 'Barangay', max: LIMITS.barangay }),
    street: validateText(form.street, { label: 'Street', max: LIMITS.street }),
    person_name: validateText(form.person_name, { label: 'Name', min: 2, max: LIMITS.name, pattern: PATTERNS.alias, message: 'Use letters, numbers, spaces, quotes, apostrophes, periods and hyphens only.' }),
    person_description: validateText(form.person_description, { label: 'Description', max: LIMITS.personDescription }),
    narrative: validateText(form.narrative, { label: 'What happened', required: true, min: 20, max: LIMITS.narrative }),
  })
}

