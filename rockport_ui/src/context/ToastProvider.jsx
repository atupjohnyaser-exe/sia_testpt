import { useCallback, useRef, useState } from 'react'
import { ToastContext } from './toast'

export function ToastProvider({ children }) {
  const [message, setMessage] = useState('')
  const timer = useRef(null)

  const showToast = useCallback((text) => {
    window.clearTimeout(timer.current)
    setMessage(text)
    timer.current = window.setTimeout(() => setMessage(''), 3200)
  }, [])

  return (
    <ToastContext.Provider value={showToast}>
      {children}
      {message && (
        <div className="toast" role="status">
          {message}
        </div>
      )}
    </ToastContext.Provider>
  )
}
