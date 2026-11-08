/*
 * Chart builders. Each one returns a small tree of SVG elements: { tag, props, children }.
 * Nothing here touches React or the page, so the same code can be drawn by the SvgTree component
 * and printed as plain SVG text for testing.
 */

export const COLORS = {
  lime: '#b5f42d',
  limeDim: '#6f9a1d',
  amber: '#ffc84c',
  orange: '#ff9a4c',
  red: '#ff5e58',
  blue: '#62c7ee',
  purple: '#b08cff',
  teal: '#5fe0b4',
  grey: '#4d5d49',
  text: '#e8f0e6',
  muted: '#8fa585',
  grid: '#243021',
  track: '#1a2318',
}

/** Colours for chart categories, in order. */
export const SERIES = [COLORS.lime, COLORS.amber, COLORS.blue, COLORS.red, COLORS.purple, COLORS.orange, COLORS.teal, '#d1d9c8']

export const h = (tag, props = {}, ...children) => ({ tag, props, children: children.flat().filter((child) => child !== null && child !== undefined && child !== false) })

const r = (value) => Math.round(value * 100) / 100

export function niceMax(value) {
  if (!(value > 0)) return 1

  const power = 10 ** Math.floor(Math.log10(value))
  const fraction = value / power
  const nice = fraction <= 1 ? 1 : fraction <= 2 ? 2 : fraction <= 5 ? 5 : 10

  return nice * power
}

/** How many steps the axis is split into, so every tick lands on a whole, round number. */
export function tickCount(max) {
  if (max <= 5) return Math.max(1, Math.round(max))

  return max / 10 ** Math.floor(Math.log10(max)) === 2 ? 4 : 5
}

export function compact(value) {
  const number = Number(value) || 0

  if (number >= 1_000_000) return `${(number / 1_000_000).toFixed(number % 1_000_000 === 0 ? 0 : 1)}M`
  if (number >= 1_000) return `${(number / 1_000).toFixed(number % 1_000 === 0 ? 0 : 1)}K`

  return String(Math.round(number))
}

export function shorten(text, max) {
  const value = String(text ?? '')

  return value.length > max ? `${value.slice(0, max - 1)}…` : value
}

/** '2026-10' becomes 'Oct 26'. */
export function monthLabel(month) {
  const [year, number] = String(month).split('-')
  const names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

  return `${names[Number(number) - 1] ?? number} ${String(year).slice(2)}`
}

/** A point on a circle. Angles run clockwise from 12 o'clock, in degrees. */
export function polar(cx, cy, radius, angle) {
  const radians = ((angle - 90) * Math.PI) / 180

  return { x: r(cx + radius * Math.cos(radians)), y: r(cy + radius * Math.sin(radians)) }
}

/** A ring segment between two angles. */
export function arcPath(cx, cy, outer, inner, start, end) {
  const stop = Math.min(end, start + 359.99)
  const a = polar(cx, cy, outer, start)
  const b = polar(cx, cy, outer, stop)
  const c = polar(cx, cy, inner, stop)
  const d = polar(cx, cy, inner, start)
  const large = stop - start > 180 ? 1 : 0

  return `M${a.x} ${a.y} A${outer} ${outer} 0 ${large} 1 ${b.x} ${b.y} L${c.x} ${c.y} A${inner} ${inner} 0 ${large} 0 ${d.x} ${d.y} Z`
}

const svg = (width, height, label, ...children) =>
  h('svg', { viewBox: `0 0 ${width} ${height}`, role: 'img', 'aria-label': label, preserveAspectRatio: 'xMidYMid meet' }, ...children)

const text = (x, y, content, props = {}) => h('text', { x, y, fontSize: 10, fill: COLORS.muted, ...props }, String(content))

/** Grid lines and numbers down the left side. */
function yAxis(left, right, top, bottom, max, count = tickCount(max)) {
  return Array.from({ length: count + 1 }, (_, index) => {
    const value = (max / count) * index
    const y = r(bottom - ((bottom - top) * index) / count)

    return [
      h('line', { x1: left, x2: right, y1: y, y2: y, stroke: COLORS.grid, strokeWidth: 1 }),
      text(left - 6, y + 3, compact(value), { textAnchor: 'end' }),
    ]
  })
}

/** Vertical bars. Each item is { label, value, extra }. The extra bar is drawn beside it. */
export function barChart(items, { totalLabel = 'TOTAL', extraLabel = 'UNSERVED', title = 'Bar chart' } = {}) {
  if (items.length === 0) return null

  const width = 440
  const height = 250
  const left = 36
  const right = 12
  const top = 28
  const bottom = 178
  const max = niceMax(Math.max(...items.map((item) => Math.max(item.value, item.extra ?? 0))))
  const slot = (width - left - right) / items.length
  const barWidth = Math.min(22, slot / 2.4)

  return svg(
    width, height, title,
    yAxis(left, width - right, top, bottom, max),
    items.map((item, index) => {
      const x = left + slot * index + slot / 2
      const bar = (value, offset, color, name) => {
        const barHeight = r(((bottom - top) * value) / max)
        const y = r(bottom - barHeight)

        return [
          h('rect', { x: r(x + offset), y, width: barWidth, height: barHeight, fill: color }, h('title', {}, `${item.label}: ${value} ${name}`)),
          value > 0 && text(r(x + offset + barWidth / 2), y - 4, value, { textAnchor: 'middle', fill: COLORS.text }),
        ]
      }

      return [
        bar(item.value, item.extra === undefined ? -barWidth / 2 : -barWidth - 1, COLORS.lime, totalLabel),
        item.extra !== undefined && bar(item.extra, 1, COLORS.amber, extraLabel),
        text(r(x), bottom + 14, shorten(item.label, 14), { textAnchor: 'end', transform: `rotate(-32 ${r(x)} ${bottom + 14})` }),
      ]
    }),
    items.some((item) => item.extra !== undefined) && [
      h('rect', { x: width - 150, y: 6, width: 10, height: 10, fill: COLORS.lime }),
      text(width - 136, 15, totalLabel, { fill: COLORS.text }),
      h('rect', { x: width - 80, y: 6, width: 10, height: 10, fill: COLORS.amber }),
      text(width - 66, 15, extraLabel, { fill: COLORS.text }),
    ],
  )
}

/** A ring split into segments { label, value, color }, with a number in the middle. */
export function donutChart(segments, { centerLabel = 'TOTAL', title = 'Donut chart' } = {}) {
  const total = segments.reduce((sum, segment) => sum + segment.value, 0)

  if (total === 0) return null

  const cx = 110
  const cy = 112
  let angle = 0

  return svg(
    440, 224, title,
    segments.filter((segment) => segment.value > 0).map((segment) => {
      const sweep = (segment.value / total) * 360
      const path = arcPath(cx, cy, 92, 58, angle, angle + sweep)

      angle += sweep

      return h('path', { d: path, fill: segment.color, stroke: '#0b0f0b', strokeWidth: 2 }, h('title', {}, `${segment.label}: ${segment.value}`))
    }),
    text(cx, cy + 6, total, { textAnchor: 'middle', fontSize: 26, fill: COLORS.text, fontWeight: 600 }),
    text(cx, cy + 24, centerLabel, { textAnchor: 'middle', fontSize: 9 }),
    segments.map((segment, index) => [
      h('rect', { x: 250, y: 74 + index * 30, width: 12, height: 12, fill: segment.color }),
      text(270, 84 + index * 30, segment.label, { fill: COLORS.text, fontSize: 11 }),
      text(270, 97 + index * 30, `${segment.value} (${Math.round((segment.value / total) * 100)}%)`, { fontSize: 10 }),
    ]),
  )
}

/** A pyramid cut into layers, top first. Each layer is { label, value, color }. */
export function pyramidChart(layers, { title = 'Pyramid chart' } = {}) {
  if (layers.every((layer) => layer.value === 0)) return null

  const width = 440
  const height = 224
  const cx = 130
  const top = 14
  const bottom = 206
  const baseWidth = 220
  const gap = 4
  const layerHeight = (bottom - top - gap * (layers.length - 1)) / layers.length
  const half = (y) => ((y - top) / (bottom - top)) * (baseWidth / 2)

  return svg(
    width, height, title,
    layers.map((layer, index) => {
      const y1 = top + index * (layerHeight + gap)
      const y2 = y1 + layerHeight
      const points = [[cx - half(y1), y1], [cx + half(y1), y1], [cx + half(y2), y2], [cx - half(y2), y2]]
        .map(([x, y]) => `${r(x)},${r(y)}`)
        .join(' ')
      const middle = r((y1 + y2) / 2)

      return [
        h('polygon', { points, fill: layer.color, fillOpacity: layer.value > 0 ? 1 : 0.2 }, h('title', {}, `${layer.label}: ${layer.value}`)),
        h('line', { x1: r(cx + half(middle)) + 6, x2: 260, y1: middle, y2: middle, stroke: COLORS.grid, strokeWidth: 1 }),
        text(268, middle - 2, layer.label, { fill: COLORS.text, fontSize: 11 }),
        text(268, middle + 12, `${layer.value} INFRACTION${layer.value === 1 ? '' : 'S'}`, { fontSize: 10 }),
      ]
    }),
  )
}

function plotPoints(points, left, right, top, bottom, max) {
  const step = points.length > 1 ? (right - left) / (points.length - 1) : 0
  const start = points.length > 1 ? left : (left + right) / 2

  return points.map((point, index) => ({
    ...point,
    x: r(start + step * index),
    y: r(bottom - ((bottom - top) * point.value) / max),
  }))
}

function xLabels(plotted, bottom) {
  const every = Math.ceil(plotted.length / 8)

  return plotted.map((point, index) => (index % every === 0 ? text(point.x, bottom + 16, point.label, { textAnchor: 'middle' }) : null))
}

/** A line over time. Each point is { label, value }. With area: true the space under it is filled. */
export function lineChart(points, { area = false, color = COLORS.lime, title = 'Line chart' } = {}) {
  if (points.length === 0) return null

  const width = 440
  const height = 240
  const left = 44
  const right = 20
  const top = 20
  const bottom = 196
  const max = niceMax(Math.max(...points.map((point) => point.value)))
  const plotted = plotPoints(points, left, width - right, top, bottom, max)
  const line = plotted.map((point, index) => `${index === 0 ? 'M' : 'L'}${point.x} ${point.y}`).join(' ')
  const first = plotted[0]
  const last = plotted[plotted.length - 1]

  return svg(
    width, height, title,
    yAxis(left, width - right, top, bottom, max),
    area && plotted.length > 1 && h('path', { d: `${line} L${last.x} ${bottom} L${first.x} ${bottom} Z`, fill: color, fillOpacity: 0.22 }),
    plotted.length > 1 && h('path', { d: line, fill: 'none', stroke: color, strokeWidth: 2, strokeLinejoin: 'round' }),
    plotted.map((point) => [
      h('circle', { cx: point.x, cy: point.y, r: 4, fill: '#0b0f0b', stroke: color, strokeWidth: 2 }, h('title', {}, `${point.label}: ${point.value}`)),
      plotted.length <= 8 && text(point.x, point.y - 10, compact(point.value), { textAnchor: 'middle', fill: COLORS.text }),
    ]),
    xLabels(plotted, bottom),
  )
}

export const areaChart = (points, options = {}) => lineChart(points, { area: true, color: COLORS.blue, title: 'Area chart', ...options })

/** Stacked bars. bars: [{ label, segments: [{ key, value }] }]. categories: [{ key, label, color }]. */
export function stackedBarChart(bars, categories, { title = 'Stacked bar chart' } = {}) {
  if (bars.length === 0) return null

  const width = 440
  const left = 44
  const right = 12
  const top = 20
  const bottom = 170
  const legendRows = Math.ceil(categories.length / 2)
  const height = bottom + 34 + legendRows * 16
  const totals = bars.map((bar) => bar.segments.reduce((sum, segment) => sum + segment.value, 0))
  const max = niceMax(Math.max(...totals))
  const slot = (width - left - right) / bars.length
  const barWidth = Math.min(34, slot * 0.6)
  const colorOf = Object.fromEntries(categories.map((category) => [category.key, category.color]))

  return svg(
    width, height, title,
    yAxis(left, width - right, top, bottom, max),
    bars.map((bar, index) => {
      const x = left + slot * index + (slot - barWidth) / 2
      let y = bottom

      return [
        bar.segments.filter((segment) => segment.value > 0).map((segment) => {
          const segmentHeight = r(((bottom - top) * segment.value) / max)

          y = r(y - segmentHeight)

          return h('rect', { x: r(x), y, width: barWidth, height: segmentHeight, fill: colorOf[segment.key], stroke: '#0b0f0b', strokeWidth: 1 }, h('title', {}, `${bar.label} // ${segment.label}: ${segment.value}`))
        }),
        text(r(x + barWidth / 2), y - 5, compact(totals[index]), { textAnchor: 'middle', fill: COLORS.text }),
        text(r(x + barWidth / 2), bottom + 14, shorten(bar.label, 10), { textAnchor: 'middle' }),
      ]
    }),
    categories.map((category, index) => {
      const column = index % 2
      const row = Math.floor(index / 2)

      return [
        h('rect', { x: left + column * 190, y: bottom + 28 + row * 16, width: 10, height: 10, fill: category.color }),
        text(left + column * 190 + 16, bottom + 37 + row * 16, shorten(category.label, 24), { fill: COLORS.text }),
      ]
    }),
  )
}

/** Horizontal bars. Each item is { label, value }. */
export function hBarChart(items, { title = 'Horizontal bar chart' } = {}) {
  if (items.length === 0) return null

  const width = 440
  const left = 178
  const right = 40
  const rowHeight = 28
  const height = 12 + items.length * rowHeight
  const max = Math.max(...items.map((item) => item.value))

  return svg(
    width, height, title,
    items.map((item, index) => {
      const y = 8 + index * rowHeight
      const barWidth = r(((width - left - right) * item.value) / max)

      return [
        text(left - 10, y + 14, shorten(item.label, 24), { textAnchor: 'end', fill: COLORS.text, fontSize: 11 }),
        h('rect', { x: left, y, width: width - left - right, height: 18, fill: COLORS.track }),
        h('rect', { x: left, y, width: barWidth, height: 18, fill: SERIES[index % SERIES.length] }, h('title', {}, `${item.label}: ${item.value}`)),
        text(left + barWidth + 8, y + 13, item.value, { fill: COLORS.text, fontSize: 11 }),
      ]
    }),
  )
}

/** Concentric rings. Each ring is { label, percent, color }. */
export function ringsChart(rings, { title = 'Progress rings' } = {}) {
  const cx = 124
  const cy = 112
  const radii = [92, 70, 48]

  return svg(
    440, 224, title,
    rings.map((ring, index) => {
      const radius = radii[index]
      const circumference = 2 * Math.PI * radius
      const filled = r((circumference * Math.min(100, ring.percent)) / 100)

      return [
        h('circle', { cx, cy, r: radius, fill: 'none', stroke: COLORS.track, strokeWidth: 14 }),
        ring.percent > 0 && h('circle', {
          cx, cy, r: radius, fill: 'none', stroke: ring.color, strokeWidth: 14, strokeLinecap: 'butt',
          strokeDasharray: `${filled} ${r(circumference)}`, transform: `rotate(-90 ${cx} ${cy})`,
        }, h('title', {}, `${ring.label}: ${ring.percent}%`)),
      ]
    }),
    rings.map((ring, index) => [
      h('rect', { x: 260, y: 62 + index * 40, width: 12, height: 12, fill: ring.color }),
      text(280, 72 + index * 40, shorten(ring.label, 22), { fill: COLORS.text, fontSize: 11 }),
      text(280, 88 + index * 40, `${ring.percent}%`, { fontSize: 15, fill: ring.color, fontWeight: 600 }),
    ]),
  )
}

/** The same tree as plain SVG text. Used for testing. */
export function toSvgString(node) {
  if (node === null || node === undefined || node === false) return ''
  if (Array.isArray(node)) return node.map(toSvgString).join('')
  if (typeof node === 'string' || typeof node === 'number') return String(node).replace(/&/g, '&amp;').replace(/</g, '&lt;')

  const attribute = (key) => (key === 'viewBox' || key === 'preserveAspectRatio' ? key : key.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`))
  const props = Object.entries(node.props).map(([key, value]) => ` ${attribute(key)}="${String(value).replace(/"/g, '&quot;')}"`).join('')

  return `<${node.tag}${props}>${node.children.map(toSvgString).join('')}</${node.tag}>`
}
