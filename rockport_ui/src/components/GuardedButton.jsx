import { Link } from 'react-router-dom'
import { useAuth } from '../context/auth'
import { useWarning } from '../context/warning'

function LockIcon() {
  return (
    <svg className="lock-icon" viewBox="0 0 10 12" width="9" height="11" aria-hidden="true">
      <rect x="1" y="5" width="8" height="6.4" rx="1" fill="currentColor" />
      <path d="M2.8 5V3.4a2.2 2.2 0 0 1 4.4 0V5" fill="none" stroke="currentColor" strokeWidth="1.4" />
    </svg>
  )
}

/**
 * A button for an action that needs a clearance level.
 * Cleared officers get a normal button. Everyone else sees it with a lock, and pressing it
 * explains what is missing instead of doing nothing.
 */
export function GuardedButton({ level, action, onClick, className = 'button button-outline', children, ...rest }) {
  const { can } = useAuth()
  const { deny } = useWarning()
  const allowed = can(level)

  return (
    <button
      type="button"
      className={`${className} ${allowed ? '' : 'is-locked'}`}
      aria-disabled={allowed ? undefined : true}
      title={allowed ? undefined : `Needs level ${level}`}
      onClick={allowed ? onClick : () => deny(level, action)}
      {...rest}
    >
      {!allowed && <LockIcon />}
      {children}
    </button>
  )
}

/** The same idea for a link that goes to another page. */
export function GuardedLink({ level, action, to, className = 'button button-outline', children }) {
  const { can } = useAuth()
  const { deny } = useWarning()

  if (can(level)) {
    return <Link to={to} className={className}>{children}</Link>
  }

  return (
    <button type="button" className={`${className} is-locked`} aria-disabled="true" title={`Needs level ${level}`} onClick={() => deny(level, action)}>
      <LockIcon />
      {children}
    </button>
  )
}

export { LockIcon }
