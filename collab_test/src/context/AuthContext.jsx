import { createContext, useContext, useEffect, useState } from 'react'
import { endpoints, tokenStore } from '../api/client'

const AuthContext = createContext(null)
const OFFICER_KEY = 'rpd.officer'

export function AuthProvider({ children }) {
  const [officer, setOfficer] = useState(() => { try { return JSON.parse(localStorage.getItem(OFFICER_KEY) || 'null') } catch { return null } })
  const [ready, setReady] = useState(!tokenStore.get())

  useEffect(() => {
    if (!tokenStore.get()) return
    endpoints.me().then(({ officer: current }) => { localStorage.setItem(OFFICER_KEY, JSON.stringify(current)); setOfficer(current) }).catch(() => { localStorage.removeItem(OFFICER_KEY); setOfficer(null) }).finally(() => setReady(true))
    const clear = () => { tokenStore.set(null); localStorage.removeItem(OFFICER_KEY); setOfficer(null); setReady(true) }
    window.addEventListener('rpd:unauthorized', clear)
    return () => window.removeEventListener('rpd:unauthorized', clear)
  }, [])

  const login = async (credentials) => {
    const result = await endpoints.login(credentials)
    tokenStore.set(result.token)
    localStorage.setItem(OFFICER_KEY, JSON.stringify(result.officer))
    setOfficer(result.officer)
    return result.officer
  }
  const logout = async () => {
    try { await endpoints.logout() } finally { tokenStore.set(null); localStorage.removeItem(OFFICER_KEY); setOfficer(null) }
  }

  return <AuthContext.Provider value={{ officer, ready, login, logout }}>{children}</AuthContext.Provider>
}

export const useAuth = () => useContext(AuthContext)
