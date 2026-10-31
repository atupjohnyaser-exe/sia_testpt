import { useOutletContext } from 'react-router-dom'
import { EmptyState, Panel, QueryState } from '../../components/ui'
import { useCostToState } from '../../hooks/queries'
import { num } from '../../lib/format'

export function CostTab() {
  const { subject } = useOutletContext()
  const query = useCostToState(subject.id)
  const data = query.data?.data

  return (
    <Panel eyebrow="WHAT THIS PERSON HAS COST THE CITY" title="COST TO STATE">
      <QueryState query={query}>
        {data?.lines.length ? (
          <div className="table-scroll">
            <table className="data-table">
              <thead><tr><th className="num">QTY</th><th>CATEGORY</th><th className="num">COST</th></tr></thead>
              <tbody>
                {data.lines.map((line) => (
                  <tr key={line.code}>
                    <td className="num">{num(line.qty)}</td>
                    <td><b>{line.label}</b></td>
                    <td className="num">{num(line.cost)}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr><td></td><td><b>COST TO STATE</b></td><td className="num total">{num(data.total)}</td></tr>
                <tr><td></td><td><b>BOUNTY</b></td><td className="num total">{num(data.bounty)}</td></tr>
              </tfoot>
            </table>
          </div>
        ) : (
          <EmptyState title="NO COSTS RECORDED" message="Cost builds up as manhunts are logged against this file." />
        )}
      </QueryState>
    </Panel>
  )
}
