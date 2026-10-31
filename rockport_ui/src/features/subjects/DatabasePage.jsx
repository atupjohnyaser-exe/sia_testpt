import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { Link, Outlet, useLocation, useMatch, useNavigate } from 'react-router-dom'
import { endpoints } from '../../api/endpoints'
import { Pagination, StatusTag } from '../../components/ui'
import { ThreatPips } from '../../components/ThreatMeter'
import { useAuth } from '../../context/auth'
import { useToast } from '../../context/toast'
import { useFocusShortcut, useListHotkeys } from '../../hooks/useHotkeys'
import { useSubjects } from '../../hooks/queries'
import { num } from '../../lib/format'
import { SubjectFormModal } from './SubjectFormModal'

const TABS = ['infractions', 'manhunts', 'locations', 'cost', 'notes', 'history']

export function NoRecordSelected() {
  return (
    <div className="database-empty">
      <div className="database-mark">DB</div>
      <small>NO RECORD SELECTED</small>
      <h1>SELECT A<br />SUBJECT FILE</h1>
      <p>Choose a person from the list to open their rap sheet. Use the arrow keys to move through the list.</p>
    </div>
  )
}

export function DatabasePage() {
  const navigate = useNavigate()
  const location = useLocation()
  const match = useMatch('/database/:id/*')
  const activeId = match?.params.id ? Number(match.params.id) : null
  const { can } = useAuth()
  const toast = useToast()
  const searchRef = useRef(null)

  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [filters, setFilters] = useState({ status: '', threat_level: '', sort: 'bounty' })
  const [page, setPage] = useState(1)
  const [creating, setCreating] = useState(false)
  const [exporting, setExporting] = useState(false)

  // Wait for a pause in typing before asking the server.
  useEffect(() => {
    const timer = setTimeout(() => {
      setSearch(searchInput.trim())
      setPage(1)
    }, 300)

    return () => clearTimeout(timer)
  }, [searchInput])

  const params = { page, per_page: 25, sort: filters.sort }
  if (search) params.search = search
  if (filters.status) params.status = filters.status
  if (filters.threat_level) params.threat_level = filters.threat_level

  const subjects = useSubjects(params)
  const rows = subjects.data?.data
  const meta = subjects.data?.meta
  const ids = useMemo(() => (rows ?? []).map((subject) => subject.id), [rows])

  // Keep the tab you are on when you switch to another person.
  const pathFor = useCallback(
    (id) => {
      const tab = location.pathname.split('/')[3]

      return `/database/${id}${TABS.includes(tab) ? `/${tab}` : ''}`
    },
    [location.pathname],
  )
  const goTo = useCallback((id) => navigate(pathFor(id)), [navigate, pathFor])

  useListHotkeys(ids, activeId, goTo)
  useFocusShortcut(searchRef)

  const setFilter = (key) => (event) => {
    setFilters((current) => ({ ...current, [key]: event.target.value }))
    setPage(1)
  }

  const exportJson = async () => {
    setExporting(true)

    try {
      const blob = await endpoints.exportSubjects()
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')

      link.href = url
      link.download = 'rockport-subjects.json'
      link.click()
      URL.revokeObjectURL(url)
      toast('EXPORT COMPLETE // LOGGED IN AUDIT LOG')
    } catch {
      toast('EXPORT FAILED')
    } finally {
      setExporting(false)
    }
  }

  return (
    <div className="database-layout">
      <aside className="record-sidebar">
        <div className="sidebar-heading">
          <small className="eyebrow">SUBJECT RECORDS</small>
          <h2>DATABASE <b>{meta ? num(meta.total) : '—'}</b></h2>
        </div>

        <label className="search-box">
          <b>⌕</b>
          <input ref={searchRef} value={searchInput} onChange={(event) => setSearchInput(event.target.value)} placeholder="NAME, ALIAS OR CASE ID" aria-label="Search records" />
          <kbd>⌘K</kbd>
        </label>

        <div className="list-filters">
          <select value={filters.status} onChange={setFilter('status')} aria-label="Filter by status">
            <option value="">ALL STATUSES</option>
            <option value="WANTED">WANTED</option>
            <option value="IN_CUSTODY">IN CUSTODY</option>
            <option value="PAROLED">PAROLED</option>
            <option value="CLEARED">CLEARED</option>
          </select>
          <select value={filters.threat_level} onChange={setFilter('threat_level')} aria-label="Filter by threat level">
            <option value="">ANY THREAT</option>
            {[1, 2, 3, 4, 5, 6].map((level) => <option key={level} value={level}>THREAT {level}</option>)}
          </select>
          <select value={filters.sort} onChange={setFilter('sort')} aria-label="Sort order">
            <option value="bounty">BOUNTY</option>
            <option value="threat">THREAT</option>
            <option value="name">NAME</option>
            <option value="recent">NEWEST</option>
          </select>
        </div>

        <div className="subject-list">
          {subjects.isLoading && <div className="table-loading">SEARCHING DATABASE -/</div>}
          {subjects.isError && <div className="table-loading">COULD NOT LOAD RECORDS.</div>}
          {rows?.length === 0 && <div className="table-loading">NO MATCHING RECORDS.</div>}
          {rows?.map((subject, index) => (
            <Link key={subject.id} to={pathFor(subject.id)} className={`subject-list-row ${subject.id === activeId ? 'selected' : ''}`}>
              <small>{String((page - 1) * 25 + index + 1).padStart(2, '0')}</small>
              <div>
                <b>{subject.name}</b>
                <span>{subject.case_id} // BOUNTY {num(subject.bounty)}</span>
              </div>
              <span className="list-side">
                <StatusTag status={subject.status} />
                <ThreatPips level={subject.threat.level} />
              </span>
              <span className="chevron">›</span>
            </Link>
          ))}
        </div>

        <Pagination meta={meta} onPage={setPage} />

        {can(3) && <button className="button create-record" onClick={() => setCreating(true)}>+ CREATE RECORD</button>}
        {can(4) && <button className="export-button" onClick={exportJson} disabled={exporting}>{exporting ? 'EXPORTING…' : 'EXPORT ALL AS JSON (AUDITED)'}</button>}
      </aside>

      <section className="record-detail"><Outlet /></section>

      {creating && <SubjectFormModal onClose={() => setCreating(false)} />}
    </div>
  )
}
