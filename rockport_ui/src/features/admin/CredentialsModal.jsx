import { useState } from 'react'
import { Modal } from '../../components/ui'
import { useToast } from '../../context/toast'
import { levelTag } from '../../lib/clearance'

/**
 * The badge ID and password of a new or reset account.
 * The password is shown once. After this window closes nobody can read it back.
 */
export function CredentialsModal({ credentials, onClose }) {
  const toast = useToast()
  const [noted, setNoted] = useState(false)

  const copy = async (text) => {
    try {
      await navigator.clipboard.writeText(text)
      toast('COPIED')
    } catch {
      toast('COULD NOT COPY. SELECT THE TEXT AND COPY IT BY HAND.')
    }
  }

  return (
    <Modal title="ACCESS CREDENTIALS" eyebrow={`${credentials.name.toUpperCase()} // ${levelTag(credentials.level)}`} onClose={() => noted && onClose()}>
      <div className="record-form">
        <div className="credential-warning">
          <b>THIS PASSWORD IS SHOWN ONCE.</b> Give it to the officer in person. It cannot be read again. If it is lost, issue a new one.
        </div>
        <div className="credential-box">
          <small>BADGE ID</small>
          <strong>{credentials.badge_id}</strong>
          <button type="button" className="mini-button" onClick={() => copy(credentials.badge_id)}>COPY</button>
        </div>
        <div className="credential-box">
          <small>PASSWORD</small>
          <strong>{credentials.password}</strong>
          <button type="button" className="mini-button" onClick={() => copy(credentials.password)}>COPY</button>
        </div>
        <label className="declaration">
          <input type="checkbox" checked={noted} onChange={(event) => setNoted(event.target.checked)} />
          I have given these credentials to the officer.
        </label>
        <div className="modal-actions">
          <button className="button" disabled={!noted} onClick={onClose}>DONE</button>
        </div>
      </div>
    </Modal>
  )
}
