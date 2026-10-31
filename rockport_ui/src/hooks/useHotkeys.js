import { useEffect } from 'react'

// True when the key press belongs to a form control or an open dialog.
function isTyping(event) {
  if (event.target instanceof Element && event.target.closest('input, textarea, select, [contenteditable="true"]')) return true

  return Boolean(document.querySelector('dialog[open]'))
}

/** One key, like Escape or "1". Ignored while typing or while a dialog is open. */
export function useHotkey(key, handler, enabled = true) {
  useEffect(() => {
    if (!enabled) return undefined

    const onKey = (event) => {
      if (event.altKey || event.ctrlKey || event.metaKey) return
      if (event.key !== key || isTyping(event)) return

      event.preventDefault()
      handler()
    }

    window.addEventListener('keydown', onKey)

    return () => window.removeEventListener('keydown', onKey)
  }, [key, handler, enabled])
}

/**
 * Arrow up and down move through a list of ids, starting from the one that is open now.
 * Does nothing while typing or while a dialog is open, so it never steals keys from forms.
 */
export function useListHotkeys(ids, activeId, onSelect) {
  useEffect(() => {
    const onKey = (event) => {
      if (event.altKey || event.ctrlKey || event.metaKey) return
      if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return
      if (isTyping(event) || ids.length === 0) return

      const current = ids.indexOf(activeId)
      const next = event.key === 'ArrowDown' ? Math.min(ids.length - 1, current + 1) : Math.max(0, current - 1)

      event.preventDefault()

      if (ids[next] !== activeId) onSelect(ids[next])
    }

    window.addEventListener('keydown', onKey)

    return () => window.removeEventListener('keydown', onKey)
  }, [ids, activeId, onSelect])
}

/** Ctrl or Cmd + K focuses the given input. */
export function useFocusShortcut(ref) {
  useEffect(() => {
    const onKey = (event) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault()
        ref.current?.focus()
      }
    }

    window.addEventListener('keydown', onKey)

    return () => window.removeEventListener('keydown', onKey)
  }, [ref])
}
