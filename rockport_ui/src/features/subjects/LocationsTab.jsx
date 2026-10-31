import { useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { EmptyState, Panel, QueryState } from '../../components/ui'
import { useAuth } from '../../context/auth'
import { useLocations } from '../../hooks/queries'
import { dateTimeText, num } from '../../lib/format'
import { SightingModal } from './SightingModal'

export function LocationsTab() {
  const { subject } = useOutletContext()
  const { can } = useAuth()
  const [adding, setAdding] = useState(false)
  const query = useLocations(subject.id)
  const data = query.data?.data

  return (
    <>
      <Panel
        eyebrow="KNOWN LOCATIONS"
        title="DISTRICTS"
        action={can(2) && <button className="button button-outline" onClick={() => setAdding(true)}>+ LOG SIGHTING</button>}
      >
        <QueryState query={query}>
          <div className="location-banner">
            <small>LAST KNOWN LOCATION</small>
            <b>{data?.last_known_location || 'UNCONFIRMED'}</b>
          </div>
          {data?.districts.length ? (
            <div className="table-scroll">
              <table className="data-table">
                <thead><tr><th>DISTRICT</th><th></th><th className="num">SIGHTINGS</th><th>LAST SEEN</th></tr></thead>
                <tbody>
                  {data.districts.map((district, index) => (
                    <tr key={district.district}>
                      <td><b>{district.district}</b></td>
                      <td>{index === 0 && <span className="status-tag status-paroled">MOST RECENT</span>}</td>
                      <td className="num">{num(district.sightings)}</td>
                      <td>{dateTimeText(district.last_seen)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <EmptyState title="NO SIGHTINGS LOGGED" message="Log a sighting to start tracking where this person has been seen." />
          )}
        </QueryState>
      </Panel>

      {data?.recent.length > 0 && (
        <Panel eyebrow="LATEST REPORTS" title="RECENT SIGHTINGS">
          <div className="table-scroll">
            <table className="data-table">
              <thead><tr><th>SEEN</th><th>DISTRICT</th><th>ADDRESS</th><th>SOURCE</th></tr></thead>
              <tbody>
                {data.recent.map((sighting) => (
                  <tr key={sighting.id}>
                    <td>{dateTimeText(sighting.seen_at)}</td>
                    <td><b>{sighting.district}</b></td>
                    <td>{sighting.address || '—'}</td>
                    <td>{(sighting.source || '—').toUpperCase()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Panel>
      )}

      {adding && <SightingModal subject={subject} onClose={() => setAdding(false)} />}
    </>
  )
}
