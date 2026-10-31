import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ErrorState, LoadingRows, Panel, StatCell } from '../../components/ui'
import { ThreatPips } from '../../components/ThreatMeter'
import { useAuth } from '../../context/auth'
import { useBulletin, usePriority, useStats } from '../../hooks/queries'
import { initials, num } from '../../lib/format'
import { SubjectFormModal } from '../subjects/SubjectFormModal'

function greeting(date) {
  const hour = date.getHours()

  return hour < 12 ? 'GOOD MORNING' : hour < 18 ? 'GOOD AFTERNOON' : 'GOOD EVENING'
}

export function CommandPage() {
  const navigate = useNavigate()
  const { officer, can } = useAuth()
  const stats = useStats()
  const priority = usePriority()
  const bulletin = useBulletin()
  const [creating, setCreating] = useState(false)
  const [now] = useState(() => new Date())
  const values = stats.data?.data ?? {}
  const current = bulletin.data?.data

  return (
    <>
      <section className="command-hero">
        <div>
          <small className="eyebrow">SECURE SESSION // TERMINAL 04</small>
          <h1>{greeting(now)},<br />{(officer?.name ?? 'OFFICER').toUpperCase()}.</h1>
          <p>
            Intelligence summary is current as of{' '}
            {now.toLocaleString([], { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}.
            Review priority subjects and outstanding records below.
          </p>
        </div>
        <Link to="/database" className="button">OPEN RECORDS DATABASE <span>→</span></Link>
      </section>

      {stats.isError ? (
        <ErrorState onRetry={() => stats.refetch()} />
      ) : (
        <section className="stats-grid">
          <StatCell label="ACTIVE RECORDS" caption={`+${values.new_this_week ?? 0} THIS WEEK`} value={values.active_records} />
          <StatCell label="OUTSTANDING WARRANTS" caption="STATUS WANTED" value={values.outstanding_warrants} />
          <StatCell label="IN CUSTODY" caption="CURRENTLY HELD" value={values.in_custody} />
          <StatCell label="CRITICAL THREAT" caption="LEVEL 5 AND ABOVE" tone="red" value={values.critical_threat} />
          <StatCell label="TOTAL BOUNTY" caption="ACTIVE RECORDS" value={values.total_bounty} />
          <StatCell label="COST TO STATE" caption="ALL MANHUNTS" value={values.total_cost_to_state} />
          <StatCell label="MANHUNTS LOGGED" caption="ALL FILES" value={values.manhunts_logged} />
        </section>
      )}

      <div className="dashboard-grid">
        <Panel eyebrow="OPERATIONAL INTELLIGENCE" title="PRIORITY SUBJECTS" action={<Link className="text-link" to="/database">VIEW ALL →</Link>} className="priority-panel">
          {priority.isLoading ? (
            <LoadingRows count={3} />
          ) : priority.isError ? (
            <ErrorState onRetry={() => priority.refetch()} />
          ) : priority.data?.data?.length ? (
            <div className="priority-list">
              {priority.data.data.map((subject, index) => (
                <button className="priority-row" key={subject.id} onClick={() => navigate(`/database/${subject.id}`)}>
                  <small className="row-index">{String(index + 1).padStart(2, '0')}</small>
                  <span className="subject-mark">{initials(subject.name)}</span>
                  <span className="subject-name">
                    <b>{subject.name}</b>
                    <small>AKA: {subject.alias || 'UNKNOWN'} // {subject.case_id}</small>
                  </span>
                  <span className="last-known">
                    <small>LAST KNOWN</small>
                    <b>{subject.last_known_location || 'UNCONFIRMED'}</b>
                  </span>
                  <span className="risk-label">
                    <small>BOUNTY</small>
                    <b>{num(subject.bounty)}</b>
                    <ThreatPips level={subject.threat.level} />
                  </span>
                  <span className="row-arrow">→</span>
                </button>
              ))}
            </div>
          ) : (
            <div className="empty-state"><b>NO PRIORITY SUBJECTS FOUND</b></div>
          )}
        </Panel>

        <aside className="side-panels">
          <section className="bulletin">
            <div className="bulletin-top">
              <span>●</span>
              <b>SYSTEM BULLETIN</b>
              <small>{current?.published_at ? new Date(current.published_at).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }) : '--:--'}</small>
            </div>
            <p>{current?.body || 'No active bulletins. Check the records feed for the latest operational notes.'}</p>
            <small>BRIEFING REF // {current?.ref || 'OPS—NONE'}</small>
          </section>

          <Panel eyebrow="SHORTCUTS" title="QUICK ACCESS" className="shortcut-panel">
            <Link className="shortcut" to="/database"><span>01</span><div><b>SUBJECT SEARCH</b><small>Search all active records</small></div><b>→</b></Link>
            <Link className="shortcut" to="/rankings"><span>02</span><div><b>RANKINGS</b><small>Longest, costliest, biggest manhunts</small></div><b>→</b></Link>
            <Link className="shortcut" to="/infractions"><span>03</span><div><b>INFRACTION INDEX</b><small>Browse charges across all files</small></div><b>→</b></Link>
            {can(4) && <Link className="shortcut" to="/audit"><span>04</span><div><b>AUDIT LOG</b><small>Review access activity</small></div><b>→</b></Link>}
            {can(3) && (
              <button className="shortcut" onClick={() => setCreating(true)}>
                <span>05</span><div><b>NEW SUBJECT RECORD</b><small>Register a new case file</small></div><b>→</b>
              </button>
            )}
          </Panel>
        </aside>
      </div>

      <div className="training-strip">
        <span>FICTIONAL TRAINING DATA</span>
        <span>DEMO RECORDS ARE NOT REAL PEOPLE OR OFFICIAL CRIMINAL RECORDS</span>
      </div>

      {creating && <SubjectFormModal onClose={() => setCreating(false)} />}
    </>
  )
}
