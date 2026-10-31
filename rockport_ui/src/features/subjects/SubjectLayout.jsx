import { useState } from 'react'
import { Link, NavLink, Outlet, useNavigate, useParams } from 'react-router-dom'
import { ErrorState, LoadingRows, StatusTag } from '../../components/ui'
import { useAuth } from '../../context/auth'
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
  const { can } = useAuth()
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
          {can(3) && <button className="button button-outline" onClick={() => setModal('edit')}>EDIT</button>}
          {can(3) && <button className="button button-outline" onClick={() => setModal('status')}>CHANGE STATUS</button>}
          {can(3) && <Link className="button button-outline" to={`/case-file/${subject.id}`}>CASE FILE</Link>}
          {can(4) && <button className="button-danger" onClick={archiveRecord}>ARCHIVE</button>}
        </div>
      </div>

      <section className="subject-profile">
        <div className="photo-placeholder">
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
