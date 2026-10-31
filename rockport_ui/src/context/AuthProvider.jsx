import { useEffect, useState } from 'react'
import { tokenStore } from '../api/client'
import { endpoints } from '../api/endpoints'
import { AuthContext } from './auth'

const OFFICER_KEY = 'rpd.officer'

function saveOfficer(officer) {
  try {
    if (officer) localStorage.setItem(OFFICER_KEY, JSON.stringify(officer))
    else localStorage.removeItem(OFFICER_KEY)
  } catch {
    // Storage can be blocked. Nothing to do.
  }
}

// A stored officer only counts if there is also a token.
function readOfficer() {
  if (!tokenStore.get()) return null

  try {
    return JSON.parse(localStorage.getItem(OFFICER_KEY) || 'null')
  } catch {
    return null
  }
}

export function AuthProvider({ children }) {
  const [officer, setOfficer] = useState(readOfficer)
  const [ready, setReady] = useState(() => !tokenStore.get())

  // Always listen, even if nobody was signed in when the page loaded.
  // Otherwise a token that dies later in the session never sends you back to login.
  useEffect(() => {
    const clear = () => {
      tokenStore.set(null)
      saveOfficer(null)
      setOfficer(null)
      setReady(true)
    }

    window.addEventListener('rpd:unauthorized', clear)

    return () => window.removeEventListener('rpd:unauthorized', clear)
  }, [])

  // On page load, confirm the saved token still works.
  useEffect(() => {
    if (!tokenStore.get()) return undefined

    let active = true

    endpoints
      .me()
      .then(({ data }) => {
        if (!active) return
        saveOfficer(data)
        setOfficer(data)
      })
      .catch(() => {
        if (!active) return
        tokenStore.set(null)
        saveOfficer(null)
        setOfficer(null)
      })
      .finally(() => {
        if (active) setReady(true)
      })

    return () => {
      active = false
    }
  }, [])

  const login = async (credentials) => {
    const result = await endpoints.login(credentials)

    tokenStore.set(result.token)
    saveOfficer(result.officer)
    setOfficer(result.officer)
    setReady(true)

    return result.officer
  }

  const logout = async () => {
    try {
      await endpoints.logout()
    } catch {
      // The token may already be dead. We sign out locally either way.
    } finally {
      tokenStore.set(null)
      saveOfficer(null)
      setOfficer(null)
    }
  }

  const can = (level) => (officer?.clearance_level ?? 0) >= level

  return <AuthContext.Provider value={{ officer, ready, login, logout, can }}>{children}</AuthContext.Provider>
}
