import { useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { EmptyState, Field, Pagination, Panel, QueryState } from '../../components/ui'
import { useAuth } from '../../context/auth'
import { useToast } from '../../context/toast'
import { useAddNote, useDeleteNote } from '../../hooks/mutations'
import { useNotes } from '../../hooks/queries'
import { errorMessage, fieldErrors } from '../../lib/errors'
import { dateTimeText } from '../../lib/format'

export function NotesTab() {
  const { subject } = useOutletContext()
  const { officer, can } = useAuth()
  const toast = useToast()
  const [page, setPage] = useState(1)
  const [body, setBody] = useState('')
  const [level, setLevel] = useState('3')
  const [errors, setErrors] = useState({})
  const notes = useNotes(subject.id, { page })
  const add = useAddNote(subject.id)
  const remove = useDeleteNote(subject.id)

  // You can lock a note up to your own clearance and no higher.
  const ceiling = Math.min(officer.clearance_level, 4)
  const levels = Array.from({ length: ceiling }, (_, index) => index + 1)
  const chosen = Math.min(Number(level), ceiling)

  const submit = async (event) => {
    event.preventDefault()
    setErrors({})

    try {
      await add.mutateAsync({ body, access_level: chosen })
      setBody('')
      setPage(1)
    } catch (error) {
      setErrors(fieldErrors(error))
    }
  }

  const removeNote = async (note) => {
    if (!window.confirm('Delete this note?')) return

    try {
      await remove.mutateAsync(note.id)
    } catch (error) {
      toast(errorMessage(error, 'COULD NOT DELETE THIS NOTE'))
    }
  }

  return (
    <>
      {can(2) && (
        <Panel eyebrow="CASE NOTES" title="ADD A NOTE">
          <form className="record-form" onSubmit={submit}>
            <Field label="NOTE" error={errors.body}>
              <textarea rows="3" value={body} onChange={(event) => setBody(event.target.value)} required minLength={3} />
            </Field>
            <Field label="WHO CAN READ THIS" error={errors.access_level}>
              <select value={chosen} onChange={(event) => setLevel(event.target.value)}>
                {levels.map((value) => <option key={value} value={value}>CLEARANCE {value} AND ABOVE</option>)}
              </select>
            </Field>
            {errors.form && <p className="form-error">{errors.form}</p>}
            <div className="modal-actions"><button className="button" disabled={add.isPending}>{add.isPending ? 'SAVING…' : 'ADD NOTE'}</button></div>
          </form>
        </Panel>
      )}

      <Panel eyebrow="ON FILE" title="NOTES">
        <QueryState query={notes}>
          {notes.data?.data.length ? (
            <div className="notes-list">
              {notes.data.data.map((note) => (
                <article className={`case-note ${note.restricted ? 'restricted' : ''}`} key={note.id}>
                  <p>{note.body}</p>
                  <small>
                    {note.author?.name ?? 'UNKNOWN'} // {dateTimeText(note.created_at)} // LEVEL {note.access_level}
                  </small>
                  {(note.author?.id === officer.id || can(4)) && (
                    <button className="mini-button danger" onClick={() => removeNote(note)}>DELETE</button>
                  )}
                </article>
              ))}
            </div>
          ) : (
            <EmptyState title="NO NOTES YET" />
          )}
          <Pagination meta={notes.data?.meta} onPage={setPage} />
        </QueryState>
      </Panel>
    </>
  )
}
