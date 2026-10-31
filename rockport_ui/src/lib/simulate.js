const whole = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min

/**
 * Realistic made-up numbers for the "generate demo numbers" button
 * on the manhunt form. Training use only.
 */
export function simulateManhunt(districts, infractionTypes) {
  const duration = whole(300, 3600)
  const extra = infractionTypes.length ? infractionTypes.filter(() => Math.random() < 0.18).slice(0, 3) : []

  return {
    minutes: Math.floor(duration / 60),
    seconds: duration % 60,
    outcome: Math.random() < 0.7 ? 'EVADED' : 'APPREHENDED',
    district: districts[whole(0, districts.length - 1)] || 'Civic District',
    counts: {
      officers_involved: whole(4, 60),
      officers_injured: whole(0, 4),
      units_stood_down: whole(0, 8),
      checkpoints: whole(0, 6),
      k9_units: whole(0, 3),
      helicopters: whole(0, 3),
      property_damage_incidents: whole(0, 15),
      insurance_claims: whole(0, 10),
    },
    infractionIds: extra.map((type) => type.id),
  }
}
