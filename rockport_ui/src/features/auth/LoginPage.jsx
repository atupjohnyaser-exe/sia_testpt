import { useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { useAuth } from '../../context/auth'
import { errorMessage } from '../../lib/errors'

export function LoginPage() {
  const { officer, login } = useAuth()
  const navigate = useNavigate()
  const [badge, setBadge] = useState('')
  const [key, setKey] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  if (officer) return <Navigate to="/command" replace />

  const submit = async (event) => {
    event.preventDefault()
    setError('')
    setBusy(true)

    try {
      await login({ badge_id: badge, access_key: key })
      navigate('/command')
    } catch (issue) {
      setError(
        issue.response
          ? errorMessage(issue, 'Sign in failed.')
          : 'CANNOT REACH THE RECORDS SERVER. CHECK THAT THE API IS RUNNING.',
      )
    } finally {
      setBusy(false)
    }
  }

  return (
    <main className="login-screen">
      <section className="login-hero">
        <div className="login-brand">
          <div className="brand-seal"><span>RPD</span></div>
          <b>ROCKPORT POLICE DEPARTMENT</b>
        </div>
        <div className="hero-copy">
          <small className="eyebrow">SECURE RECORDS NETWORK // SRN-04</small>
          <h1>KNOW THE RECORD.<br />PROTECT THE CITY.</h1>
          <p>Restricted access portal for subject records, bounties, manhunt reports, and threat assessments.</p>
        </div>
        <div className="network-stats">
          <div><small>NETWORK</small><b>ENCRYPTED</b></div>
          <div><small>DATABASE</small><b>ONLINE</b></div>
          <div><small>SESSION</small><b>8 HOUR LIMIT</b></div>
        </div>
        <span className="hero-watermark">RPD</span>
      </section>

      <section className="login-panel">
        <div className="login-icon" aria-hidden="true">▥</div>
        <small className="eyebrow">IDENTITY VERIFICATION</small>
        <h2>OFFICER SIGN IN</h2>
        <p>Enter your department credentials to begin a secure session.</p>
        <form onSubmit={submit}>
          <label className="field">
            <span>BADGE ID</span>
            <div className="input-prefix">
              <b>ID</b>
              <input value={badge} onChange={(event) => setBadge(event.target.value)} placeholder="E.G. RP-1047" autoComplete="username" required />
            </div>
          </label>
          <label className="field">
            <span>ACCESS KEY</span>
            <div className="input-prefix">
              <b>••</b>
              <input type="password" value={key} onChange={(event) => setKey(event.target.value)} placeholder="ENTER SECURE KEY" autoComplete="current-password" required />
            </div>
          </label>
          {error && <p className="form-error">{error}</p>}
          <button className="button login-submit" disabled={busy}>
            {busy ? 'VERIFYING…' : 'AUTHENTICATE SESSION'}<span>→</span>
          </button>
        </form>
        {import.meta.env.DEV && <small className="demo-hint">DEV BUILD // DEMO LOGIN RP-1047 / demo1234</small>}
        <div className="authorized-note">
          <b>AUTHORIZED PERSONNEL ONLY</b>
          <span>All access attempts, including failed ones, are logged and monitored.</span>
        </div>
        <small className="fiction-note">FICTIONAL TRAINING DATA // ALL PEOPLE AND RECORDS ARE FICTIONAL</small>
      </section>
    </main>
  )
}
