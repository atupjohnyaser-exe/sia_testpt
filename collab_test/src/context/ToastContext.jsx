import { createContext, useCallback, useContext, useState } from 'react'

const ToastContext = createContext(() => {})
export function ToastProvider({ children }) {
  const [toast, setToast] = useState('')
  const showToast = useCallback((message) => {
    setToast(message)
    window.setTimeout(() => setToast(''), 3200)
  }, [])
  return <ToastContext.Provider value={showToast}>{children}{toast && <div className="toast" role="status">{toast}</div>}</ToastContext.Provider>
}
export const useToast = () => useContext(ToastContext)
