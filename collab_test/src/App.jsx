import { Navigate, NavLink, Outlet, Route, Routes, useNavigate } from 'react-router-dom'
import { useAuth } from './context/AuthContext'
import { ActivityPage } from './pages/ActivityPage'
import { CommandPage } from './pages/CommandPage'
import { DatabasePage } from './pages/DatabasePage'
import { LoginPage } from './pages/LoginPage'
import { OffensesPage } from './pages/OffensesPage'
import { useToast } from './context/ToastContext'
import { useApiStatus } from './hooks/api'
import { Logo, PageFooter } from './components/UI'

function Protected({ children }) {
  const { officer, ready } = useAuth()
  if (!ready) return <div className="boot-screen"><span className="boot-mark">RPD</span><small>AUTHENTICATING SECURE SESSION…</small></div>
  return officer ? children : <Navigate to="/login" replace />
}

function Shell() {
  const { officer, logout } = useAuth()
  const navigate = useNavigate()
  const toast = useToast()
  const apiStatus = useApiStatus()
  const signOut = async () => { await logout(); toast('SESSION TERMINATED'); navigate('/login') }
  return <div className="app-shell">
    <header className="topbar">
      <NavLink to="/command" className="brand-link"><Logo /></NavLink>
      <nav className="main-nav" aria-label="Main navigation">
        <NavLink to="/command">COMMAND</NavLink><NavLink to="/database">DATABASE</NavLink><NavLink to="/offenses">OFFENSE INDEX</NavLink><NavLink to="/activity">AUDIT LOG</NavLink>
      </nav>
      <div className="session">{apiStatus.isError && <b className="offline-tag">OFFLINE</b>}<span className={`api-light ${apiStatus.isError ? 'offline' : ''}`} title={apiStatus.isError ? 'API offline' : 'API connected'} /><span className="initials">{officer?.initials || 'AC'}</span><div><small>{apiStatus.isError ? 'OFFLINE // API' : `SESSION: ${officer?.rank || 'OFFICER'}`}</small><b>{officer?.name || 'AUTHORIZED USER'}</b></div><button className="button button-outline signout" onClick={signOut}>SIGN OUT</button></div>
    </header>
    <main className="page-content"><Outlet /></main>
    <PageFooter />
  </div>
}

export default function App() {
  return <Routes>
    <Route path="/login" element={<LoginPage />} />
    <Route element={<Protected><Shell /></Protected>}>
      <Route path="/" element={<Navigate to="/command" replace />} />
      <Route path="/command" element={<CommandPage />} />
      <Route path="/database" element={<DatabasePage />} />
      <Route path="/database/:id" element={<DatabasePage />} />
      <Route path="/offenses" element={<OffensesPage />} />
      <Route path="/activity" element={<ActivityPage />} />
    </Route>
    <Route path="*" element={<Navigate to="/command" replace />} />
  </Routes>
}
