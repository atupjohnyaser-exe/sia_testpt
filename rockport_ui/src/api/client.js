import axios from 'axios'

const TOKEN_KEY = 'rpd.sanctum.token'

function readToken() {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

let currentToken = readToken()

export const tokenStore = {
  get: () => currentToken,
  set(value) {
    currentToken = value || null
    try {
      if (currentToken) localStorage.setItem(TOKEN_KEY, currentToken)
      else localStorage.removeItem(TOKEN_KEY)
    } catch {
      // Storage can be blocked. The token still lives in memory for this tab.
    }
  },
}

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: { Accept: 'application/json' },
  timeout: 20000,
})

api.interceptors.request.use((config) => {
  const token = tokenStore.get()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// A 401 while we hold a token means it expired or was revoked.
// AuthProvider listens for this and sends the officer back to the login screen.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && tokenStore.get()) {
      tokenStore.set(null)
      window.dispatchEvent(new Event('rpd:unauthorized'))
    }
    return Promise.reject(error)
  },
)
