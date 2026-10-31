import { useEffect, useRef } from 'react'
import { label, num } from '../lib/format'

export function Logo() {
  return (
    <div className="brand">
      <div className="brand-seal"><span>RPD</span></div>
      <div>
        <small>ROCKPORT // RECORDS DIVISION</small>
        <strong>COMMAND CENTER</strong>
      </div>
    </div>
  )
}

/** Colour comes from the CSS class status-<value>. */
export function StatusTag({ status }) {
  const value = String(status ?? '')

  return <span className={`status-tag status-${value.toLowerCase()}`}>{label(value) || 'UNKNOWN'}</span>
}

export function Panel({ eyebrow, title, action, className = '', children }) {
  return (
    <section className={`panel ${className}`}>
      <div className="panel-head">
        {eyebrow && <small className="eyebrow">{eyebrow}</small>}
        <div className="panel-title-row">
          <h2>{title}</h2>
          {action}
        </div>
      </div>
      {children}
    </section>
  )
}

/** A number cell. Shows a dash while loading instead of padding it with a zero. */
export function StatCell({ label: title, caption, value, tone = '' }) {
  const shown = value === null || value === undefined ? '—' : num(value)

  return (
    <div className="stat-cell">
      <small>{title}</small>
      <span>{caption}</span>
      <b className={tone}>{shown}</b>
    </div>
  )
}

export function ErrorState({ onRetry, message = 'The secure records service is not responding.' }) {
  return (
    <div className="state-box">
      <b>CONNECTION ERROR</b>
      <p>{message}</p>
      {onRetry && <button className="button button-outline" onClick={onRetry}>RETRY ↻</button>}
    </div>
  )
}

export function LoadingRows({ count = 4 }) {
  return (
    <div className="loading-list" aria-label="Loading">
      {Array.from({ length: count }, (_, index) => (
        <div className="skeleton-row" key={index}><i /><span /><b /></div>
      ))}
    </div>
  )
}

export function EmptyState({ title = 'NOTHING HERE YET', message = '' }) {
  return (
    <div className="empty-state">
      <b>{title}</b>
      {message && <p>{message}</p>}
    </div>
  )
}

export function PageFooter() {
  return (
    <footer className="page-footer">
      <span>RPD RECORDS NETWORK // AUTHORIZED USE ONLY</span>
      <strong>FICTIONAL TRAINING DATA</strong>
      <span>SESSION ENCRYPTED</span>
    </footer>
  )
}

export function PageHeading({ eyebrow, title, children, action }) {
  return (
    <header className="page-heading">
      <div>
        <small className="eyebrow">{eyebrow}</small>
        <h1>{title}</h1>
        {children && <p>{children}</p>}
      </div>
      {action}
    </header>
  )
}

/** Wraps one query so every screen handles loading and errors the same way. */
export function QueryState({ query, rows = 3, children }) {
  if (query.isLoading) return <LoadingRows count={rows} />
  if (query.isError) return <ErrorState onRetry={() => query.refetch()} />

  return children
}

export function Pagination({ meta, onPage }) {
  if (!meta || meta.last_page <= 1) return null

  return (
    <nav className="pagination" aria-label="Pagination">
      <button className="button button-outline" disabled={meta.current_page <= 1} onClick={() => onPage(meta.current_page - 1)}>← PREV</button>
      <span>PAGE {meta.current_page} OF {meta.last_page} // {num(meta.total)} RECORDS</span>
      <button className="button button-outline" disabled={meta.current_page >= meta.last_page} onClick={() => onPage(meta.current_page + 1)}>NEXT →</button>
    </nav>
  )
}

export function Field({ label: title, error, children }) {
  return (
    <label className="field">
      <span>{title}</span>
      {children}
      {error && <em>{error}</em>}
    </label>
  )
}

export function Modal({ title, eyebrow, onClose, children, wide = false }) {
  const dialog = useRef(null)

  useEffect(() => {
    const element = dialog.current
    element?.showModal()

    return () => element?.close()
  }, [])

  return (
    <dialog
      ref={dialog}
      className={`modal ${wide ? 'modal-wide' : ''}`}
      onCancel={(event) => {
        event.preventDefault()
        onClose()
      }}
      onClick={(event) => {
        if (event.target === dialog.current) onClose()
      }}
    >
      <header className="modal-head">
        <div>
          <small className="eyebrow">{eyebrow}</small>
          <h2>{title}</h2>
        </div>
        <button className="icon-button" aria-label="Close dialog" onClick={onClose}>×</button>
      </header>
      {children}
    </dialog>
  )
}

/** Cancel and submit buttons at the bottom of every form dialog. */
export function ModalActions({ onClose, busy, submitLabel, busyLabel = 'SAVING…' }) {
  return (
    <div className="modal-actions">
      <button type="button" className="button button-outline" onClick={onClose}>CANCEL</button>
      <button className="button" disabled={busy}>{busy ? busyLabel : submitLabel}</button>
    </div>
  )
}
