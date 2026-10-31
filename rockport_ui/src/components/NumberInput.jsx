import { useEffect, useRef } from 'react'

const clamp = (value, min, max) => Math.min(max, Math.max(min, value))

/**
 * A number field with its own small up and down buttons in the site's colours,
 * instead of the browser's grey ones. Click to change by one step. Hold to keep going.
 * Typing works too, and so do the arrow keys. The value is a number, or '' when empty.
 */
export function NumberInput({ value, onChange, min = 0, max = 999999, step = 1, required = false, disabled = false, ariaLabel }) {
  const timers = useRef({ hold: null, repeat: null })
  const latest = useRef({ value, onChange, min, max, step })

  // The hold-to-repeat timer runs outside React's render, so it reads the newest props from here.
  useEffect(() => {
    latest.current = { value, onChange, min, max, step }
  })

  const stop = () => {
    window.clearTimeout(timers.current.hold)
    window.clearInterval(timers.current.repeat)
  }

  // Never leave a timer running if the field disappears while a button is held.
  useEffect(() => {
    const held = timers.current

    return () => {
      window.clearTimeout(held.hold)
      window.clearInterval(held.repeat)
    }
  }, [])

  const bump = (direction) => {
    const { value: current, onChange: change, min: low, max: high, step: size } = latest.current
    const base = current === '' || Number.isNaN(Number(current)) ? (direction > 0 ? low - size : low + size) : Number(current)

    change(clamp(base + direction * size, low, high))
  }

  const press = (event, direction) => {
    event.preventDefault()
    stop()
    bump(direction)

    timers.current.hold = window.setTimeout(() => {
      timers.current.repeat = window.setInterval(() => bump(direction), 70)
    }, 400)
  }

  const typed = (event) => {
    const raw = event.target.value

    onChange(raw === '' ? '' : Math.trunc(Number(raw)))
  }

  // Keep the number inside its limits once the person leaves the field.
  const settle = () => {
    if (value !== '' && !Number.isNaN(Number(value))) onChange(clamp(Number(value), min, max))
  }

  const blockLetters = (event) => {
    if (['e', 'E', '+', '-', '.', ','].includes(event.key)) event.preventDefault()
  }

  const atMin = value !== '' && Number(value) <= min
  const atMax = value !== '' && Number(value) >= max

  return (
    <div className="stepper">
      <input
        type="number"
        inputMode="numeric"
        value={value}
        min={min}
        max={max}
        step={step}
        required={required}
        disabled={disabled}
        aria-label={ariaLabel}
        onChange={typed}
        onBlur={settle}
        onKeyDown={blockLetters}
        onWheel={(event) => event.currentTarget.blur()}
      />
      <div className="stepper-buttons">
        <button
          type="button"
          tabIndex={-1}
          disabled={disabled || atMax}
          aria-label="Increase"
          onPointerDown={(event) => press(event, 1)}
          onPointerUp={() => stop()}
          onPointerLeave={() => stop()}
          onPointerCancel={() => stop()}
        >
          <svg viewBox="0 0 10 6" width="10" height="6" aria-hidden="true"><path d="M1 5 5 1l4 4" fill="none" stroke="currentColor" strokeWidth="1.6" /></svg>
        </button>
        <button
          type="button"
          tabIndex={-1}
          disabled={disabled || atMin}
          aria-label="Decrease"
          onPointerDown={(event) => press(event, -1)}
          onPointerUp={() => stop()}
          onPointerLeave={() => stop()}
          onPointerCancel={() => stop()}
        >
          <svg viewBox="0 0 10 6" width="10" height="6" aria-hidden="true"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" strokeWidth="1.6" /></svg>
        </button>
      </div>
    </div>
  )
}
