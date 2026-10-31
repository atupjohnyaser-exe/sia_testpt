import { Link, useOutletContext } from 'react-router-dom'
import { Panel, QueryState } from '../../components/ui'
import { ThreatMeter } from '../../components/ThreatMeter'
import { useAuth } from '../../context/auth'
import { useRapSheet } from '../../hooks/queries'
import { dateText, num } from '../../lib/format'

function Figure({ title, value, tone = '' }) {
  return (
    <div className="rap-item">
      <small>{title}</small>
      <b className={tone}>{value}</b>
    </div>
  )
}

export function OverviewTab() {
  const { subject, openThreatOverride } = useOutletContext()
  const { can } = useAuth()
  const rap = useRapSheet(subject.id)
  const sheet = rap.data?.data

  return (
    <div className="overview-grid">
      <Panel eyebrow="RAP SHEET" title="SUMMARY">
        <QueryState query={rap}>
          {sheet && (
            <div className="rap-grid">
              <Figure title="BOUNTY" value={num(sheet.bounty)} tone="lime" />
              <Figure title="COST TO STATE" value={num(sheet.cost_to_state)} />
              <Figure title="FINES DUE" value={num(sheet.fines.due)} />
              <Figure title="FINES PAID" value={num(sheet.fines.paid)} />
              <Figure title="UNSERVED INFRACTIONS" value={num(sheet.infractions.unserved)} tone={sheet.infractions.unserved > 0 ? 'red' : ''} />
              <Figure title="SERVED INFRACTIONS" value={num(sheet.infractions.served)} />
              <Figure title="TIMES EVADED" value={num(sheet.manhunts.times_evaded)} />
              <Figure title="TIMES APPREHENDED" value={num(sheet.manhunts.times_apprehended)} />
              <Figure title="OFFICERS INJURED" value={num(sheet.police.officers_injured)} />
              <Figure title="UNITS STOOD DOWN" value={num(sheet.police.units_stood_down)} />
            </div>
          )}
        </QueryState>
      </Panel>

      <Panel eyebrow="THREAT ASSESSMENT" title="HEAT LEVEL" className="threat-panel">
        <ThreatMeter threat={subject.threat} />
        {can(4) && <button className="button button-outline override-button" onClick={openThreatOverride}>OVERRIDE LEVEL</button>}
      </Panel>

      <section className="advisory">
        <small>OFFICER ADVISORY</small>
        <p>{subject.officer_advisory || 'No advisory on file.'}</p>
        {subject.advisory_reviewed_at && (
          <span>REVIEWED // {dateText(subject.advisory_reviewed_at)} // {subject.advisory_reviewed_by || 'UNKNOWN OFFICER'}</span>
        )}
      </section>

      <Panel eyebrow="DESCRIPTION" title="CHARACTERISTICS">
        <div className="character-grid">
          <div><small>HEIGHT</small><b>{subject.height || '—'}</b></div>
          <div><small>EYE COLOR</small><b>{subject.eye_color || '—'}</b></div>
          <div><small>HAIR COLOR</small><b>{subject.hair_color || '—'}</b></div>
          <div><small>DISTINGUISHING FEATURES</small><b>{subject.distinguishing_features || 'None recorded'}</b></div>
        </div>
      </Panel>

      <Panel
        eyebrow="LATEST ACTIVITY"
        title="RECENT INFRACTIONS"
        className="recent-panel"
        action={<Link className="text-link" to={`/database/${subject.id}/infractions`}>VIEW ALL →</Link>}
      >
        {subject.recent_infractions?.length ? (
          subject.recent_infractions.map((infraction) => (
            <div className="offense-row" key={infraction.id}>
              <span>{dateText(infraction.occurred_on)}</span>
              <span className={`status-tag status-${infraction.served ? 'cleared' : 'wanted'}`}>{infraction.served ? 'SERVED' : 'UNSERVED'}</span>
              <div>
                <b>{infraction.type?.name}</b>
                <small>{infraction.location}</small>
              </div>
            </div>
          ))
        ) : (
          <div className="empty-state"><b>NO INFRACTIONS ON FILE</b></div>
        )}
      </Panel>
    </div>
  )
}
