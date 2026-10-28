import { useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export function LoginPage() {
  const { officer, login } = useAuth()
  const navigate = useNavigate()
  const [badge, setBadge] = useState('')
  const [key, setKey] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)
  if (officer) return <Navigate to="/command" replace />
  const submit = async (event) => {
    event.preventDefault(); setError(''); setBusy(true)
    try { await login({ badge_id: badge, access_key: key }); navigate('/command') }
    catch (issue) { setError(issue.response?.data?.message || 'API CONNECTION FAILED. CHECK THE BACKEND AND TRY AGAIN.') }
    finally { setBusy(false) }
  }
  return <main className="login-screen">
    <section className="login-hero">
      <div className="login-brand"><div className="brand-seal"><span>RPD</span></div><b>ROCKPORT POLICE DEPARTMENT</b></div>
      <div className="hero-copy"><small className="eyebrow">SECURE RECORDS NETWORK // SRN-04</small><h1>KNOW THE RECORD.<br />PROTECT THE CITY.</h1><p>Restricted access portal for criminal records, active warrants, threat assessments, and case intelligence.</p></div>
      <div className="network-stats"><div><small>NETWORK</small><b>ENCRYPTED</b></div><div><small>DATABASE</small><b>ONLINE</b></div><div><small>ACCESS</small><b>LEVEL 03</b></div></div>
      <span className="hero-watermark">RPD</span>
    </section>
    <section className="login-panel">
      <div className="login-icon" aria-hidden="true">▥</div><small className="eyebrow">IDENTITY VERIFICATION</small><h2>OFFICER SIGN IN</h2><p>Enter your department credentials to begin a secure session.</p>
      <form onSubmit={submit}>
        <label className="field"><span>BADGE ID</span><div className="input-prefix"><b>ID</b><input value={badge} onChange={(e) => setBadge(e.target.value)} placeholder="E.G. RP-1047" autoComplete="username" required /></div></label>
        <label className="field"><span>ACCESS KEY</span><div className="input-prefix"><b>••</b><input type="password" value={key} onChange={(e) => setKey(e.target.value)} placeholder="ENTER SECURE KEY" autoComplete="current-password" required /></div></label>
        {error && <p className="form-error">{error}</p>}
        <button className="button login-submit" disabled={busy}>{busy ? 'VERIFYING…' : 'AUTHENTICATE SESSION'}<span>→</span></button>
      </form>
      <small className="demo-hint">DEMO MODE // ENTER ANY BADGE ID AND ACCESS KEY</small>
      <div className="authorized-note"><b>AUTHORIZED PERSONNEL ONLY</b><span>All access attempts are logged and monitored.</span></div>
      <small className="fiction-note">FICTIONAL TRAINING DATA // ALL PEOPLE AND RECORDS ARE FICTIONAL</small>
    </section>
  </main>
}
