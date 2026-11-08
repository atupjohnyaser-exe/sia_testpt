import { useEffect, useRef } from 'react'
import { cells, makeDrops, stepDrops } from '../lib/rain'

const FONT_SIZE = 16
const FRAME_MS = 45

/**
 * Digits falling in the background, like a live terminal feed. Drawn on a canvas.
 * It stops while the tab is hidden, and draws one still picture if the person asked for less motion.
 */
export function DigitalRain({ className = '' }) {
  const canvasRef = useRef(null)

  useEffect(() => {
    const canvas = canvasRef.current
    const context = canvas?.getContext('2d')

    if (!canvas || !context) return undefined

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    let drops = []
    let rows = 0
    let tick = 0
    let frame = 0
    let lastDraw = 0

    const draw = () => {
      const { width, height } = canvas.getBoundingClientRect()

      context.clearRect(0, 0, width, height)
      context.font = `${FONT_SIZE}px "Space Mono", Consolas, monospace`
      context.textBaseline = 'top'

      drops.forEach((drop, column) => {
        for (const cell of cells(drop, column, rows, tick)) {
          context.fillStyle = cell.head ? 'rgba(226, 255, 150, 0.95)' : `rgba(142, 205, 38, ${(cell.alpha * 0.7).toFixed(2)})`
          context.fillText(cell.glyph, column * FONT_SIZE, cell.row * FONT_SIZE)
        }
      })
    }

    const resize = () => {
      const { width, height } = canvas.getBoundingClientRect()
      const ratio = Math.min(window.devicePixelRatio || 1, 2)

      canvas.width = Math.floor(width * ratio)
      canvas.height = Math.floor(height * ratio)
      context.setTransform(ratio, 0, 0, ratio, 0, 0)

      rows = Math.ceil(height / FONT_SIZE)
      drops = makeDrops(Math.ceil(width / FONT_SIZE), rows)

      // Start with the streaks already part way down, so the screen is never empty.
      for (let step = 0; step < 40; step += 1) stepDrops(drops, rows)

      draw()
    }

    const loop = (time) => {
      frame = window.requestAnimationFrame(loop)

      if (document.hidden || time - lastDraw < FRAME_MS) return

      lastDraw = time
      tick += 1
      stepDrops(drops, rows)
      draw()
    }

    resize()

    const observer = new ResizeObserver(resize)
    observer.observe(canvas)

    if (!reduceMotion) frame = window.requestAnimationFrame(loop)

    return () => {
      window.cancelAnimationFrame(frame)
      observer.disconnect()
    }
  }, [])

  return <canvas ref={canvasRef} className={`digital-rain ${className}`} aria-hidden="true" />
}
