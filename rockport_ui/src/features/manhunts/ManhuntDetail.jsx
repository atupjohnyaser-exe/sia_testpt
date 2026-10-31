import { useCallback } from 'react'
import { Link, useNavigate, useOutletContext, useParams } from 'react-router-dom'
import { EmptyState, Panel, QueryState, StatusTag } from '../../components/ui'
import { useAuth } from '../../context/auth'
import { useToast } from '../../context/toast'
import { useDeleteManhunt } from '../../hooks/mutations'
import { useHotkey } from '../../hooks/useHotkeys'
import { useManhunt } from '../../hooks/queries'
import { errorMessage } from '../../lib/errors'
import { dateText, dateTimeText, num } from '../../lib/format'

function Row({ title, value }) {
  return (
    <div className="kv-row">
      <span>{title}</span>
      <b>{value}</b>
    </div>
  )
}

export function ManhuntDetail() {
  const { subject } = useOutletContext()
  const { manhuntId } = useParams()
  const { can } = useAuth()
  const navigate = useNavigate()
  const toast = useToast()
  const query = useManhunt(manhuntId)
  const remove = useDeleteManhunt(subject.id)
  const manhunt = query.data?.data
  const back = `/database/${subject.id}/manhunts`

  const goBack = useCallback(() => navigate(back), [navigate, back])

  // Esc goes back, like in the game's menus.
  useHotkey('Escape', goBack)

  const removeManhunt = async () => {
    if (!window.confirm(`Remove manhunt ${manhunt.code}? Its cost and bounty come off this record.`)) return

    try {
      await remove.mutateAsync(manhunt.id)
      navigate(back)
    } catch (error) {
      toast(errorMessage(error, 'COULD NOT REMOVE THIS MANHUNT'))
    }
  }

  return (
    <Panel
      eyebrow={`MANHUNT DETAIL // ${subject.case_id}`}
      title={manhunt ? manhunt.code : 'MANHUNT'}
      action={<Link className="button button-outline" to={back}>← BACK (ESC)</Link>}
    >
      <QueryState query={query} rows={5}>
        {manhunt && (
          <>
            <div className="manhunt-head">
              <span><small>DISTRICT</small><b>{manhunt.district}</b></span>
              <span><small>STARTED</small><b>{dateTimeText(manhunt.started_at)}</b></span>
              <span><small>OUTCOME</small><StatusTag status={manhunt.outcome} /></span>
            </div>

            <div className="kv-list">
              <Row title="MANHUNT LENGTH" value={manhunt.duration_label} />
              <Row title="OFFICERS DEPLOYED" value={num(manhunt.officers_involved)} />
              <Row title="OFFICERS INJURED" value={num(manhunt.officers_injured)} />
              <Row title="UNITS STOOD DOWN" value={num(manhunt.units_stood_down)} />
              <Row title="CHECKPOINTS DEPLOYED" value={num(manhunt.checkpoints)} />
              <Row title="K-9 UNITS DEPLOYED" value={num(manhunt.k9_units)} />
              <Row title="HELICOPTERS DEPLOYED" value={num(manhunt.helicopters)} />
              <Row title="PROPERTY DAMAGE INCIDENTS" value={num(manhunt.property_damage_incidents)} />
              <Row title="INSURANCE CLAIMS" value={num(manhunt.insurance_claims)} />
              <Row title="INFRACTIONS RECORDED" value={num(manhunt.infractions_count)} />
              <Row title="COST TO STATE" value={num(manhunt.cost_to_state)} />
              <Row title="MANHUNT BOUNTY" value={num(manhunt.bounty_value)} />
            </div>

            {manhunt.cost_breakdown.length > 0 && (
              <div className="table-scroll">
                <table className="data-table">
                  <thead><tr><th className="num">QTY</th><th>COST CATEGORY</th><th className="num">PRICE EACH</th><th className="num">COST</th></tr></thead>
                  <tbody>
                    {manhunt.cost_breakdown.map((line) => (
                      <tr key={line.code}>
                        <td className="num">{num(line.qty)}</td>
                        <td><b>{line.label}</b></td>
                        <td className="num">{num(line.unit_cost)}</td>
                        <td className="num">{num(line.cost)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}

            {manhunt.infractions?.length > 0 ? (
              <div className="table-scroll">
                <table className="data-table">
                  <thead><tr><th>INFRACTIONS DURING THIS MANHUNT</th><th>DATE</th><th className="num">BOUNTY</th></tr></thead>
                  <tbody>
                    {manhunt.infractions.map((infraction) => (
                      <tr key={infraction.id}>
                        <td><b>{infraction.type?.name}</b></td>
                        <td>{dateText(infraction.occurred_on)}</td>
                        <td className="num">{num(infraction.bounty_value)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <EmptyState title="NO INFRACTIONS LINKED TO THIS MANHUNT" />
            )}

            {manhunt.notes && <div className="advisory manhunt-notes"><small>NOTES</small><p>{manhunt.notes}</p></div>}

            {can(4) && (
              <div className="detail-footer">
                <button className="button-danger" onClick={removeManhunt}>REMOVE MANHUNT</button>
              </div>
            )}
          </>
        )}
      </QueryState>
    </Panel>
  )
}
