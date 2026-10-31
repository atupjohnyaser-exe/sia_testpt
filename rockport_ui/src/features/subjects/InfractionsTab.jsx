import { useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { EmptyState, Pagination, Panel, QueryState, StatusTag } from '../../components/ui'
import { useAuth } from '../../context/auth'
import { useToast } from '../../context/toast'
import { useDeleteInfraction, useUpdateInfraction } from '../../hooks/mutations'
import { useInfractionSummary, useSubjectInfractions } from '../../hooks/queries'
import { errorMessage } from '../../lib/errors'
import { dateText, num } from '../../lib/format'
import { AddInfractionModal } from './AddInfractionModal'

export function InfractionsTab() {
  const { subject } = useOutletContext()
  const { can } = useAuth()
  const toast = useToast()
  const [page, setPage] = useState(1)
  const [served, setServed] = useState('')
  const [adding, setAdding] = useState(false)

  const params = { page, per_page: 15 }
  if (served) params.served = served

  const summary = useInfractionSummary(subject.id)
  const list = useSubjectInfractions(subject.id, params)
  const update = useUpdateInfraction(subject.id)
  const remove = useDeleteInfraction(subject.id)

  const change = async (infraction, body) => {
    try {
      await update.mutateAsync({ id: infraction.id, body })
    } catch (error) {
      toast(errorMessage(error, 'COULD NOT UPDATE THIS INFRACTION'))
    }
  }

  const removeInfraction = async (infraction) => {
    if (!window.confirm('Remove this infraction from the record?')) return

    try {
      await remove.mutateAsync(infraction.id)
    } catch (error) {
      toast(errorMessage(error, 'COULD NOT REMOVE THIS INFRACTION'))
    }
  }

  return (
    <>
      <Panel
        eyebrow="BY TYPE"
        title="INFRACTIONS"
        action={can(2) && <button className="button button-outline" onClick={() => setAdding(true)}>+ RECORD INFRACTION</button>}
      >
        <QueryState query={summary}>
          <div className="table-scroll">
            <table className="data-table">
              <thead><tr><th>INFRACTION</th><th>SEVERITY</th><th className="num">UNSERVED</th><th className="num">TOTAL</th></tr></thead>
              <tbody>
                {summary.data?.data.types.map((type) => (
                  <tr key={type.code}>
                    <td><b>{type.name}</b></td>
                    <td><StatusTag status={type.severity} /></td>
                    <td className="num">{num(type.unserved)}</td>
                    <td className="num">{num(type.total)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {summary.data?.data.types.length === 0 && <EmptyState title="NO INFRACTIONS ON FILE" />}
        </QueryState>
      </Panel>

      <Panel
        eyebrow="FULL HISTORY"
        title="ALL INFRACTIONS"
        action={
          <label className="filter-select">
            <small>SHOW</small>
            <select value={served} onChange={(event) => { setServed(event.target.value); setPage(1) }}>
              <option value="">ALL</option>
              <option value="false">UNSERVED</option>
              <option value="true">SERVED</option>
            </select>
          </label>
        }
      >
        <QueryState query={list}>
          <div className="table-scroll">
            <table className="data-table">
              <thead>
                <tr><th>DATE</th><th>INFRACTION</th><th>LOCATION</th><th className="num">BOUNTY</th><th className="num">FINE</th><th>STATE</th>{can(3) && <th>ACTIONS</th>}</tr>
              </thead>
              <tbody>
                {list.data?.data.map((infraction) => (
                  <tr key={infraction.id}>
                    <td>{dateText(infraction.occurred_on)}</td>
                    <td><b>{infraction.type?.name}</b>{infraction.manhunt_code && <span className="subline">DURING {infraction.manhunt_code}</span>}</td>
                    <td>{infraction.location}{infraction.disposition && <span className="subline">{infraction.disposition}</span>}</td>
                    <td className="num">{num(infraction.bounty_value)}</td>
                    <td className="num">{num(infraction.fine_amount)}{infraction.fine_paid && <span className="subline">PAID</span>}</td>
                    <td><span className={`status-tag status-${infraction.served ? 'cleared' : 'wanted'}`}>{infraction.served ? 'SERVED' : 'UNSERVED'}</span></td>
                    {can(3) && (
                      <td>
                        <div className="row-actions">
                          <button className="mini-button" disabled={update.isPending} onClick={() => change(infraction, { served: !infraction.served })}>{infraction.served ? 'UNSERVE' : 'MARK SERVED'}</button>
                          <button className="mini-button" disabled={update.isPending} onClick={() => change(infraction, { fine_paid: !infraction.fine_paid })}>{infraction.fine_paid ? 'UNPAY FINE' : 'FINE PAID'}</button>
                          {can(4) && <button className="mini-button danger" onClick={() => removeInfraction(infraction)}>REMOVE</button>}
                        </div>
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {list.data?.data.length === 0 && <EmptyState title="NOTHING MATCHES THIS FILTER" />}
          <Pagination meta={list.data?.meta} onPage={setPage} />
        </QueryState>
      </Panel>

      {adding && <AddInfractionModal subject={subject} onClose={() => setAdding(false)} />}
    </>
  )
}
