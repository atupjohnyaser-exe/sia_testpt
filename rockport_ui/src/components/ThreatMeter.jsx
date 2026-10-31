/** Six small blocks. Filled up to the threat level. */
export function ThreatPips({ level = 1 }) {
  return (
    <span className="threat-pips" role="img" aria-label={`Threat level ${level} of 6`}>
      {[1, 2, 3, 4, 5, 6].map((step) => (
        <i key={step} className={step <= level ? `on t${level}` : ''} />
      ))}
    </span>
  )
}

/** Level, name, blocks, and the suggested response for that level. */
export function ThreatMeter({ threat }) {
  return (
    <div className="threat-meter">
      <div className="threat-head">
        <b>LEVEL {threat.level}</b>
        <span>{threat.label.toUpperCase()}</span>
      </div>
      <ThreatPips level={threat.level} />
      <p className={`threat-response t${threat.level}`}>{threat.response}</p>
      {threat.overridden && <small className="threat-override">SUPERVISOR OVERRIDE // {threat.override_reason}</small>}
    </div>
  )
}
