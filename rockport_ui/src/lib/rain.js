/*
 * The moving numbers behind the command page. Plain functions, so they can be tested without a screen.
 * A "drop" is one falling streak of digits in one column.
 */

export const GLYPHS = '0123456789'

export function makeDrops(columns, rows, random = Math.random) {
  return Array.from({ length: columns }, () => ({
    y: -random() * rows,
    speed: 0.25 + random() * 0.7,
    length: 6 + Math.floor(random() * 14),
  }))
}

/** Moves every drop down. A drop that has fallen off the bottom starts again above the top. */
export function stepDrops(drops, rows, random = Math.random) {
  for (const drop of drops) {
    drop.y += drop.speed

    if (drop.y - drop.length > rows) {
      drop.y = -random() * 12
      drop.speed = 0.25 + random() * 0.7
      drop.length = 6 + Math.floor(random() * 14)
    }
  }
}

/** The digit shown in a cell. It changes slowly over time so the streaks shimmer instead of flicker. */
export function glyph(column, row, tick) {
  return GLYPHS[(column * 31 + row * 17 + (tick >> 3) * 7) % GLYPHS.length]
}

/** Every visible cell of one drop: { row, glyph, alpha, head }. The head is the brightest. */
export function cells(drop, column, rows, tick) {
  const head = Math.floor(drop.y)
  const result = []

  for (let index = 0; index < drop.length; index += 1) {
    const row = head - index

    if (row >= 0 && row < rows) {
      result.push({ row, glyph: glyph(column, row, tick), alpha: 1 - index / drop.length, head: index === 0 })
    }
  }

  return result
}
