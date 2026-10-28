import { useMemo, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useBulletin, usePrioritySubjects, useStats } from '../hooks/api'
import { ErrorState, LoadingRows, Panel, RecordModal, RiskBar, StatusTag, StatCell } from '../components/UI'
import { useCreateSubject } from '../hooks/api'
import { useAuth } from '../context/AuthContext'

function greeting() {
  const hour = new Date().getHours()
  return hour < 12 ? 'GOOD MORNING' : hour < 18 ? 'GOOD AFTERNOON' : 'GOOD EVENING'
}

export function CommandPage() {
  const navigate = useNavigate()
  const { officer } = useAuth()
  const stats = useStats()
  const priority = usePrioritySubjects()
  const bulletin = useBulletin()
  const [creating, setCreating] = useState(false)
  const create = useCreateSubject()
  const values = useMemo(() => stats.data || {}, [stats.data])
  return <>
    <section className="command-hero">
      <div><small className="eyebrow">SECURE SESSION // TERMINAL 04</small><h1>{greeting()},<br />{`DETECTIVE ${(officer?.name || 'A. CROSS').replace(/^Det\.\s*/i, '')}`.toUpperCase()}.</h1><p>Intelligence summary is current as of {new Date().toLocaleString([], { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}. Review priority subjects and outstanding records below.</p></div>
      <Link to="/database" className="button">OPEN RECORDS DATABASE <span>→</span></Link>
    </section>
    {stats.isError ? <ErrorState onRetry={() => stats.refetch()} /> : <section className="stats-grid">
      <StatCell label="ACTIVE RECORDS" caption={`+${values.new_this_week || 0} THIS WEEK`} value={values.active_records} />
      <StatCell label="OUTSTANDING WARRANTS" caption="REQUIRES REVIEW" value={values.outstanding_warrants} />
      <StatCell label="RECORDED OFFENSES" caption="ACROSS ALL FILES" value={values.recorded_offenses} />
      <StatCell label="CRITICAL RISK" caption="ENHANCED RESPONSE" tone="red" value={values.critical_risk} />
    </section>}
    <div className="dashboard-grid">
      <Panel eyebrow="OPERATIONAL INTELLIGENCE" title="PRIORITY SUBJECTS" action={<Link className="text-link" to="/database">VIEW ALL →</Link>} className="priority-panel">
        {priority.isLoading ? <LoadingRows count={3} /> : priority.isError ? <ErrorState onRetry={() => priority.refetch()} /> : priority.data?.data?.length ? <div className="priority-list">{priority.data.data.map((subject, index) => <button className="priority-row" key={subject.id} onClick={() => navigate(`/database/${subject.id}`)}>
          <small className="row-index">{String(index + 1).padStart(2, '0')}</small><span className="subject-mark">{subject.name?.split(' ').map((part) => part[0]).join('').slice(0, 2)}</span><span className="subject-name"><b>{subject.name}</b><small>AKA: {subject.alias || 'UNKNOWN'} // {subject.case_id}</small></span><span className="last-known"><small>LAST KNOWN</small><b>{subject.last_known_location || 'UNCONFIRMED'}</b></span><span className="risk-label"><small>RISK</small><b className={subject.risk_score >= 80 ? 'risk-high' : ''}>{subject.risk_score}</b></span><span className="row-arrow">→</span>
        </button>)}</div> : <div className="empty-state">NO PRIORITY SUBJECTS FOUND</div>}
      </Panel>
      <aside className="side-panels">
        <section className="bulletin"><div className="bulletin-top"><span>●</span><b>SYSTEM BULLETIN</b><small>09:32</small></div><p>{bulletin.data?.bulletin?.body || 'No active bulletins. Check the records feed for the latest operational notes.'}</p><small>BRIEFING REF // {bulletin.data?.bulletin?.ref || 'OPS—NONE'}</small></section>
        <Panel eyebrow="SHORTCUTS" title="QUICK ACCESS" className="shortcut-panel">
          <Link className="shortcut" to="/database"><span>01</span><div><b>SUBJECT SEARCH</b><small>Search all active records</small></div><b>→</b></Link>
          <Link className="shortcut" to="/offenses"><span>02</span><div><b>OFFENSE INDEX</b><small>Browse charges and history</small></div><b>→</b></Link>
          <Link className="shortcut" to="/activity"><span>03</span><div><b>AUDIT LOG</b><small>Review recent access activity</small></div><b>→</b></Link>
          <button className="shortcut" onClick={() => setCreating(true)}><span>04</span><div><b>NEW SUBJECT RECORD</b><small>Register a new case file</small></div><b>→</b></button>
        </Panel>
      </aside>
    </div>
    <div className="training-strip"><span>FICTIONAL TRAINING DATA</span><span>DEMO RECORDS ARE NOT REAL PEOPLE OR OFFICIAL CRIMINAL RECORDS</span></div>
    {creating && <RecordModal onClose={() => setCreating(false)} onSave={(body) => create.mutateAsync(body)} busy={create.isPending} />}
  </>
}
