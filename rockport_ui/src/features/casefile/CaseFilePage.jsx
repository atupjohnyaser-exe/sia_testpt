import { Link, useParams } from 'react-router-dom'
import { ErrorState, LoadingRows } from '../../components/ui'
import { useCaseFile } from '../../hooks/queries'
import { dateText, dateTimeText, num } from '../../lib/format'

/**
 * A print-friendly case file. Use the button, then choose "Save as PDF" in the print window.
 * Opening this page asks the server to generate the file, and the server writes that to the audit log.
 */
export function CaseFilePage() {
  const { id } = useParams()
  const query = useCaseFile(id)

  if (query.isLoading) return <LoadingRows count={6} />
  if (query.isError) return <ErrorState onRetry={() => query.refetch()} message="The case file could not be generated." />

  const file = query.data.data
  const subject = file.subject

  return (
    <article className="case-file">
      <div className="case-file-bar no-print">
        <Link className="button button-outline" to={`/database/${subject.id}`}>← BACK TO RECORD</Link>
        <button className="button" onClick={() => window.print()}>PRINT / SAVE AS PDF ⎙</button>
      </div>

      <header className="cf-header">
        <div>
          <small>ROCKPORT POLICE DEPARTMENT // RECORDS DIVISION</small>
          <h1>CASE FILE {subject.case_id}</h1>
        </div>
        <div className="cf-meta">
          <span>GENERATED {dateTimeText(file.generated_at)}</span>
          <span>BY {file.generated_by.name} ({file.generated_by.badge_id})</span>
        </div>
      </header>

      <section className="cf-section">
        <h2>SUBJECT</h2>
        <div className="cf-grid">
          <div><small>NAME</small><b>{subject.name}</b></div>
          <div><small>ALIAS</small><b>{subject.alias || '—'}</b></div>
          <div><small>AGE</small><b>{subject.age ?? '—'}</b></div>
          <div><small>STATUS</small><b>{subject.status.replaceAll('_', ' ')}</b></div>
          <div><small>THREAT LEVEL</small><b>{subject.threat.level} // {subject.threat.label.toUpperCase()}</b></div>
          <div><small>LAST KNOWN LOCATION</small><b>{subject.last_known_location || '—'}</b></div>
          <div><small>HEIGHT / EYES / HAIR</small><b>{[subject.height, subject.eye_color, subject.hair_color].filter(Boolean).join(' / ') || '—'}</b></div>
          <div><small>DISTINGUISHING FEATURES</small><b>{subject.distinguishing_features || '—'}</b></div>
        </div>
        <p className="cf-response"><small>SUGGESTED RESPONSE</small>{subject.threat.response}</p>
        {subject.officer_advisory && <p className="cf-response"><small>OFFICER ADVISORY</small>{subject.officer_advisory}</p>}
      </section>

      <section className="cf-section">
        <h2>SUMMARY</h2>
        <div className="cf-grid">
          <div><small>BOUNTY</small><b>{num(subject.bounty)}</b></div>
          <div><small>COST TO STATE</small><b>{num(subject.cost_to_state)}</b></div>
          <div><small>FINES DUE / PAID</small><b>{num(subject.fines_due)} / {num(subject.fines_paid)}</b></div>
          <div><small>UNSERVED INFRACTIONS</small><b>{num(subject.unserved_infractions)} OF {num(subject.total_infractions)}</b></div>
          <div><small>TIMES EVADED</small><b>{num(subject.times_evaded)}</b></div>
          <div><small>TIMES APPREHENDED</small><b>{num(subject.times_apprehended)}</b></div>
        </div>
      </section>

      <section className="cf-section">
        <h2>INFRACTIONS ({file.infractions.length})</h2>
        <table className="data-table">
          <thead><tr><th>DATE</th><th>INFRACTION</th><th>LOCATION</th><th className="num">BOUNTY</th><th className="num">FINE</th><th>STATE</th></tr></thead>
          <tbody>
            {file.infractions.map((infraction) => (
              <tr key={infraction.id}>
                <td>{dateText(infraction.occurred_on)}</td>
                <td>{infraction.type?.name}</td>
                <td>{infraction.location}</td>
                <td className="num">{num(infraction.bounty_value)}</td>
                <td className="num">{num(infraction.fine_amount)}</td>
                <td>{infraction.served ? 'SERVED' : 'UNSERVED'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>

      <section className="cf-section">
        <h2>MANHUNTS ({file.manhunts.length})</h2>
        <table className="data-table">
          <thead><tr><th>CODE</th><th>DATE</th><th>DISTRICT</th><th>LENGTH</th><th className="num">UNITS</th><th className="num">COST</th><th className="num">BOUNTY</th><th>OUTCOME</th></tr></thead>
          <tbody>
            {file.manhunts.map((manhunt) => (
              <tr key={manhunt.id}>
                <td>{manhunt.code}</td>
                <td>{dateText(manhunt.started_at)}</td>
                <td>{manhunt.district}</td>
                <td>{manhunt.duration_label}</td>
                <td className="num">{num(manhunt.officers_involved)}</td>
                <td className="num">{num(manhunt.cost_to_state)}</td>
                <td className="num">{num(manhunt.bounty_value)}</td>
                <td>{manhunt.outcome}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>

      <section className="cf-section">
        <h2>COST TO STATE</h2>
        <table className="data-table">
          <thead><tr><th className="num">QTY</th><th>CATEGORY</th><th className="num">COST</th></tr></thead>
          <tbody>
            {file.cost_to_state.lines.map((line) => (
              <tr key={line.code}><td className="num">{num(line.qty)}</td><td>{line.label}</td><td className="num">{num(line.cost)}</td></tr>
            ))}
          </tbody>
          <tfoot><tr><td></td><td><b>TOTAL</b></td><td className="num total">{num(file.cost_to_state.total)}</td></tr></tfoot>
        </table>
      </section>

      <section className="cf-section">
        <h2>KNOWN LOCATIONS</h2>
        <table className="data-table">
          <thead><tr><th>DISTRICT</th><th className="num">SIGHTINGS</th><th>LAST SEEN</th></tr></thead>
          <tbody>
            {file.locations.map((district) => (
              <tr key={district.district}><td>{district.district}</td><td className="num">{num(district.sightings)}</td><td>{dateTimeText(district.last_seen)}</td></tr>
            ))}
          </tbody>
        </table>
      </section>

      <section className="cf-section">
        <h2>STATUS HISTORY</h2>
        <table className="data-table">
          <thead><tr><th>WHEN</th><th>CHANGE</th><th>REASON</th><th>BY</th></tr></thead>
          <tbody>
            {file.status_history.map((change) => (
              <tr key={change.id}>
                <td>{dateTimeText(change.created_at)}</td>
                <td>{change.from ? change.from.replaceAll('_', ' ') : 'NEW'} → {change.to.replaceAll('_', ' ')}</td>
                <td>{change.reason}</td>
                <td>{change.changed_by?.name ?? 'SYSTEM'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>

      <footer className="cf-footer">
        FICTIONAL TRAINING DATA // THIS FILE WAS GENERATED AND LOGGED BY THE RPD RECORDS NETWORK // AUTHORIZED USE ONLY
      </footer>
    </article>
  )
}
