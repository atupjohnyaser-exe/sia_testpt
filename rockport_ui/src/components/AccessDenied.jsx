import { Link } from 'react-router-dom'
import { useAuth } from '../context/auth'
import { LEVELS, levelName } from '../lib/clearance'

/** Shown instead of a page the signed-in officer is not cleared for. */
export function AccessDenied({ level, area }) {
  const { officer } = useAuth()

  return (
    <section className="access-denied" role="alert">
      <div className="denied-mark" aria-hidden="true">!</div>
      <small className="eyebrow">NO CLEARANCE</small>
      <h1>{area} IS RESTRICTED</h1>
      <p>
        This needs <b>{levelName(level)}</b> (level {level}) or higher.
        You are signed in as <b>{officer.clearance_label}</b> (level {officer.clearance_level}).
      </p>
      <p className="denied-hint">{levelName(level)}: {LEVELS[level].summary}</p>
      <p className="denied-hint">Ask a higher-ranking officer, or the Chief Admin, if you need access.</p>
      <Link to="/command" className="button button-outline">← BACK TO COMMAND</Link>
    </section>
  )
}
