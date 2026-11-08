import { createContext, useContext } from 'react'

export const WarningContext = createContext({ deny: () => {}, show: () => {} })

/** deny(level, 'what you tried') shows the "no clearance" warning. show(text) shows any warning. */
export const useWarning = () => useContext(WarningContext)
