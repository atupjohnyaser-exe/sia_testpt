import { useOutletContext } from 'react-router-dom'
import { ChartCard } from '../../components/charts/ChartCard'
import { QueryState } from '../../components/ui'
import { useAnalytics } from '../../hooks/queries'
import { areaChart, barChart, COLORS, donutChart, hBarChart, lineChart, monthLabel, pyramidChart, ringsChart, SERIES, stackedBarChart } from '../../lib/charts'

const SEVERITY_COLORS = { HIGH: COLORS.red, MEDIUM: COLORS.amber, LOW: COLORS.lime }

/** Eight charts about one record. The server groups the numbers, this page only draws them. */
export function AnalyticsTab() {
  const { subject } = useOutletContext()
  const query = useAnalytics(subject.id)
  const data = query.data?.data

  // The categories shown in the cost chart are every cost type that appears in any manhunt.
  const categories = []

  for (const manhunt of data?.manhunt_costs ?? []) {
    for (const line of manhunt.lines) {
      if (!categories.some((category) => category.key === line.code)) {
        categories.push({ key: line.code, label: line.label, color: SERIES[categories.length % SERIES.length] })
      }
    }
  }

  return (
    <QueryState query={query} rows={4}>
      {data && (
        <div className="chart-grid">
          <ChartCard
            title="INFRACTIONS BY TYPE"
            caption="Total and still unserved"
            node={barChart(data.infractions_by_type.map((type) => ({ label: type.name, value: type.total, extra: type.unserved })), { title: 'Infractions by type' })}
          />
          <ChartCard
            title="SERVED VS UNSERVED"
            caption="How much of the record is settled"
            node={donutChart(
              [
                { label: 'SERVED', value: data.served.served, color: COLORS.lime },
                { label: 'UNSERVED', value: data.served.unserved, color: COLORS.amber },
              ],
              { centerLabel: 'INFRACTIONS', title: 'Served versus unserved infractions' },
            )}
          />
          <ChartCard
            title="SEVERITY PYRAMID"
            caption="Most serious at the top"
            node={pyramidChart(
              data.infractions_by_severity.map((layer) => ({ label: layer.severity, value: layer.total, color: SEVERITY_COLORS[layer.severity] })),
              { title: 'Infractions by severity' },
            )}
          />
          <ChartCard
            title="BOUNTY BUILD-UP"
            caption="Running total by month"
            node={lineChart(data.bounty_timeline.map((point) => ({ label: monthLabel(point.month), value: point.cumulative })), { title: 'Bounty over time' })}
          />
          <ChartCard
            title="COST PER MANHUNT"
            caption="What each one cost the state"
            node={stackedBarChart(
              data.manhunt_costs.map((manhunt) => ({
                label: manhunt.code.replace('MH-', ''),
                segments: manhunt.lines.map((line) => ({ key: line.code, value: line.cost, label: line.label })),
              })),
              categories,
              { title: 'Cost per manhunt' },
            )}
          />
          <ChartCard
            title="SIGHTINGS OVER TIME"
            caption="Reports per month"
            node={areaChart(data.sightings_by_month.map((point) => ({ label: monthLabel(point.month), value: point.count })), { title: 'Sightings over time' })}
          />
          <ChartCard
            title="WHERE THEY WERE SEEN"
            caption="Municipalities with the most sightings"
            node={hBarChart(data.sightings_by_place.map((place) => ({ label: place.name, value: place.count })), { title: 'Sightings by municipality' })}
          />
          <ChartCard
            title="CASE PROGRESS"
            caption="Percent complete"
            node={ringsChart(
              [
                { label: 'INFRACTIONS SERVED', percent: data.progress.infractions_served, color: COLORS.lime },
                { label: 'FINES PAID', percent: data.progress.fines_paid, color: COLORS.amber },
                { label: 'ENDED IN ARREST', percent: data.progress.manhunts_apprehended, color: COLORS.blue },
              ],
              { title: 'Case progress' },
            )}
          />
        </div>
      )}
    </QueryState>
  )
}
