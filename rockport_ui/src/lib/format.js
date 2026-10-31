const numberFormat = new Intl.NumberFormat('en-US')

const pad = (value) => String(value).padStart(2, '0')

/** 1040715559 becomes 1,040,715,559. Missing values show a dash. */
export function num(value) {
  return value === null || value === undefined ? '—' : numberFormat.format(value)
}

/** Seconds to mm:ss, or h:mm:ss for an hour or more. */
export function duration(seconds) {
  const total = Math.max(0, Number(seconds) || 0)
  const hours = Math.floor(total / 3600)
  const minutes = Math.floor((total % 3600) / 60)

  return hours > 0
    ? `${hours}:${pad(minutes)}:${pad(total % 60)}`
    : `${pad(minutes)}:${pad(total % 60)}`
}

export function dateText(value) {
  if (!value) return '—'

  return new Date(value)
    .toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
    .toUpperCase()
}

export function dateTimeText(value) {
  if (!value) return '—'

  const date = new Date(value)
  const time = date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })

  return `${dateText(value)} ${time}`
}

export function initials(name = '') {
  return name
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

/** IN_CUSTODY becomes IN CUSTODY. */
export function label(value) {
  return String(value ?? '').replaceAll('_', ' ').toUpperCase()
}

/** Value for <input type="datetime-local"> in the browser's own time zone. */
export function localInputValue(date = new Date()) {
  const shifted = new Date(date.getTime() - date.getTimezoneOffset() * 60000)

  return shifted.toISOString().slice(0, 16)
}

/** Date only, for <input type="date"> and its max attribute. */
export function localDateValue(date = new Date()) {
  return localInputValue(date).slice(0, 10)
}
