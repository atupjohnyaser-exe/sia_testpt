import { useEffect, useRef, useState } from 'react'

export function Logo({ compact = false }) {
  return <div className="brand"><div className="brand-seal"><span>RPD</span></div><div><small>ROCKPORT // RECORDS DIVISION</small>{!compact && <strong>COMMAND CENTER</strong>}</div></div>
}
export function StatusTag({ status }) { return <span className={`status-tag status-${String(status || '').toLowerCase()}`}>{String(status || 'UNKNOWN').replaceAll('_', ' ')}</span> }
export function RiskBar({ score = 0 }) { return <div className="risk-wrap"><div className="risk-track"><span style={{ width: `${Math.min(100, score)}%` }} /></div><b className={score >= 80 ? 'risk-high' : ''}>{score}</b></div> }
export function Panel({ eyebrow, title, action, className = '', children }) { return <section className={`panel ${className}`}><div className="panel-head">{eyebrow && <small className="eyebrow">{eyebrow}</small>}<div className="panel-title-row"><h2>{title}</h2>{action}</div></div>{children}</section> }
export function StatCell({ label, caption, value, tone = '' }) { return <div className="stat-cell"><small>{label}</small><span>{caption}</span><b className={tone}>{String(value ?? '—').padStart(2, '0')}</b></div> }
export function ErrorState({ onRetry, message = 'The secure records service is not responding.' }) { return <div className="state-box"><b>CONNECTION ERROR</b><p>{message}</p><button className="button button-outline" onClick={onRetry}>RETRY ↻</button></div> }
export function LoadingRows({ count = 4 }) { return <div className="loading-list" aria-label="Loading">{Array.from({ length: count }, (_, i) => <div className="skeleton-row" key={i}><i /><span /><b /></div>)}</div> }
export function EmptyState({ title = 'NO RECORDS FOUND', message = 'Adjust your search or create a new record.' }) { return <div className="empty-state"><b>{title}</b><p>{message}</p></div> }
export function PageFooter() { return <footer className="page-footer"><span>RPD RECORDS NETWORK // AUTHORIZED USE ONLY</span><strong>FICTIONAL TRAINING DATA</strong><span>SESSION ENCRYPTED</span></footer> }

export function Modal({ title, eyebrow, onClose, children, wide = false }) {
  const dialog = useRef(null)
  useEffect(() => { dialog.current?.showModal(); return () => dialog.current?.close() }, [])
  return <dialog ref={dialog} className={`modal ${wide ? 'modal-wide' : ''}`} onClose={onClose} onClick={(event) => { if (event.target === dialog.current) onClose() }}>
    <header className="modal-head"><div><small className="eyebrow">{eyebrow}</small><h2>{title}</h2></div><button className="icon-button" aria-label="Close dialog" onClick={onClose}>×</button></header>
    {children}
  </dialog>
}

export function RecordModal({ initial, onClose, onSave, busy = false, saveLabel = 'SAVE TO DATABASE' }) {
  const [form, setForm] = useState(() => ({ name: '', alias: '', age: '', status: 'WANTED', classification: 'Under review', last_known_location: '', height: '', eye_color: '', hair_color: '', risk_score: 50, distinguishing_features: '', officer_advisory: '', ...(initial || {}) }))
  const [errors, setErrors] = useState({})
  const fields = [
    ['name', 'FULL NAME', 'text', true], ['alias', 'KNOWN ALIAS', 'text'], ['age', 'AGE', 'number'], ['status', 'STATUS', 'select'],
    ['classification', 'CLASSIFICATION', 'select2'], ['last_known_location', 'LAST KNOWN LOCATION', 'text'],
    ['height', 'HEIGHT', 'text'], ['eye_color', 'EYE COLOR', 'text'], ['hair_color', 'HAIR COLOR', 'text'], ['risk_score', 'RISK SCORE', 'number'],
  ]
  const set = (key, value) => setForm((current) => ({ ...current, [key]: key === 'risk_score' || key === 'age' ? (value === '' ? '' : Number(value)) : value }))
  const submit = async (event) => {
    event.preventDefault(); setErrors({})
    try { await onSave({ ...form, age: form.age === '' ? null : Number(form.age), risk_score: Number(form.risk_score), risk_override: true }); onClose() }
    catch (error) { setErrors(error.response?.data?.errors || { form: [error.response?.data?.message || 'Could not save this record.'] }) }
  }
  return <Modal title={initial ? 'EDIT RECORD' : 'CREATE RECORD'} eyebrow="DATABASE ENTRY" onClose={onClose} wide>
    <form className="record-form" onSubmit={submit}>
      <div className="form-grid">
        {fields.map(([key, label, type, required]) => <label className="field" key={key}><span>{label}</span>
          {type === 'select' ? <select value={form[key]} onChange={(e) => set(key, e.target.value)}><option>WANTED</option><option>IN_CUSTODY</option><option>PAROLED</option><option>CLEARED</option></select>
            : type === 'select2' ? <select value={form[key]} onChange={(e) => set(key, e.target.value)}><option>Under review</option><option>Priority Beta</option><option>Priority Alpha</option></select>
              : <input type={type} min={type === 'number' ? 0 : undefined} max={key === 'age' ? 120 : key === 'risk_score' ? 100 : undefined} value={form[key] ?? ''} onChange={(e) => set(key, e.target.value)} required={required} />}
          {errors[key] && <em>{errors[key][0]}</em>}
        </label>)}
      </div>
      <label className="field"><span>DISTINGUISHING FEATURES</span><input value={form.distinguishing_features || ''} onChange={(e) => set('distinguishing_features', e.target.value)} /></label>
      <label className="field"><span>OFFICER ADVISORY</span><textarea rows="3" value={form.officer_advisory || ''} onChange={(e) => set('officer_advisory', e.target.value)} /></label>
      {errors.form && <p className="form-error">{errors.form[0]}</p>}
      <div className="modal-actions"><button type="button" className="button button-outline" onClick={onClose}>CANCEL</button><button disabled={busy} className="button">{busy ? 'SAVING…' : saveLabel}</button></div>
    </form>
  </Modal>
}

export function useKeyboardFocus(ref) {
  useEffect(() => {
    const handler = (event) => { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); ref.current?.focus() } }
    window.addEventListener('keydown', handler); return () => window.removeEventListener('keydown', handler)
  }, [ref])
}
