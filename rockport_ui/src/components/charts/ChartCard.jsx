import { SvgTree } from './SvgTree'

/** One chart in a titled card. An empty chart (null) shows a quiet "no data" box instead. */
export function ChartCard({ title, caption, node, wide = false }) {
  return (
    <section className={`chart-card ${wide ? 'wide' : ''}`}>
      <header>
        <h3>{title}</h3>
        {caption && <small>{caption}</small>}
      </header>
      {node ? (
        <div className="chart-body"><SvgTree node={node} /></div>
      ) : (
        <div className="chart-empty">NO DATA YET</div>
      )}
    </section>
  )
}
