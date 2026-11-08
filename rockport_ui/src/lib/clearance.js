/** The five clearance levels. The server enforces them. This file only explains them. */
export const LEVELS = {
  1: {
    name: 'Patrol Officer',
    summary: 'Files field reports and edits their own. Sees no records of people and deletes nothing.',
  },
  2: {
    name: 'Senior Officer',
    summary: 'Reads the database of people, adds field records, and reviews and edits reports.',
  },
  3: {
    name: 'Detective',
    summary: 'Creates and edits records, changes status, marks infractions served, prints case files.',
  },
  4: {
    name: 'Supervisor',
    summary: 'Archives records, exports data, overrides threat levels, sets cost rates, reads the audit log.',
  },
  5: {
    name: 'Chief Admin',
    summary: 'Manages personnel and access, corrects crime history, clears and restores records.',
  },
}

export const levelName = (level) => LEVELS[level]?.name ?? `Level ${level}`

/** "Level 3 // Detective" */
export const levelTag = (level) => `LEVEL ${level} // ${levelName(level).toUpperCase()}`
