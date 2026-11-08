import { useCallback, useEffect, useRef, useState } from 'react'
import { levelName } from '../lib/clearance'
import { useAuth } from './auth'
import { WarningContext } from './warning'

/**
 * The "no clearance" banner. Any refused request, and any locked button, ends up here
 * with the same wording: what was tried, which level is needed, and which level you have.
 */
export function WarningProvider({ children }) {
  const { officer } = useAuth()
  const [warning, setWarning] = useState(null)
  const timer = useRef(null)

  const show = useCallback((details) => {
    window.clearTimeout(timer.current)
    setWarning(details)
    timer.current = window.setTimeout(() => setWarning(null), 9000)
  }, [])

  const deny = useCallback(
    (required, action = 'This action') =>
      show({
        message: `${action} needs ${levelName(required)} (level ${required}) or higher.`,
        required,
        yours: officer?.clearance_level ?? null,
      }),
    [officer, show],
  )

  // Refusals that come back from the server.
  useEffect(() => {
    const onForbidden = (event) => {
      const data = event.detail

      show({
        message: data.message,
        required: data.required_level,
        yours: data.your_level,
      })
    }

    window.addEventListener('bpd:forbidden', onForbidden)

    return () => window.removeEventListener('bpd:forbidden', onForbidden)
  }, [show])

  return (
    <WarningContext.Provider value={{ deny, show }}>
      {children}
      {warning && (
        <aside className="clearance-warning" role="alert">
          <span className="warn-mark" aria-hidden="true">!</span>
          <div>
            <b>NO CLEARANCE</b>
            <p>{warning.message}</p>
            {warning.required && (
              <small>
                YOU: {warning.yours ? `LEVEL ${warning.yours} // ${levelName(warning.yours).toUpperCase()}` : 'UNKNOWN'}
                {'  '}NEEDED: LEVEL {warning.required} // {levelName(warning.required).toUpperCase()}. ASK A HIGHER-RANKING OFFICER OR THE CHIEF ADMIN.
              </small>
            )}
          </div>
          <button type="button" aria-label="Dismiss warning" onClick={() => setWarning(null)}>×</button>
        </aside>
      )}
    </WarningContext.Provider>
  )
}
