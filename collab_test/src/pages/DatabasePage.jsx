import { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { useAddNote, useAddOffense, useCreateSubject, useCrimeCategories, useDeleteSubject, useNotes, useOffenses, useSubject, useSubjects, useUpdateSubject } from '../hooks/api'
import { EmptyState, ErrorState, LoadingRows, Modal, Panel, RecordModal, RiskBar, StatusTag, useKeyboardFocus } from '../components/UI'
import { useToast } from '../context/ToastContext'
import { endpoints } from '../api/client'

const tabs = ['overview', 'offenses', 'notes']
const formatDate = (value) => value ? new Date(value).toLocaleDateString('en-CA') : '—'

function RecordList({ activeId, onCreate }) {
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')
  const [cursor, setCursor] = useState(-1)
  const input = useRef(null)
  const navigate = useNavigate()
  const [exporting, setExporting] = useState(false)
  useKeyboardFocus(input)
  useEffect(() => { const timer = setTimeout(() => setQuery(search), 300); return () => clearTimeout(timer) }, [search])
  const records = useSubjects({ search: query, sort: 'risk', per_page: 50 })
  const list = records.data?.data || []
  useEffect(() => {
    const handler = (event) => {
      if (event.target === input.current || !['ArrowDown', 'ArrowUp'].includes(event.key)) return
      event.preventDefault(); setCursor((i) => Math.max(0, Math.min(list.length - 1, i + (event.key === 'ArrowDown' ? 1 : -1))))
      const next = Math.max(0, Math.min(list.length - 1, cursor + (event.key === 'ArrowDown' ? 1 : -1)))
      if (list[next]) navigate(`/database/${list[next].id}`)
    }
    window.addEventListener('keydown', handler); return () => window.removeEventListener('keydown', handler)
  }, [cursor, list, navigate])
  return <aside className="record-sidebar">
    <div className="sidebar-heading"><small className="eyebrow">DATABASE INDEX</small><h2>SUBJECT RECORDS <b>{String(records.data?.meta?.total ?? '—').padStart(2, '0')}</b></h2></div>
    <label className="search-box"><b>SEARCH</b><input ref={input} value={search} onChange={(e) => { setSearch(e.target.value); setCursor(-1) }} placeholder="NAME / ALIAS / CASE ID" /><kbd>⌘ K</kbd></label>
    {records.isLoading ? <LoadingRows /> : records.isError ? <ErrorState onRetry={() => records.refetch()} /> : list.length ? <div className="subject-list">{list.map((subject, index) => <Link key={subject.id} to={`/database/${subject.id}`} className={`subject-list-row ${String(activeId) === String(subject.id) ? 'selected' : ''}`}>
      <small>{String(index + 1).padStart(2, '0')}</small><div><b>{subject.name}</b><span>AKA: {subject.alias || 'UNKNOWN'}</span></div><StatusTag status={subject.status} /><span className="chevron">›</span>
    </Link>)}</div> : <EmptyState title="NO MATCHING RECORDS" />}
    <button className="button create-record" onClick={onCreate}>＋ &nbsp; CREATE NEW RECORD</button>
    <button className="export-button" disabled={exporting} onClick={async () => { setExporting(true); try { const payload = await endpoints.exportSubjects(); const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' }); const url = URL.createObjectURL(blob); const anchor = document.createElement('a'); anchor.href = url; anchor.download = 'rpd-subject-records.json'; anchor.click(); URL.revokeObjectURL(url) } finally { setExporting(false) } }}>{exporting ? 'PREPARING EXPORT…' : '↓ EXPORT JSON'}</button>
    <div className="clearance-note"><span>SECURITY LEVEL 03</span><b>FICTIONAL TRAINING DATA</b></div>
  </aside>
}

function OffenseForm({ subjectId, onClose }) {
  const mutation = useAddOffense(subjectId)
  const categoryQuery = useCrimeCategories()
  const categories = categoryQuery.data?.data || []
  const [form, setForm] = useState({ occurred_on: '', category: '', location: '', disposition: '' })
  const [errors, setErrors] = useState({})
  const submit = async (event) => { event.preventDefault(); setErrors({}); try { await mutation.mutateAsync(form); onClose() } catch (error) { setErrors(error.response?.data?.errors || { form: ['Unable to add offense.'] }) } }
  return <Modal eyebrow="CASE FILE UPDATE" title="ADD OFFENSE" onClose={onClose}>
    <form className="record-form" onSubmit={submit}>
      <label className="field"><span>DATE</span><input type="date" value={form.occurred_on} onChange={(e) => setForm({ ...form, occurred_on: e.target.value })} required />{errors.occurred_on && <em>{errors.occurred_on[0]}</em>}</label>
      <label className="field"><span>CHARGE CATEGORY</span><select value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })} required><option value="">SELECT CATEGORY</option>{categories.map((item) => <option key={item.url || item.name} value={item.url || item.name}>{item.name}</option>)}</select>{errors.category && <em>{errors.category[0]}</em>}</label>
      <label className="field"><span>LOCATION</span><input value={form.location} onChange={(e) => setForm({ ...form, location: e.target.value })} required />{errors.location && <em>{errors.location[0]}</em>}</label>
      <label className="field"><span>DISPOSITION / OUTCOME</span><input value={form.disposition} onChange={(e) => setForm({ ...form, disposition: e.target.value })} /></label>
      {errors.form && <p className="form-error">{errors.form[0]}</p>}
      <div className="modal-actions"><button type="button" className="button button-outline" onClick={onClose}>CANCEL</button><button className="button" disabled={mutation.isPending}>ADD OFFENSE</button></div>
    </form>
  </Modal>
}

function NoteForm({ subjectId, onClose }) {
  const mutation = useAddNote(subjectId)
  const [body, setBody] = useState('')
  const [error, setError] = useState('')
  const submit = async (event) => { event.preventDefault(); setError(''); try { await mutation.mutateAsync({ body }); onClose() } catch (issue) { setError(issue.response?.data?.errors?.body?.[0] || 'Unable to add note.') } }
  return <Modal eyebrow="RESTRICTED CASE INTELLIGENCE" title="ADD INVESTIGATOR NOTE" onClose={onClose}>
    <form className="record-form" onSubmit={submit}><label className="field"><span>NOTE BODY</span><textarea rows="7" value={body} onChange={(e) => setBody(e.target.value)} required minLength={3} /></label>{error && <p className="form-error">{error}</p>}<div className="modal-actions"><button type="button" className="button button-outline" onClick={onClose}>CANCEL</button><button className="button" disabled={mutation.isPending}>SAVE NOTE</button></div></form>
  </Modal>
}

function DetailView({ id }) {
  const [params, setParams] = useSearchParams()
  const activeTab = tabs.includes(params.get('tab')) ? params.get('tab') : 'overview'
  const recordQuery = useSubject(id)
  const record = recordQuery.data?.data
  const [editing, setEditing] = useState(false)
  const [addingOffense, setAddingOffense] = useState(false)
  const [addingNote, setAddingNote] = useState(false)
  const edit = useUpdateSubject(id)
  const remove = useDeleteSubject()
  const toast = useToast()
  const offenses = useOffenses(id)
  const notes = useNotes(id)
  if (recordQuery.isLoading) return <section className="record-detail"><LoadingRows count={4} /></section>
  if (recordQuery.isError) return <section className="record-detail"><ErrorState message={recordQuery.error?.response?.data?.message || `Could not load this record${recordQuery.error?.response?.status ? ` (HTTP ${recordQuery.error.response.status})` : '. Check that the API is running and the record still exists.'}`} onRetry={() => recordQuery.refetch()} /></section>
  if (!record) return <EmptyState title="RECORD UNAVAILABLE" />
  return <section className="record-detail">
    <div className="active-file"><span><small>ACTIVE CASE FILE</small><b>{record.case_id}</b></span><div className="record-actions"><button className="button button-outline" onClick={() => setEditing(true)}>EDIT RECORD</button><button className="button button-danger" disabled={remove.isPending} onClick={async () => { if (window.confirm('Archive this fictional training record?')) { await remove.mutateAsync(id); toast('RECORD ARCHIVED'); window.location.assign('/database') } }}>ARCHIVE</button></div></div>
    <div className="subject-profile">
      <div className="photo-placeholder">{record.photo_url ? <img src={record.photo_url} alt="" /> : <><span>{record.initials || record.name?.split(' ').map((part) => part[0]).join('').slice(0, 2)}</span><small>IMAGE PENDING</small></>}</div>
      <div className="profile-copy"><small className="eyebrow">REGISTERED SUBJECT</small><h1>{record.name}</h1><b className="alias">KNOWN ALIAS // {record.alias || 'UNLISTED'}</b><div className="profile-facts"><div><small>AGE</small><b>{record.age || '—'}</b></div><div><small>CLASSIFICATION</small><b>{record.classification}</b></div><div><small>LAST KNOWN LOCATION</small><b>{record.last_known_location || 'UNCONFIRMED'}</b></div></div></div>
      <div className="status-card"><small>SUBJECT STATUS</small><StatusTag status={record.status} /></div>
    </div>
    <nav className="tabs">{tabs.map((tab) => <button key={tab} className={activeTab === tab ? 'active' : ''} onClick={() => setParams({ tab })}>{tab.toUpperCase()}{tab === 'offenses' && <i>{record.offenses_count || 0}</i>}</button>)}</nav>
    {activeTab === 'overview' && <div className="overview-grid">
      <Panel eyebrow="01" title="PHYSICAL CHARACTERISTICS" className="character-panel"><div className="character-grid"><div><small>HEIGHT</small><b>{record.height || '—'}</b></div><div><small>EYE COLOR</small><b>{record.eye_color || '—'}</b></div><div><small>HAIR COLOR</small><b>{record.hair_color || '—'}</b></div><div><small>DISTINGUISHING FEATURE</small><b>{record.distinguishing_features || '—'}</b></div></div></Panel>
      <Panel eyebrow="02" title="THREAT ASSESSMENT" className="threat-panel"><div className="threat-score"><b>{record.risk_score}</b><small>/ 100</small></div><RiskBar score={record.risk_score} /><div className="risk-legend"><span>LOW</span><span>MODERATE</span><span>CRITICAL</span></div><strong className={record.risk_score >= 80 ? 'risk-high' : ''}>{record.risk_score >= 80 ? 'HIGH RISK — ENHANCED RESPONSE ADVISED' : 'STANDARD MONITORING'}</strong></Panel>
      {record.officer_advisory && <div className="advisory"><small>OFFICER ADVISORY</small><p>{record.officer_advisory}</p><span>REVIEWED // {formatDate(record.advisory_reviewed_at)} // {record.advisory_reviewed_by || 'DET. A. CROSS'}</span></div>}
      <Panel eyebrow="03" title="RECENT ACTIVITY" className="recent-panel"><div className="offense-rows">{(record.offenses || []).map((offense) => <div className="offense-row" key={offense.id}><span>{formatDate(offense.occurred_on)}</span><StatusTag status={offense.severity} /><div><b>{offense.charge}</b><small>{offense.location} // {offense.disposition}</small></div></div>)}</div></Panel>
    </div>}
    {activeTab === 'offenses' && <Panel eyebrow="03" title="RECORDED OFFENSES" action={<button className="button button-outline" onClick={() => setAddingOffense(true)}>＋ ADD OFFENSE</button>}><div className="table-scroll"><table><thead><tr><th>DATE</th><th>CHARGE</th><th>LOCATION</th><th>DISPOSITION</th></tr></thead><tbody>{offenses.isLoading ? <tr><td colSpan="4">LOADING OFFENSES…</td></tr> : (offenses.data?.data || []).map((item) => <tr key={item.id}><td>{formatDate(item.occurred_on)}</td><td><b>{item.charge}</b></td><td>{item.location}</td><td>{item.disposition}</td></tr>)}</tbody></table>{offenses.data?.data?.length === 0 && <EmptyState />}{offenses.isError && <ErrorState onRetry={() => offenses.refetch()} />}</div></Panel>}
    {activeTab === 'notes' && <Panel eyebrow="03" title="INVESTIGATOR NOTES" action={<button className="button button-outline" onClick={() => setAddingNote(true)}>＋ ADD NOTE</button>}><div className="notes-list">{notes.isLoading ? <LoadingRows count={2} /> : (notes.data?.data || []).map((note) => <article className="case-note" key={note.id}><p>{note.body}</p><small>ENTERED BY // {note.entered_by} · {new Date(note.created_at).toLocaleString()} · ACCESS LEVEL {String(note.access_level).padStart(2, '0')}</small></article>)}{notes.data?.data?.length === 0 && <EmptyState title="NO INVESTIGATOR NOTES" />}{notes.isError && <ErrorState onRetry={() => notes.refetch()} />}</div></Panel>}
    {editing && <RecordModal initial={record} onClose={() => setEditing(false)} onSave={(body) => edit.mutateAsync(body)} busy={edit.isPending} saveLabel="SAVE CHANGES" />}
    {addingOffense && <OffenseForm subjectId={id} onClose={() => setAddingOffense(false)} />}
    {addingNote && <NoteForm subjectId={id} onClose={() => setAddingNote(false)} />}
  </section>
}

export function DatabasePage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [creating, setCreating] = useState(false)
  const create = useCreateSubject()
  return <div className="database-layout">
    <RecordList activeId={id} onCreate={() => setCreating(true)} />
    {id ? <DetailView id={id} /> : <section className="database-empty"><span className="database-mark">RPD</span><small>SECURE RECORDS NETWORK</small><h1>SUBJECT<br />DATABASE</h1><p>Select a subject file from the index to review intelligence or create a new case record.</p><Link className="button button-outline" to="/command">RETURN TO COMMAND CENTER</Link></section>}
    {creating && <RecordModal onClose={() => setCreating(false)} onSave={async (body) => { const result = await create.mutateAsync(body); if (result?.data?.id) navigate(`/database/${result.data.id}`); return result }} busy={create.isPending} />}
  </div>
}
