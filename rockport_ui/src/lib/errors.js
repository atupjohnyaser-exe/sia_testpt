/** One message per field from a Laravel 422 response, plus a general one under `form`. */
export function fieldErrors(error) {
  const data = error?.response?.data
  const result = {}

  if (data?.errors) {
    for (const [key, messages] of Object.entries(data.errors)) {
      result[key] = messages[0]
    }
  }

  result.form = data?.message || 'Could not reach the server. Try again.'

  return result
}

/** True when the server refused because the officer's clearance is too low. */
export function isClearanceError(error) {
  return error?.response?.status === 403 && error.response.data?.code === 'clearance_required'
}

/** A message for a toast. Empty for clearance refusals, because the warning banner already shows those. */
export function errorMessage(error, fallback = 'Something went wrong. Try again.') {
  if (isClearanceError(error)) return ''

  return error?.response?.data?.message || fallback
}
