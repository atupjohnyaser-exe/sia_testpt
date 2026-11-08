import { Link } from 'react-router-dom'

/** The frame for pages anyone can open without signing in. */
export function PublicShell({ eyebrow, title, intro, children }) {
  return (
    <main className="public-page">
      <header className="public-header">
        <Link to="/login" className="login-brand">
          <div className="brand-seal"><span>BPD</span></div>
          <b>BOHOL POLICE DEPARTMENT</b>
        </Link>
        <Link to="/login" className="text-link">← BACK TO SIGN IN</Link>
      </header>
      <section className="public-body">
        <small className="eyebrow">{eyebrow}</small>
        <h1>{title}</h1>
        {intro && <p className="public-intro">{intro}</p>}
        {children}
      </section>
      <footer className="public-footer">FICTIONAL TRAINING DATA // ALL PEOPLE AND RECORDS ARE FICTIONAL</footer>
    </main>
  )
}
