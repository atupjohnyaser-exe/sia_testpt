import { createElement } from 'react'

/** Draws a chart tree from lib/charts.js as real SVG elements. */
export function SvgTree({ node }) {
  if (node === null || node === undefined || node === false) return null
  if (Array.isArray(node)) return node.map((child, index) => <SvgTree key={index} node={child} />)
  if (typeof node === 'string' || typeof node === 'number') return node

  return createElement(node.tag, node.props, ...node.children.map((child, index) => (typeof child === 'object' && child !== null ? <SvgTree key={index} node={child} /> : child)))
}
