import { NavLink, Outlet } from 'react-router-dom'
import { PageHeading, StatCell } from '../../components/ui'
import { useAdminSummary } from '../../hooks/queries'
import { LEVELS } from '../../lib/clearance'

export function AdminPage() {
  const summary = useAdminSummary()
  const data = summary.data?.data
  const peak = Math.max(1, ...(data?.by_level ?? []).map((level) => level.total))

  return (
    <div className="content-page">
      <PageHeading eyebrow="CHIEF ADMIN ONLY" title="PERSONNEL & ACCESS" action={<span className="online-indicator">LEVEL 5</span>}>
        Review applications for access, manage the people who have it, and restore archived records.
      </PageHeading>

      <section className="stats-grid admin-stats">
        <StatCell label="WAITING FOR REVIEW" caption="NEW APPLICATIONS" tone={data?.pending_requests > 0 ? 'red' : ''} value={data?.pending_requests} />
        <StatCell label="ACTIVE PERSONNEL" caption="CAN SIGN IN" value={data?.active_personnel} />
        <StatCell label="INACTIVE" caption="SIGN IN BLOCKED" value={data?.inactive_personnel} />
        <StatCell label="ARCHIVED RECORDS" caption="CAN BE RESTORED" value={data?.archived_records} />
      </section>

      <section className="level-bars" aria-label="Personnel by clearance level">
        {(data?.by_level ?? []).map((level) => (
          <div className="level-bar" key={level.level}>
            <span><b>L{level.level}</b> {level.label.toUpperCase()}</span>
            <div className="bar-track"><i style={{ width: `${(level.total / peak) * 100}%` }} className={`l${level.level}`} /></div>
            <strong>{level.total}</strong>
            <small>{LEVELS[level.level].summary}</small>
          </div>
        ))}
      </section>

      <nav className="tabs" aria-label="Chief admin sections">
        <NavLink to="/admin" end>APPLICATIONS{data?.pending_requests > 0 && <i>{data.pending_requests}</i>}</NavLink>
        <NavLink to="/admin/personnel">PERSONNEL</NavLink>
        <NavLink to="/admin/archived">ARCHIVED RECORDS</NavLink>
      </nav>

      <Outlet />
    </div>
  )
}
