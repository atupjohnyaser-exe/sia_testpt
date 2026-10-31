import { Navigate, NavLink, Outlet, Route, Routes, useNavigate } from 'react-router-dom'
import { Logo, PageFooter } from './components/ui'
import { useAuth } from './context/auth'
import { useToast } from './context/toast'
import { useApiStatus } from './hooks/queries'
import { AuditPage } from './features/audit/AuditPage'
import { LoginPage } from './features/auth/LoginPage'
import { CaseFilePage } from './features/casefile/CaseFilePage'
import { CommandPage } from './features/dashboard/CommandPage'
import { InfractionIndexPage } from './features/infractions/InfractionIndexPage'
import { ManhuntDetail } from './features/manhunts/ManhuntDetail'
import { RankingsPage } from './features/rankings/RankingsPage'
import { CostTab } from './features/subjects/CostTab'
import { DatabasePage, NoRecordSelected } from './features/subjects/DatabasePage'
import { HistoryTab } from './features/subjects/HistoryTab'
import { InfractionsTab } from './features/subjects/InfractionsTab'
import { LocationsTab } from './features/subjects/LocationsTab'
import { ManhuntsTab } from './features/manhunts/ManhuntsTab'
import { NotesTab } from './features/subjects/NotesTab'
import { OverviewTab } from './features/subjects/OverviewTab'
import { SubjectLayout } from './features/subjects/SubjectLayout'

function Protected({ children }) {
  const { officer, ready } = useAuth()

  if (!ready) {
    return (
      <div className="boot-screen">
        <span className="boot-mark">RPD</span>
        <small>AUTHENTICATING SECURE SESSION…</small>
      </div>
    )
  }

  return officer ? children : <Navigate to="/login" replace />
}

/** Only shows its page to officers at or above this clearance. */
function RequireClearance({ level, children }) {
  const { can } = useAuth()

  return can(level) ? children : <Navigate to="/command" replace />
}

function Shell() {
  const { officer, logout, can } = useAuth()
  const navigate = useNavigate()
  const toast = useToast()
  const apiStatus = useApiStatus()
  const offline = apiStatus.isError && !apiStatus.error?.response

  const signOut = async () => {
    await logout()
    toast('SESSION TERMINATED')
    navigate('/login')
  }

  return (
    <div className="app-shell">
      <header className="topbar">
        <NavLink to="/command" className="brand-link"><Logo /></NavLink>
        <nav className="main-nav" aria-label="Main navigation">
          <NavLink to="/command">COMMAND</NavLink>
          <NavLink to="/database">DATABASE</NavLink>
          <NavLink to="/infractions">INFRACTIONS</NavLink>
          <NavLink to="/rankings">RANKINGS</NavLink>
          {can(4) && <NavLink to="/audit">AUDIT LOG</NavLink>}
        </nav>
        <div className="session">
          {offline && <b className="offline-tag">OFFLINE</b>}
          <span className={`api-light ${offline ? 'offline' : ''}`} title={offline ? 'API offline' : 'API connected'} />
          <span className="initials">{officer?.initials}</span>
          <div>
            <small>{offline ? 'OFFLINE // API' : `${officer?.clearance_label?.toUpperCase()} // LEVEL ${officer?.clearance_level}`}</small>
            <b>{officer?.name}</b>
          </div>
          <button className="button button-outline signout" onClick={signOut}>SIGN OUT</button>
        </div>
      </header>
      <main className="page-content"><Outlet /></main>
      <PageFooter />
    </div>
  )
}

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route element={<Protected><Shell /></Protected>}>
        <Route path="/" element={<Navigate to="/command" replace />} />
        <Route path="/command" element={<CommandPage />} />

        <Route path="/database" element={<DatabasePage />}>
          <Route index element={<NoRecordSelected />} />
          <Route path=":id" element={<SubjectLayout />}>
            <Route index element={<OverviewTab />} />
            <Route path="infractions" element={<InfractionsTab />} />
            <Route path="manhunts" element={<ManhuntsTab />} />
            <Route path="manhunts/:manhuntId" element={<ManhuntDetail />} />
            <Route path="locations" element={<LocationsTab />} />
            <Route path="cost" element={<CostTab />} />
            <Route path="notes" element={<NotesTab />} />
            <Route path="history" element={<HistoryTab />} />
          </Route>
        </Route>

        <Route path="/case-file/:id" element={<RequireClearance level={3}><CaseFilePage /></RequireClearance>} />
        <Route path="/infractions" element={<InfractionIndexPage />} />
        <Route path="/rankings" element={<RankingsPage />} />
        <Route path="/audit" element={<RequireClearance level={4}><AuditPage /></RequireClearance>} />
      </Route>
      <Route path="*" element={<Navigate to="/command" replace />} />
    </Routes>
  )
}
