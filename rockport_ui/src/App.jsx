import { Navigate, NavLink, Outlet, Route, Routes, useNavigate } from 'react-router-dom'
import { AccessDenied } from './components/AccessDenied'
import { LockIcon } from './components/GuardedButton'
import { Logo, PageFooter } from './components/ui'
import { useAuth } from './context/auth'
import { useToast } from './context/toast'
import { useApiStatus } from './hooks/queries'
import { AccessStatusPage } from './features/access/AccessStatusPage'
import { RequestAccessPage } from './features/access/RequestAccessPage'
import { AdminPage } from './features/admin/AdminPage'
import { ArchivedTab } from './features/admin/ArchivedTab'
import { PersonnelTab } from './features/admin/PersonnelTab'
import { RequestsTab } from './features/admin/RequestsTab'
import { AuditPage } from './features/audit/AuditPage'
import { LoginPage } from './features/auth/LoginPage'
import { CaseFilePage } from './features/casefile/CaseFilePage'
import { CommandPage } from './features/dashboard/CommandPage'
import { InfractionIndexPage } from './features/infractions/InfractionIndexPage'
import { ManhuntDetail } from './features/manhunts/ManhuntDetail'
import { ManhuntsTab } from './features/manhunts/ManhuntsTab'
import { RankingsPage } from './features/rankings/RankingsPage'
import { ReportsPage } from './features/reports/ReportsPage'
import { AnalyticsTab } from './features/subjects/AnalyticsTab'
import { CostTab } from './features/subjects/CostTab'
import { DatabasePage, NoRecordSelected } from './features/subjects/DatabasePage'
import { HistoryTab } from './features/subjects/HistoryTab'
import { InfractionsTab } from './features/subjects/InfractionsTab'
import { LocationsTab } from './features/subjects/LocationsTab'
import { NotesTab } from './features/subjects/NotesTab'
import { OverviewTab } from './features/subjects/OverviewTab'
import { SubjectLayout } from './features/subjects/SubjectLayout'

function Protected({ children }) {
  const { officer, ready } = useAuth()

  if (!ready) {
    return (
      <div className="boot-screen">
        <span className="boot-mark">BPD</span>
        <small>AUTHENTICATING SECURE SESSION…</small>
      </div>
    )
  }

  return officer ? children : <Navigate to="/login" replace />
}

/**
 * A page for officers at or above this level. Everyone else sees a warning that says
 * what is needed, instead of being sent away without a word.
 */
function RequireLevel({ level, area, children }) {
  const { can } = useAuth()

  return can(level) ? children : <AccessDenied level={level} area={area} />
}

/** A menu link. If your level is too low it shows a lock, and the page explains why. */
function NavItem({ to, level, end = false, children }) {
  const { can } = useAuth()
  const allowed = can(level)

  return (
    <NavLink to={to} end={end} className={allowed ? '' : 'locked'} title={allowed ? undefined : `Needs level ${level}`}>
      {!allowed && <LockIcon />}
      {children}
    </NavLink>
  )
}

function Shell() {
  const { officer, logout } = useAuth()
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
          <NavItem to="/command" level={1}>COMMAND</NavItem>
          <NavItem to="/database" level={2}>DATABASE</NavItem>
          <NavItem to="/reports" level={1}>REPORTS</NavItem>
          <NavItem to="/infractions" level={2}>INFRACTIONS</NavItem>
          <NavItem to="/rankings" level={2}>RANKINGS</NavItem>
          <NavItem to="/audit" level={4}>AUDIT LOG</NavItem>
          <NavItem to="/admin" level={5}>ADMIN</NavItem>
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
      <Route path="/request-access" element={<RequestAccessPage />} />
      <Route path="/access-status" element={<AccessStatusPage />} />

      <Route element={<Protected><Shell /></Protected>}>
        <Route path="/" element={<Navigate to="/command" replace />} />
        <Route path="/command" element={<CommandPage />} />
        <Route path="/reports" element={<ReportsPage />} />

        <Route path="/database" element={<RequireLevel level={2} area="THE DATABASE OF PEOPLE"><DatabasePage /></RequireLevel>}>
          <Route index element={<NoRecordSelected />} />
          <Route path=":id" element={<SubjectLayout />}>
            <Route index element={<OverviewTab />} />
            <Route path="infractions" element={<InfractionsTab />} />
            <Route path="manhunts" element={<ManhuntsTab />} />
            <Route path="manhunts/:manhuntId" element={<ManhuntDetail />} />
            <Route path="locations" element={<LocationsTab />} />
            <Route path="cost" element={<CostTab />} />
            <Route path="analytics" element={<AnalyticsTab />} />
            <Route path="notes" element={<NotesTab />} />
            <Route path="history" element={<HistoryTab />} />
          </Route>
        </Route>

        <Route path="/case-file/:id" element={<RequireLevel level={3} area="CASE FILES"><CaseFilePage /></RequireLevel>} />
        <Route path="/infractions" element={<RequireLevel level={2} area="THE INFRACTION INDEX"><InfractionIndexPage /></RequireLevel>} />
        <Route path="/rankings" element={<RequireLevel level={2} area="RANKINGS"><RankingsPage /></RequireLevel>} />
        <Route path="/audit" element={<RequireLevel level={4} area="THE AUDIT LOG"><AuditPage /></RequireLevel>} />

        <Route path="/admin" element={<RequireLevel level={5} area="PERSONNEL AND ACCESS"><AdminPage /></RequireLevel>}>
          <Route index element={<RequestsTab />} />
          <Route path="personnel" element={<PersonnelTab />} />
          <Route path="archived" element={<ArchivedTab />} />
        </Route>
      </Route>
      <Route path="*" element={<Navigate to="/command" replace />} />
    </Routes>
  )
}
