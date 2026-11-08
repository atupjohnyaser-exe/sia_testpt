import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { DigitalRain } from '../../components/DigitalRain'
import { GuardedButton, LockIcon } from '../../components/GuardedButton'
import { ErrorState, LoadingRows, Panel, StatCell } from '../../components/ui'
import { ThreatPips } from '../../components/ThreatMeter'
import { useAuth } from '../../context/auth'
import { useBulletin, usePriority, useStats } from '../../hooks/queries'
import { useClock } from '../../hooks/useClock'
import { initials, num } from '../../lib/format'
import { SubjectFormModal } from '../subjects/SubjectFormModal'

function greeting(date) {
  const hour = date.getHours()

  return hour < 12 ? 'GOOD MORNING' : hour < 18 ? 'GOOD AFTERNOON' : 'GOOD EVENING'
}

/** A quick link. Shows a lock when your level is too low, and the page it opens explains why. */
function Shortcut({ to, level, number, title, text }) {
  const { can } = useAuth()
  const allowed = can(level)

  return (
    <Link className={`shortcut ${allowed ? '' : 'locked'}`} to={to}>
      <span>{number}</span>
      <div><b>{title}</b><small>{allowed ? text : `Needs level ${level}`}</small></div>
      {allowed ? <b>→</b> : <LockIcon />}
    </Link>
  )
}

export function CommandPage() {
  const navigate = useNavigate()
  const { officer, can } = useAuth()
  const now = useClock()
  const seesPeople = can(2)
  const stats = useStats()
  const priority = usePriority(seesPeople)
  const bulletin = useBulletin()
  const [creating, setCreating] = useState(false)
  const values = stats.data?.data ?? {}
  const current = bulletin.data?.data

  return (
    <div className="command-stage">
      <DigitalRain />

      <div className="command-content">
        <section className="command-hero">
          <div>
            <small className="eyebrow">SECURE SESSION // TERMINAL 04</small>
            <h1>{greeting(now)},<br />{(officer?.name ?? 'OFFICER').toUpperCase()}.</h1>
            <p>
              {officer?.rank ? `${officer.rank}. ` : ''}
              {seesPeople
                ? 'Review priority subjects and outstanding records below.'
                : 'You can file field reports. Records of people are hidden at your level.'}
            </p>
          </div>
          <div className="hero-side">
            <div className="live-clock" aria-label="System time">
              <small>SYSTEM TIME</small>
              <b>{now.toLocaleTimeString('en-GB')}</b>
              <span>{now.toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }).toUpperCase()}</span>
            </div>
            {seesPeople ? (
              <Link to="/database" className="button">OPEN RECORDS DATABASE <span>→</span></Link>
            ) : (
              <Link to="/reports" className="button">FILE A REPORT <span>→</span></Link>
            )}
          </div>
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
          {seesPeople ? (
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
          ) : (
            <Panel eyebrow="RESTRICTED" title="PRIORITY SUBJECTS" className="priority-panel">
              <div className="restricted-box">
                <LockIcon />
                <b>PEOPLE ARE HIDDEN AT LEVEL {officer?.clearance_level}</b>
                <p>Records of people need Senior Officer clearance (level 2) or higher. Your reports are still reviewed and linked to records by the officers who can see them.</p>
                <Link className="button button-outline" to="/reports">GO TO MY REPORTS</Link>
              </div>
            </Panel>
          )}

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
              <Shortcut to="/reports" level={1} number="01" title="FIELD REPORTS" text="File and read reports" />
              <Shortcut to="/database" level={2} number="02" title="SUBJECT SEARCH" text="Search all active records" />
              <Shortcut to="/rankings" level={2} number="03" title="RANKINGS" text="Longest, costliest, biggest manhunts" />
              <Shortcut to="/infractions" level={2} number="04" title="INFRACTION INDEX" text="Browse charges across all files" />
              <Shortcut to="/audit" level={4} number="05" title="AUDIT LOG" text="Review access activity" />
              <Shortcut to="/admin" level={5} number="06" title="PERSONNEL & ACCESS" text="Applications, accounts, restores" />
              <GuardedButton level={3} action="Creating a record" className="shortcut" onClick={() => setCreating(true)}>
                <span>07</span><div><b>NEW SUBJECT RECORD</b><small>Register a new case file</small></div><b>→</b>
              </GuardedButton>
            </Panel>
          </aside>
        </div>

        <div className="training-strip">
          <span>FICTIONAL TRAINING DATA</span>
          <span>DEMO RECORDS ARE NOT REAL PEOPLE OR OFFICIAL CRIMINAL RECORDS</span>
        </div>
      </div>

      {creating && <SubjectFormModal onClose={() => setCreating(false)} />}
    </div>
  )
}
