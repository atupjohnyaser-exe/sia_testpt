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

export function errorMessage(error, fallback = 'Something went wrong. Try again.') {
  return error?.response?.data?.message || fallback
}
