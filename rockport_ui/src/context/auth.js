import { createContext, useContext } from 'react'

export const AuthContext = createContext(null)

/** officer, ready, login, logout, and can(level) to check clearance. */
export const useAuth = () => useContext(AuthContext)
