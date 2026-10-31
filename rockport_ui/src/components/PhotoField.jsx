import { useEffect, useState } from 'react'
import { PHOTO, validatePhoto } from '../lib/validation'

/**
 * Choose a portrait from your computer. The picture is checked here first
 * (type, size, pixel size) and again by the server, which is the check that counts.
 *
 *   currentUrl  the portrait the record has now, if any
 *   photo       a newly chosen file: { file, previewUrl }, or null
 *   removed     true if the person asked to take the current portrait off
 */
export function PhotoField({ currentUrl, photo, removed, onPick, onRemove, error, onError }) {
  const [brokenUrl, setBrokenUrl] = useState(null)
  const previewUrl = photo?.previewUrl

  // Free the temporary preview when it is replaced or the form closes.
  useEffect(
    () => () => {
      if (previewUrl) URL.revokeObjectURL(previewUrl)
    },
    [previewUrl],
  )

  const shown = previewUrl ?? (removed ? null : currentUrl)
  const canRemove = Boolean(photo || (currentUrl && !removed))

  const choose = async (event) => {
    const file = event.target.files?.[0]

    // Clear the field so choosing the same file twice still triggers a change.
    event.target.value = ''

    if (!file) return

    const problem = await validatePhoto(file)

    if (problem) {
      onError(problem)

      return
    }

    onError('')
    onRemove(false)
    onPick({ file, previewUrl: URL.createObjectURL(file) })
  }

  const remove = () => {
    onError('')
    onPick(null)
    onRemove(true)
  }

  return (
    <div className="photo-field">
      <div className="photo-preview">
        {shown && shown !== brokenUrl ? <img src={shown} alt="Portrait preview" onError={() => setBrokenUrl(shown)} /> : <span>NO PHOTO</span>}
      </div>
      <div className="photo-controls">
        <small className="eyebrow">PORTRAIT</small>
        <p>JPG, PNG or WebP. Up to {PHOTO.maxBytes / 1024 / 1024} MB. {PHOTO.minSide} to {PHOTO.maxSide} pixels on each side.</p>
        <div className="photo-actions">
          <label className="button button-outline file-pick">
            <input type="file" accept={PHOTO.types.join(',')} onChange={choose} />
            {shown ? 'CHOOSE ANOTHER' : 'CHOOSE IMAGE'}
          </label>
          {canRemove && (
            <button type="button" className="button button-outline" onClick={remove}>REMOVE</button>
          )}
          {removed && !photo && (
            <button type="button" className="button button-outline" onClick={() => onRemove(false)}>UNDO REMOVE</button>
          )}
        </div>
        {removed && !photo && <small className="photo-note">THE PORTRAIT WILL BE REMOVED WHEN YOU SAVE.</small>}
        {error && <em className="photo-error">{error}</em>}
      </div>
    </div>
  )
}
