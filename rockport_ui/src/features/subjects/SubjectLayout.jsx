import { useState } from 'react'
import { NavLink, Outlet, useNavigate, useParams } from 'react-router-dom'
import { GuardedButton, GuardedLink } from '../../components/GuardedButton'
import { ErrorState, LoadingRows, StatusTag } from '../../components/ui'
import { useToast } from '../../context/toast'
import { useArchiveSubject } from '../../hooks/mutations'
import { useSubject } from '../../hooks/queries'
import { errorMessage } from '../../lib/errors'
import { dateText } from '../../lib/format'
import { StatusModal } from './StatusModal'
import { SubjectFormModal } from './SubjectFormModal'
import { ThreatOverrideModal } from './ThreatOverrideModal'

export function SubjectLayout() {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const query = useSubject(id)
  const archive = useArchiveSubject()
  const [modal, setModal] = useState(null)
  const [brokenPhoto, setBrokenPhoto] = useState(null)

  if (query.isLoading) return <LoadingRows count={5} />

  if (query.isError) {
    const missing = query.error?.response?.status === 404

    return <ErrorState onRetry={() => query.refetch()} message={missing ? 'This record does not exist or was archived.' : undefined} />
  }

  const subject = query.data.data
  const base = `/database/${subject.id}`

  const archiveRecord = async () => {
    if (!window.confirm(`Archive ${subject.name}? A supervisor can restore the record later.`)) return

    try {
      await archive.mutateAsync(subject.id)
      navigate('/database')
    } catch (error) {
      toast(errorMessage(error, 'COULD NOT ARCHIVE THIS RECORD'))
    }
  }

  return (
    <>
      <div className="active-file">
        <span><small>ACTIVE FILE</small><b>{subject.case_id}</b></span>
        <div className="record-actions">
          <GuardedButton level={3} action="Editing a record" onClick={() => setModal('edit')}>EDIT</GuardedButton>
          <GuardedButton level={subject.status === 'CLEARED' ? 5 : 3} action={subject.status === 'CLEARED' ? 'Re-opening a cleared record' : 'Changing a status'} onClick={() => setModal('status')}>CHANGE STATUS</GuardedButton>
          <GuardedLink level={3} action="Printing a case file" to={`/case-file/${subject.id}`}>CASE FILE</GuardedLink>
          <GuardedButton level={4} action="Archiving a record" className="button-danger" onClick={archiveRecord}>ARCHIVE</GuardedButton>
        </div>
      </div>

      <section className="subject-profile">
        <div className={`photo-placeholder ${subject.photo_url && subject.photo_url !== brokenPhoto ? 'has-photo' : ''}`}>
          {subject.photo_url && subject.photo_url !== brokenPhoto ? (
            <img src={subject.photo_url} alt={`Portrait of ${subject.name}`} onError={() => setBrokenPhoto(subject.photo_url)} />
          ) : (
            <span>NO PHOTO</span>
          )}
          <small>FICTIONAL</small>
        </div>
        <div className="profile-copy">
          <small className="alias">AKA: {(subject.alias || 'UNKNOWN').toUpperCase()}</small>
          <h1>{subject.name}</h1>
          <div className="profile-facts">
            <div><small>AGE</small><b>{subject.age ?? '—'}</b></div>
            <div><small>LAST KNOWN LOCATION</small><b>{subject.last_known_location || 'UNCONFIRMED'}</b></div>
            <div><small>FILE OPENED</small><b>{dateText(subject.created_at)}</b></div>
          </div>
        </div>
        <div className="status-card">
          <small>STATUS</small>
          <StatusTag status={subject.status} />
        </div>
      </section>

      <nav className="tabs" aria-label="Record sections">
        <NavLink to={base} end>OVERVIEW</NavLink>
        <NavLink to={`${base}/infractions`}>INFRACTIONS{subject.unserved_infractions > 0 && <i>{subject.unserved_infractions}</i>}</NavLink>
        <NavLink to={`${base}/manhunts`}>MANHUNTS{subject.times_evaded + subject.times_apprehended > 0 && <i>{subject.times_evaded + subject.times_apprehended}</i>}</NavLink>
        <NavLink to={`${base}/locations`}>LOCATIONS</NavLink>
        <NavLink to={`${base}/cost`}>COST TO STATE</NavLink>
        <NavLink to={`${base}/analytics`}>ANALYTICS</NavLink>
        <NavLink to={`${base}/notes`}>NOTES</NavLink>
        <NavLink to={`${base}/history`}>HISTORY</NavLink>
      </nav>

      <Outlet context={{ subject, openThreatOverride: () => setModal('threat') }} />

      {modal === 'edit' && <SubjectFormModal subject={subject} onClose={() => setModal(null)} />}
      {modal === 'status' && <StatusModal subject={subject} onClose={() => setModal(null)} />}
      {modal === 'threat' && <ThreatOverrideModal subject={subject} onClose={() => setModal(null)} />}
    </>
  )
}
